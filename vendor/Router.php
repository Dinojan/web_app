<?php
// Router.php - Advanced Router inspired by Laravel
// Features: HTTP method support, route parameters, named routes, middleware, route groups, closures, controller actions

class Route
{
    public $uri;
    public $methods = [];
    public $action;
    public $name;
    public $middleware = [];
    public $originalUri; // For param extraction

    public function __construct($uri, $methods, $action, $name = null, $middleware = [])
    {
        $this->originalUri = $uri;
        $this->uri = $this->compileUri($uri);
        $this->methods = (array) $methods;
        $this->action = $action;
        $this->name = $name;
        $this->middleware = (array) $middleware;
    }

    private function compileUri($uri)
    {
        // Escape regex special characters except { } and /
        $escaped = preg_replace('/([.\/+*?^$()[]|])/','\\\$1', trim($uri, '/'));
        // Convert {param} to regex pattern
        return preg_replace('/\{([^}]+)\}/', '([^/]+)', $escaped);
    }

    public function matches($method, $path)
    {
        if (!in_array($method, $this->methods)) {
            return false;
        }
        return preg_match("#^" . $this->uri . "$#", trim($path, '/'));
    }

    public function extractParameters($path)
    {
        preg_match("#^" . $this->uri . "$#", trim($path, '/'), $matches);
        array_shift($matches); // Remove full match
        $params = [];
        if (preg_match_all('/\{([^}]+)\}/', $this->originalUri, $paramNames)) {
            foreach ($paramNames[1] as $index => $name) {
                $params[$name] = $matches[$index] ?? null;
            }
        }
        return $params;
    }
}

class Router
{
    private static $instance;
    private $routes = [];
    private $routeGroups = [];
    private $namedRoutes = [];
    private $currentRoute;
    private $params = [];
    private $APP_FOLDER = '';

    // Singleton instance
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->APP_FOLDER = getenv('APP_FOLDER');
    }

    public function loadRoutes()
    {
        $routesFile = __DIR__ . '/../routes/web.php';
        if (file_exists($routesFile)) {
            require_once $routesFile;
        }
    }

    // === Route registration methods ===
    public static function get($uri, $action, $name = null, $middleware = [])
    {
        self::add('GET', $uri, $action, $name, $middleware);
    }

    public static function post($uri, $action, $name = null, $middleware = [])
    {
        self::add('POST', $uri, $action, $name, $middleware);
    }

    public static function put($uri, $action, $name = null, $middleware = [])
    {
        self::add('PUT', $uri, $action, $name, $middleware);
    }

    public static function delete($uri, $action, $name = null, $middleware = [])
    {
        self::add('DELETE', $uri, $action, $name, $middleware);
    }

    private static function add($method, $uri, $action, $name, $middleware)
    {
        $router = self::getInstance();
        if (!isset($router->routes[$method])) {
            $router->routes[$method] = [];
        }

        // Handle group prefix if active
        $prefix = '';
        if (!empty($router->routeGroups)) {
            $lastGroup = end($router->routeGroups);
            $prefix = $lastGroup['prefix'] ?? '';
        }

        $fullUri = rtrim($prefix . '/' . ltrim($uri, '/'), '/');
        if ($fullUri === '') $fullUri = '/';

        $route = new Route($fullUri, [$method], $action, $name, $middleware);
        $router->routes[$method][] = $route;

        if ($name) {
            $router->namedRoutes[$name] = $route;
        }
    }

    // === Resource routes ===
    public static function resource($name, $controller, $options = [])
    {
        self::get("/{$name}", "$controller@index");
        self::get("/{$name}/create", "$controller@create");
        self::post("/{$name}", "$controller@store");
        self::get("/{$name}/{id}", "$controller@show");
        self::get("/{$name}/{id}/edit", "$controller@edit");
        self::put("/{$name}/{id}", "$controller@update");
        self::delete("/{$name}/{id}", "$controller@destroy");
    }

    // === Route group support ===
    public static function group($attributes, $callback)
    {
        $router = self::getInstance();
        $router->routeGroups[] = $attributes;
        call_user_func($callback);
        array_pop($router->routeGroups);
    }

    // === Main routing logic ===
    public function route()
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = $this->getPath();
        $this->currentRoute = null;

        foreach ($this->routes[$method] ?? [] as $route) {
            if ($route->matches($method, $path)) {
                $this->currentRoute = $route;
                $this->params = $route->extractParameters($path);
                break;
            }
        }

        if (!$this->currentRoute) {
            $this->handleNotFound($path, $method);
            return;
        }

        $this->runMiddleware($this->currentRoute->middleware);
        $this->executeAction($this->currentRoute->action, $this->params);
    }

    private function runMiddleware($middleware)
    {
        foreach ($middleware as $mw) {
            $mwFile = __DIR__ . "/../app/Middleware/{$mw}.php";
            if (file_exists($mwFile)) {
                require_once $mwFile;
                $instance = new $mw();
                $instance->handle($this);
            }
        }
    }

    private function executeAction($action, $params = [])
    {
        if (is_callable($action)) {
            call_user_func_array($action, array_values($params));
        } elseif (is_string($action) && strpos($action, '@') !== false) {
            list($controllerName, $methodName) = explode('@', $action);
            $controllerFile = __DIR__ . "/../app/Controllers/{$controllerName}.php";
            if (file_exists($controllerFile)) {
                require_once $controllerFile;
                $controller = new $controllerName();
                if (method_exists($controller, $methodName)) {
                    call_user_func_array([$controller, $methodName], array_values($params));
                } else {
                    throw new Exception("Method not found: {$controllerName}::{$methodName}");
                }
            } else {
                throw new Exception("Controller not found: {$controllerName}");
            }
        } else {
            throw new Exception("Invalid route action type");
        }
    }

    // === Generate URL from route name ===
    public function url($name, $parameters = [])
    {
        $route = $this->namedRoutes[$name] ?? null;
        if (!$route) {
            return '#';
        }

        // Replace {param} placeholders
        $uri = $route->originalUri;
        foreach ($parameters as $key => $value) {
            $uri = str_replace("{{$key}}", $value, $uri);
        }

        // Determine if /public/ should be added (local) or skipped (cPanel)
        $skipRoot = defined('SKIP_ROOT') ? SKIP_ROOT : '';
        $base = rtrim(BASE_URL, '/') . '/';
        $uri = ltrim($uri, '/');

        // Build full URL
        return $base . $skipRoot . $uri;
    }

    // === Handle 404 ===
    private function handleNotFound($path = '', $method = '')
    {
        http_response_code(404);
        echo "<h1>404 - Route not found</h1>";
        echo "<pre>PATH: {$path}\nMETHOD: {$method}</pre>";
        exit;
    }

    // === Get current request path ===
    private function getPath()
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        // Normalize slashes
        $path = str_replace('\\', '/', $path);

        // Remove "/public" and app folder prefix like "/web_app_1"
        $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        if ($scriptName !== '/' && $scriptName !== '\\') {
            $path = preg_replace('#^' . preg_quote($scriptName, '#') . '#', '', $path);
        }

        // Remove app folder name if still present
        $appFolder = basename(dirname(__DIR__)); // e.g. "web_app_1"
        $path = preg_replace('#^/?' . preg_quote($appFolder, '#') . '/#', '', $path);

        $path = trim($path, '/');
        return $path ?: '/';
    }

    public function redirect($path)
    {
        header("Location: " . BASE_URL . '/' . ltrim($path, '/'));
        exit;
    }

    public function dispatch()
{
    // Load route definitions (if not already loaded)
    $this->loadRoutes();

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $path = $this->getPath();
    $this->currentRoute = null;

    // Find matching route
    foreach ($this->routes[$method] ?? [] as $route) {
        if ($route->matches($method, $path)) {
            $this->currentRoute = $route;
            $this->params = $route->extractParameters($path);
            break;
        }
    }

    if (!$this->currentRoute) {
        $this->handleNotFound($path, $method);
        return;
    }

    // Run middleware (if any)
    $this->runMiddleware($this->currentRoute->middleware);

    // Execute action (and capture the result)
    return $this->executeActionAndReturn($this->currentRoute->action, $this->params);
}
private function executeActionAndReturn($action, $params = [])
{
    if (is_callable($action)) {
        return call_user_func_array($action, array_values($params));
    }

    if (is_string($action) && strpos($action, '@') !== false) {
        list($controllerName, $methodName) = explode('@', $action);
        $controllerFile = __DIR__ . "/../app/Controllers/{$controllerName}.php";

        if (!file_exists($controllerFile)) {
            throw new Exception("Controller not found: {$controllerName}");
        }

        require_once $controllerFile;
        $controller = new $controllerName();

        if (!method_exists($controller, $methodName)) {
            throw new Exception("Method not found: {$controllerName}::{$methodName}");
        }

        // ✅ Return whatever the controller returns
        return call_user_func_array([$controller, $methodName], array_values($params));
    }

    throw new Exception("Invalid route action type");
}

}
?>
