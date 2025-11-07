@extends('app')

@section('content')
    <h1>Welcome to My MVC App!</h1>
    <p>This is a simple, empty project setup with advanced routing inspired by Laravel.</p>

    <ul>
        <li><a href="<?php echo route('dashboard'); ?>">Go to Dashboard</a></li>
        <li><a href="<?php echo route('profile.show', ['id' => 123]); ?>">View Profile Example</a></li>
    </ul>

    <p>Project ready for development. Add your routes, controllers, and views!</p>
@endsection
