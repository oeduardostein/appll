<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Usuario; // ajuste se o namespace/modelo for diferente

class PasswordResetController extends Controller
{
    /**
     * POST /api/password/forgot
     * body: { "email": "user@example.com" }
     */
    public function requestCode(Request $request)
    {
        $data = $request->validate([
            'email' => ['required','email']
        ]);

        // Procura usuário
        $usuario = User::where('email', $data['email'])->first();

        // (Opção de segurança): sempre retornar sucesso para não revelar se existe
        if (!$usuario) {
            // Retorna 200 para evitar enumeração de e-mails
            return response()->json([
                'message' => 'Se existir uma conta para este e-mail, enviaremos um código.'
            ]);
        }

        // Gera código numérico de 6 dígitos
        $codigo = (string) random_int(100000, 999999);

        // Salva no banco
        $usuario->codigo = $codigo;
        $usuario->save();

        // Envia e-mail simples (usa as configs do .env)
        try {
            Mail::raw(
                "Seu código de recuperação de senha é: {$codigo}\n\nSe não foi você, ignore este e-mail.",
                function ($message) use ($usuario) {
                    $message->to($usuario->email)
                            ->subject('Código de recuperação de senha');
                }
            );
        } catch (\Throwable $e) {
            // Em produção, logue o erro
            return response()->json([
                'message' => 'Não foi possível enviar o e-mail agora.'
            ], 500);
        }

        return response()->json([
            'message' => 'Se existir uma conta para este e-mail, enviaremos um código.'
        ]);
    }

    /**
     * POST /api/password/reset
     * body: { "email": "...", "codigo": "123456", "password": "...", "password_confirmation": "..." }
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => ['required','email'],
            'codigo'   => ['required','string','min:4','max:10'],
            'password' => ['required','confirmed','min:8'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dados inválidos.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $email  = $request->input('email');
        $codigo = $request->input('codigo');
        $senha  = $request->input('password');

        $usuario = Usuario::where('email', $email)->first();

        // Não revela se o e-mail existe
        if (!$usuario) {
            return response()->json([
                'message' => 'Código inválido ou expirado.'
            ], 422);
        }

        // Confere o código
        if (!$usuario->codigo || $usuario->codigo !== $codigo) {
            return response()->json([
                'message' => 'Código inválido ou expirado.'
            ], 422);
        }

        // Atualiza senha com hash e limpa o código
        $usuario->password = Hash::make($senha);
        $usuario->codigo = null;
        $usuario->save();

        return response()->json([
            'message' => 'Senha redefinida com sucesso.'
        ]);
    }
}
