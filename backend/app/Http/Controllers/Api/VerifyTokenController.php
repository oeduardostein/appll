<?php

namespace App\Http\Controllers\Api;

use App\Services\DetranCaptchaClient;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyTokenController extends Controller
{
    public function __construct(private readonly DetranCaptchaClient $captchaClient)
    {
    }

    /**
     * Verifica se o token JSESSIONID armazenado no banco está funcionando.
     * Esta rota é pública e não requer autenticação.
     */
    public function __invoke(Request $request): Response
    {
        try {
            // Buscar token do banco
            $token = DB::table('admin_settings')->where('id', 1)->value('value');

            if (!$token) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Nenhum token encontrado no banco de dados.',
                    'token_exists' => false,
                    'token_valid' => false,
                ], Response::HTTP_NOT_FOUND);
            }

            // Fazer requisição de teste para o endpoint de captcha do DETRAN
            // Este é um endpoint simples que não requer muitos parâmetros
            $captchaUrl = 'https://www.e-crvsp.sp.gov.br/gever/jcaptcha?id=' . time();

            $headers = [
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
                'Cache-Control' => 'max-age=0',
                'Connection' => 'keep-alive',
                'Cookie' => 'dataUsuarPublic=Thu%20Aug%2001%202024%2014%3A46%3A49%20GMT-0300%20(Hor%C3%A1rio%20Padr%C3%A3o%20de%20Bras%C3%ADlia); naoExibirPublic=sim; JSESSIONID=' . $token,
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'none',
                'Sec-Fetch-User' => '?1',
                'Upgrade-Insecure-Requests' => '1',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
            ];

            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->withOptions(['verify' => false])
                ->get($captchaUrl);

            $isValid = $response->successful();
            $statusCode = $response->status();
            $bodyLength = strlen($response->body());

            // Verificar se a resposta parece ser uma imagem de captcha válida
            $contentType = $response->header('Content-Type', '');
            $isImage = str_contains($contentType, 'image') || $bodyLength > 1000;

            $tokenValid = $isValid && $isImage;

            Log::info('Verificação de token realizada', [
                'token_exists' => true,
                'token_valid' => $tokenValid,
                'status_code' => $statusCode,
                'content_type' => $contentType,
                'body_length' => $bodyLength,
            ]);

            if ($tokenValid) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Token está funcionando corretamente.',
                    'token_exists' => true,
                    'token_valid' => true,
                    'test_details' => [
                        'status_code' => $statusCode,
                        'content_type' => $contentType,
                        'response_size' => $bodyLength,
                    ],
                ], Response::HTTP_OK);
            }

            // Token existe mas não está funcionando
            return response()->json([
                'status' => 'error',
                'message' => 'Token encontrado no banco, mas não está funcionando. Pode estar expirado ou inválido.',
                'token_exists' => true,
                'token_valid' => false,
                'test_details' => [
                    'status_code' => $statusCode,
                    'content_type' => $contentType,
                    'response_size' => $bodyLength,
                    'error' => $statusCode >= 400 ? 'Requisição falhou' : 'Resposta não é uma imagem válida',
                ],
            ], Response::HTTP_OK);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Erro de conexão ao verificar token', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Não foi possível conectar ao serviço do DETRAN para verificar o token.',
                'token_exists' => true,
                'token_valid' => false,
                'error' => 'Connection error',
            ], Response::HTTP_BAD_GATEWAY);

        } catch (\Exception $e) {
            Log::error('Erro ao verificar token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu um erro ao verificar o token.',
                'token_exists' => true,
                'token_valid' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

