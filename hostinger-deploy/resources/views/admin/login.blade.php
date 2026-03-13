@extends('layouts.app')

@section('title', 'Login Admin')

@section('content')
<div class="login-container">
    <div class="login-box">
        <div class="login-header">
            <i class="fas fa-shield-alt"></i>
            <h2>Login Admin</h2>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('admin.login.submit') }}" method="POST" class="login-form">
            @csrf

            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i>
                    Kata Laluan
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autofocus
                    placeholder="Masukkan kata laluan admin">
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-sign-in-alt"></i>
                Login
            </button>
        </form>

        <p class="login-footer">
            <a href="{{ route('home') }}">
                <i class="fas fa-arrow-left"></i>
                Kembali ke Utama
            </a>
        </p>
    </div>
</div>
@endsection

@push('styles')
<style>
    .login-container {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 60vh;
        padding: 2rem;
    }

    .login-box {
        width: 100%;
        max-width: 450px;
        background: var(--bg-white);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-lg);
        padding: 2.5rem;
        animation: fadeIn 0.4s ease;
    }

    .login-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .login-header i {
        font-size: 3rem;
        color: var(--primary-color);
        margin-bottom: 1rem;
        display: block;
    }

    .login-header h2 {
        margin: 0;
        color: var(--text-dark);
        font-size: 1.75rem;
        font-weight: 700;
    }

    .login-form {
        margin-bottom: 1.5rem;
    }

    .login-form .form-group {
        margin-bottom: 1.5rem;
    }

    .login-form .form-group label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
        font-weight: 600;
        color: var(--text-dark);
        font-size: 1rem;
    }

    .login-form .form-group label i {
        color: var(--primary-color);
    }

    .login-form .form-group input {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 2px solid var(--border-color);
        border-radius: var(--radius-md);
        font-size: 1rem;
        transition: all 0.3s ease;
        background-color: var(--bg-light);
    }

    .login-form .form-group input:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
        background-color: var(--bg-white);
    }

    .login-form .form-group input::placeholder {
        color: var(--text-light);
        opacity: 0.7;
    }

    .btn-block {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.875rem 1.5rem;
        font-size: 1rem;
    }

    .login-footer {
        text-align: center;
        margin: 0;
        padding-top: 1.5rem;
        border-top: 1px solid var(--border-color);
    }

    .login-footer a {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--text-light);
        text-decoration: none;
        font-size: 0.9375rem;
        transition: color 0.3s ease;
    }

    .login-footer a:hover {
        color: var(--primary-color);
    }

    .alert {
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.9375rem;
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-success i {
        color: #10b981;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .alert-danger i {
        color: #ef4444;
    }

    @media (max-width: 768px) {
        .login-container {
            padding: 1rem;
            min-height: 50vh;
        }

        .login-box {
            padding: 2rem 1.5rem;
        }

        .login-header i {
            font-size: 2.5rem;
        }

        .login-header h2 {
            font-size: 1.5rem;
        }

        .login-form .form-group input {
            padding: 0.75rem;
        }

        .btn-block {
            padding: 0.75rem 1.25rem;
        }
    }
</style>
@endpush
