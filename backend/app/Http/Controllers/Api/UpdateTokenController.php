<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class UpdateTokenController extends Controller
{
    /**
     * Atualiza o token JSESSIONID no banco de dados.
     * Esta rota é pública e não requer autenticação.
     */
    public function __invoke(Request $request): Response
    {
        $request->validate([
            'token' => ['required', 'string', 'max:255'],
        ]);

        $token = $request->input('token');

        try {
            // Primeiro tentar atualizar pelo id=1 (padrão usado nos outros controllers)
            $updated = DB::table('admin_settings')
                ->where('id', 1)
                ->update([
                    'value' => $token,
                    'updated_at' => now(),
                ]);

            if ($updated === 0) {
                // Se não encontrou registro com id=1, tentar atualizar ou criar pelo admin_id + key
                // Assumindo admin_id=1 e key='session_token' ou 'value'
                DB::table('admin_settings')->updateOrInsert(
                    [
                        'admin_id' => 1,
                        'key' => 'session_token',
                    ],
                    [
                        'value' => $token,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                Log::info('Token criado/atualizado via API pública (updateOrInsert)', [
                    'admin_id' => 1,
                ]);
            } else {
                Log::info('Token atualizado via API pública (id=1)', [
                    'id' => 1,
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Token atualizado com sucesso.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar token via API pública', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Não foi possível atualizar o token.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

