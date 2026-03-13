<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    /**
     * Display the admin login form.
     */
    public function showLoginForm(): View
    {
        return view('admin.login');
    }

    /**
     * Handle admin login form submission.
     */
    public function login(Request $request)
    {
        $password = $request->input('password');
        $expectedPassword = config('admin.password');

        if ($password === $expectedPassword) {
            session(['admin_logged_in' => true]);

            return redirect()->route('admin')->with('success', 'Login berjaya');

        }

        return back()->with('error', 'Kata laluan salah');
    }

    /**
     * Handle admin logout.
     */
    public function logout(Request $request)
    {
        $request->session()->forget('admin_logged_in');

        return redirect()->route('home')->with('success', 'Logout berjaya');
    }
}
