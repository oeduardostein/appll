<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (session('admin_authenticated')) {
            return redirect()->route('admin.clients.index');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // TODO: substituir por autenticação real integrada ao backend
        session(['admin_authenticated' => true, 'admin_user' => [
            'name' => 'Lucas',
            'role' => 'Administrador',
            'email' => $credentials['email'],
        ]]);

        return redirect()->route('admin.clients.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['admin_authenticated', 'admin_user']);

        return redirect()->route('admin.login');
    }
}
