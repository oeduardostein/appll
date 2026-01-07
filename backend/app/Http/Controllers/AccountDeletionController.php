<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Controllers\Api\Traits\FindsUserFromApiToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AccountDeletionController extends Controller
{
    use FindsUserFromApiToken;

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

    /**
     * Handle deletion for authenticated API users.
     */
    public function destroyAuthenticated(Request $request): JsonResponse
    {
        $user = $this->findUserFromRequest($request);

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Não autenticado.',
            ], 401);
        }

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Conta excluída com sucesso.',
            'redirect_to' => 'login',
        ]);
    }

}
