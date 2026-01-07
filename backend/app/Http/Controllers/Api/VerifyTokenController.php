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

            // Fazer requisição de teste para a página inicial do DETRAN
            // Isso valida se a sessão está ativa sem fazer uma consulta real
            $testUrl = 'https://www.e-crvsp.sp.gov.br/gever/GVR/pesquisa/baseEstadual.do';

            $headers = [
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
                'Cache-Control' => 'max-age=0',
                'Connection' => 'keep-alive',
                'Referer' => 'https://www.e-crvsp.sp.gov.br/gever/GVR/pesquisa/baseEstadual.do',
                'Sec-Fetch-Dest' => 'frame',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'same-origin',
                'Sec-Fetch-User' => '?1',
                'Upgrade-Insecure-Requests' => '1',
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36',
                'sec-ch-ua' => '"Chromium";v="134", "Not:A-Brand";v="24", "Google Chrome";v="134"',
                'sec-ch-ua-mobile' => '?0',
                'sec-ch-ua-platform' => '"macOS"',
            ];

            $cookieDomain = 'www.e-crvsp.sp.gov.br';

            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->withOptions(['verify' => false])
                ->withCookies([
                    'dataUsuarPublic' => 'Mon Mar 24 2025 08:14:44 GMT-0300 (Horário Padrão de Brasília)',
                    'JSESSIONID' => $token,
                ], $cookieDomain)
                ->get($testUrl);

            $isValid = $response->successful();
            $statusCode = $response->status();
            $body = $response->body();
            $bodyLength = strlen($body);

            // Verificar se a resposta contém conteúdo válido (não é página de erro ou login)
            $contentType = $response->header('Content-Type', '');
            $isHtml = str_contains($contentType, 'html') || str_contains($body, '<html');
            
            // Verificar se não é página de erro ou redirecionamento para login
            $hasError = stripos($body, 'erro') !== false || 
                       stripos($body, 'sessão expirada') !== false ||
                       stripos($body, 'login') !== false ||
                       stripos($body, 'acesso negado') !== false;
            
            // Verificar se contém elementos esperados da página de pesquisa
            $hasValidContent = stripos($body, 'baseEstadual') !== false ||
                              stripos($body, 'pesquisa') !== false ||
                              stripos($body, 'placa') !== false;

            $tokenValid = $isValid && $isHtml && !$hasError && ($hasValidContent || $bodyLength > 500);

            Log::info('Verificação de token realizada', [
                'token_exists' => true,
                'token_valid' => $tokenValid,
                'status_code' => $statusCode,
                'content_type' => $contentType,
                'body_length' => $bodyLength,
                'has_error' => $hasError,
                'has_valid_content' => $hasValidContent,
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
                    'has_error' => $hasError,
                    'has_valid_content' => $hasValidContent,
                    'error' => $statusCode >= 400 ? 'Requisição falhou' : ($hasError ? 'Página contém erros ou redirecionamento' : 'Resposta não contém conteúdo válido'),
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

