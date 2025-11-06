<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function showRequestForm(): View
    {
        return view('admin.auth.forgot-password');
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $admin = Admin::query()->where('email', $data['email'])->first();

        if (! $admin) {
            return back()
                ->with('status', 'Se existir uma conta para este e-mail, enviaremos um código.')
                ->withInput();
        }

        $codigo = (string) random_int(100000, 999999);

        $admin->codigo = $codigo;
        $admin->save();

        try {
            Mail::raw(
                "Seu código de recuperação de senha é: {$codigo}\n\nSe não foi você, ignore este e-mail.",
                function ($message) use ($admin) {
                    $message->to($admin->email)
                        ->subject('Código de recuperação de senha');
                }
            );
        } catch (\Throwable $e) {
            return back()
                ->withErrors(['email' => 'Não foi possível enviar o e-mail agora. Tente novamente em instantes.'])
                ->withInput();
        }

        return redirect()
            ->route('admin.password.reset.form', ['email' => $admin->email])
            ->with('status', 'Enviamos um código de recuperação para o e-mail informado.');
    }

    public function showResetForm(Request $request): View
    {
        return view('admin.auth.reset-password', [
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'codigo' => ['required', 'string', 'min:4', 'max:10'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $admin = Admin::query()->where('email', $credentials['email'])->first();

        if (! $admin || $admin->codigo !== $credentials['codigo']) {
            return back()
                ->withErrors([
                    'codigo' => 'Código inválido ou expirado.',
                ])
                ->withInput($request->except('password', 'password_confirmation'));
        }

        $admin->password = Hash::make($credentials['password']);
        $admin->codigo = null;
        $admin->save();

        return redirect()
            ->route('admin.login')
            ->with('status', 'Senha redefinida com sucesso. Acesse com sua nova senha.');
    }
}
