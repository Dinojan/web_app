<?php
namespace Core;
// core/View.php - Simple Blade-like compiler for directives (no compiled files)
class View
{
    private $data = [];
    private $sections = [];
    private $includedFiles = [];
    private $layout = null;

    public function __construct($data = [])
    {
        $this->data = $data + get_defined_vars();
        $this->sections = [];
        $this->includedFiles = [];
        $this->layout = null;
    }

    public function render($viewPath, $data = [])
{
    if (!file_exists($viewPath)) {
        die("<pre>❌ View not found: $viewPath</pre>");
    }

    // For debugging only (optional)
    // echo "<pre>✅ Rendering view: $viewPath</pre>";

    $this->data = array_merge($this->data, $data);
    $source = file_get_contents($viewPath);

    // Compile all Blade-like directives
    $compiled = $this->compileDirectives($source);

    // Evaluate the view
    extract($this->data);
    ob_start();
    eval('?>' . $compiled);
    $content = ob_get_clean();

    // If this view extends a layout, render it
    if (!empty($this->layout)) {
        $layout = $this->layout;
        $this->layout = null;

        // Pass along compiled sections
        $layoutView = new self($this->data);
        $layoutView->sections = $this->sections;

        return $layoutView->render($layout, $this->data);
    }

    // Return final HTML
    return $content;
}



    private function compileDirectives($source)
    {
        // All common directives compiled here (regex-based replacements)
        $source = $this->compileExtends($source);
        $source = $this->compileSections($source);
        $source = $this->compileYield($source); // Added for layouts
        $source = $this->compileStacks($source);
        $source = $this->compileControl($source);
        $source = $this->compileEcho($source);
        $source = $this->compileUnescaped($source);
        $source = $this->compileAuth($source);
        $source = $this->compileCan($source);
        $source = $this->compileInclude($source);
        $source = $this->compilePhp($source);
        $source = $this->compileCsrf($source);
        $source = $this->compileJson($source);
        $source = $this->compileLang($source);
        $source = $this->compileComments($source);
        $source = $this->compileVerbatim($source);
        return $source;
    }

    // 1. @extends
    private function compileExtends($source)
{
    if (preg_match('/@extends\([\'"]([^\'"]+)[\'"]\)/', $source, $matches)) {
        $layout = $matches[1];
        $source = str_replace($matches[0], '', $source); // remove directive
        $this->layout = __DIR__ . "/../resources/views/" . str_replace('.', '/', $layout) . ".php";
    }
    return $source;
}



    // 2. @section / @endsection
    private function compileSections($source)
    {
        // Match @section('name') ... @endsection
        $source = preg_replace_callback(
            '/@section\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)(.*?)@endsection/s',
            function ($matches) {
                $name = $matches[1];
                $content = $matches[2];
                // Store the section in $this->sections
                $this->sections[$name] = $content;
                // Remove the original @section ... @endsection from output
                return '';
            },
            $source
        );
        return $source;
    }


    // 3. @yield (in layouts)
    private function compileYield($source)
    {
        // Replace @yield('name') with the stored section content
        $source = preg_replace_callback(
            '/@yield\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
            function ($matches) {
                $name = $matches[1];
                return $this->sections[$name] ?? '';
            },
            $source
        );
        return $source;
    }


    // 4. {{ }} (escaped echo)
    private function compileEcho($source)
{
    $source = preg_replace('/\{\{\s*(.+?)\s*\}\}/', '<?php echo $this->e($1); ?>', $source);
    return $source;
}


    // 5. {!! !!} (unescaped)
    private function compileUnescaped($source)
    {
        $source = preg_replace('/\{\{\!\s*(.+?)\s*\!\}\}/', '<?php echo $1; ?>', $source);
        return $source;
    }

    // 6. @push / @endpush and @stack
    private function compileStacks($source)
    {
        $source = preg_replace('/@push\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', '<?php ob_start(); ?>', $source);
        $source = str_replace('@endpush', '<?php $stack[$1] .= ob_get_clean(); ?>', $source);
        $source = preg_replace('/@stack\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', '<?php echo $stack[$1] ?? ""; ?>', $source);
        return $source;
    }

    // 7. @if / @endif, @else, @elseif
    private function compileControl($source)
    {
        $source = str_replace('@endif', '<?php endif; ?>', $source);
        $source = str_replace('@else', '<?php else: ?>', $source);
        $source = str_replace('@elseif', '<?php elseif', $source);
        $source = preg_replace('/@if\s*\((.+?)\)/', '<?php if($1): ?>', $source);
        $source = str_replace('@unless', '<?php if(!', $source);
        $source = str_replace('@endunless', '): ?> <?php endif; ?>', $source);
        // @forelse / @empty / @endforelse
        $source = preg_replace('/@forelse\s*\((.+?)\s+as\s+(.+?)\)/', '<?php $loop = $1; if($loop->count()): foreach($loop as $2): ?>', $source);
        $source = str_replace('@empty', '<?php else: ?>', $source);
        $source = str_replace('@endforelse', '<?php endforeach; endif; ?>', $source);
        // @foreach
        $source = preg_replace('/@foreach\s*\((.+?)\s+as\s+(.+?)\)/', '<?php foreach($1 as $2): ?>', $source);
        $source = str_replace('@endforeach', '<?php endforeach; ?>', $source);
        // @while
        $source = preg_replace('/@while\s*\((.+?)\)/', '<?php while($1): ?>', $source);
        $source = str_replace('@endwhile', '<?php endwhile; ?>', $source);
        // @switch / @case / @break / @endswitch
        $source = preg_replace('/@switch\s*\((.+?)\)/', '<?php switch($1): ?>', $source);
        $source = str_replace('@case', '<?php case', $source);
        $source = str_replace('@break', 'break;', $source);
        $source = str_replace('@endswitch', '<?php endswitch; ?>', $source);
        return $source;
    }

    // 8. @auth / @endauth, @guest / @endguest
    private function compileAuth($source)
    {
        $source = preg_replace('/@auth/', '<?php if(auth()->check()): ?>', $source);
        $source = str_replace('@endauth', '<?php endif; ?>', $source);
        $source = preg_replace('/@guest/', '<?php if(!auth()->check()): ?>', $source);
        $source = str_replace('@endguest', '<?php endif; ?>', $source);
        return $source;
    }

    // 9. @can / @endcan, @cannot
    private function compileCan($source)
    {
        $source = preg_replace('/@can\s*\([\'"](.+?)[\'"]\)/', '<?php if(auth()->user()->can($1)): ?>', $source);
        $source = str_replace('@endcan', '<?php endif; ?>', $source);
        $source = preg_replace('/@cannot\s*\([\'"](.+?)[\'"]\)/', '<?php if(!auth()->user()->can($1)): ?>', $source);
        return $source;
    }

    // 10. @include, @require, @require_once, @include_once (enhanced with recursive compilation)
    private function compileInclude($source)
    {
        // @include
        $source = preg_replace_callback('/@include\s*\([\'"]([^\'"]+)[\'"]\)/', function ($matches) {
            $view = $matches[1];
            $viewPath = view_path($view);
            if (!in_array($viewPath, $this->includedFiles)) {
                $this->includedFiles[] = $viewPath;
                $includeView = new View($this->data);
                return $includeView->render($viewPath, $this->data);
            }
            return '';
        }, $source);

        // @require (fatal error if missing)
        $source = preg_replace_callback('/@require\s*\([\'"]([^\'"]+)[\'"]\)/', function ($matches) {
            $view = $matches[1];
            $viewPath = view_path($view);
            if (!file_exists($viewPath)) {
                throw new Exception("Required view not found: $view");
            }
            if (!in_array($viewPath, $this->includedFiles)) {
                $this->includedFiles[] = $viewPath;
                $includeView = new View($this->data);
                return $includeView->render($viewPath, $this->data);
            }
            return '';
        }, $source);

        // @require_once (include once, fatal if missing)
        $source = preg_replace_callback('/@require_once\s*\([\'"]([^\'"]+)[\'"]\)/', function ($matches) {
            $view = $matches[1];
            $viewPath = view_path($view);
            if (!file_exists($viewPath)) {
                throw new Exception("Required view not found: $view");
            }
            if (!in_array($viewPath, $this->includedFiles)) {
                $this->includedFiles[] = $viewPath;
                $includeView = new View($this->data);
                return $includeView->render($viewPath, $this->data);
            }
            return '';
        }, $source);

        // @include_once (include once, non-fatal if missing)
        $source = preg_replace_callback('/@include_once\s*\([\'"]([^\'"]+)[\'"]\)/', function ($matches) {
            $view = $matches[1];
            $viewPath = view_path($view);
            if (file_exists($viewPath) && !in_array($viewPath, $this->includedFiles)) {
                $this->includedFiles[] = $viewPath;
                $includeView = new View($this->data);
                return $includeView->render($viewPath, $this->data);
            }
            return '';
        }, $source);

        return $source;
    }

    // 11. @php / @endphp
    private function compilePhp($source)
    {
        $source = str_replace('@endphp', '?>', $source);
        $source = preg_replace('/@php/', '<?php', $source);
        return $source;
    }

    // 12. @csrf
    private function compileCsrf($source)
    {
        $source = str_replace('@csrf', '<input type="hidden" name="_token" value="' . csrf_token() . '">', $source);
        return $source;
    }

    // 13. @method
    private function compileMethod($source)
    {
        $source = preg_replace('/@method\s*\([\'"](.+?)[\'"]\)/', '<input type="hidden" name="_method" value="$1">', $source);
        return $source;
    }

    // 14. @json
    private function compileJson($source)
    {
        $source = preg_replace('/@json\s*\((.+?)\)/', '<?php echo json_encode($1, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>', $source);
        return $source;
    }

    // 15. @lang or __()
    private function compileLang($source)
    {
        $source = preg_replace('/@lang\s*\([\'"](.+?)[\'"]\)/', '<?php echo __($1); ?>', $source);
        $source = preg_replace('/\{\{\s*__\((\'|"(.+?)"|\'(.+?)\')\s*\}\}/', '<?php echo __($1); ?>', $source);
        return $source;
    }

    // 16. {{-- --}} (comments)
    private function compileComments($source)
    {
        $source = preg_replace('/\{\{\-\-.*?\-\-\}\}/s', '', $source);
        return $source;
    }

    // 17. @verbatim / @endverbatim
    private function compileVerbatim($source)
    {
        $source = preg_replace('/@verbatim/', '<!-- verbatim start -->', $source);
        $source = str_replace('@endverbatim', '<!-- verbatim end -->', $source);
        // Raw output between markers
        return $source;
    }

    // Helper: e() for escaping
    private function e($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
    }
}