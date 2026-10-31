<?php

namespace App\Http\Controllers\authentications;

use App\Http\Controllers\Controller;
use App\Support\DashboardLanding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginBasic extends Controller
{
  public function index()
  {
    return view('content.authentications.auth-login-basic');
  }

  // Handle login submit
  public function login(Request $request)
  {
    $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt(
        $request->only('email', 'password'),
        $request->boolean('remember')
    )) {
        $request->session()->regenerate();

        $landing = DashboardLanding::routeName(Auth::user());

        return redirect()->route($landing ?? 'dashboard');
    }

    return back()->withErrors([
        'email' => 'Invalid credentials',
    ])->onlyInput('email');
  }

  // Logout
  public function logout(Request $request)
  {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
  }
}
