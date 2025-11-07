@extends('app')

@section('content')
    <header>
        <h2>My MVC App</h2>
        <nav><a href="{{ route('home') }}">Home</a> | <a href="{{ route('dashboard') }}">Dashboard</a></nav>
    </header>
    <main>
    </main>
    <footer style="margin-top: 40px; text-align: center; color: #666;">
        &copy; <?php echo date('Y'); ?> My MVC App
    </footer>
@endsection