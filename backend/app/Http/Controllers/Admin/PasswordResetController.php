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
                ->with('status', 'Caso exista um cadastro para esse e-mail, enviaremos um código de verificação nos próximos minutos.')
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
            report($e);
            return back()
                ->withErrors(['email' => 'Não conseguimos enviar o e-mail neste momento. Tente novamente em alguns minutos.'])
                ->withInput();
        }

        return redirect()
            ->route('admin.password.reset.form', ['email' => $admin->email])
            ->with('status', 'Acabamos de enviar um código de verificação para o e-mail informado. Verifique também a pasta de spam.');
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
                    'codigo' => 'Não encontramos uma combinação válida de e-mail e código. Gere um novo código e tente novamente.',
                ])
                ->withInput($request->except('password', 'password_confirmation'));
        }

        $admin->password = Hash::make($credentials['password']);
        $admin->codigo = null;
        $admin->save();

        return redirect()
            ->route('admin.login')
            ->with('status', 'Senha atualizada com sucesso. Entre com a nova credencial.');
    }
}
