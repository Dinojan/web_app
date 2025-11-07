@extends('app')

@section('content')
    <header>
        <h2>My MVC App</h2>
        <nav><a href="<?php echo BASE_URL; ?>">Home</a> | <a href="<?php echo BASE_URL; ?>dashboard">Dashboard</a></nav>
    </header>
    <main>
    </main>
    <footer style="margin-top: 40px; text-align: center; color: #666;">
        &copy; <?php echo date('Y'); ?> My MVC App
    </footer>
@endsection