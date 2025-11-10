<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AccountDeletionController extends Controller
{
    /**
     * Display the account deletion form.
     */
    public function show(): View
    {
        return view('account-deletion');
    }

    /**
     * Handle the deletion request.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return back()
                ->withInput(['email' => $data['email']])
                ->withErrors([
                    'email' => 'Email ou senha inválidos.',
                ]);
        }

        $user->delete();

        return back()->with('status', 'Conta excluída com sucesso.');
    }
}
