<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\CaptchaException;
use App\Services\DetranCaptchaClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class CaptchaSolveController extends Controller
{
    public function __construct(private readonly DetranCaptchaClient $captchaClient)
    {
    }

    public function __invoke(): JsonResponse
    {
        $apiKey = config('services.twocaptcha.key');

        if (!$apiKey) {
            return response()->json(
                ['message' => 'API key do 2Captcha não configurada.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        try {
            $captcha = $this->captchaClient->fetch();
        } catch (CaptchaException $exception) {
            return response()->json(
                ['message' => $exception->getMessage()],
                $exception->statusCode
            );
        }

        $captchaBase64 = base64_encode($captcha['body']);

        $uploadResponse = Http::asForm()->post('https://2captcha.com/in.php', [
            'key' => $apiKey,
            'method' => 'base64',
            'body' => $captchaBase64,
            'json' => 1,
        ]);

        if (!$uploadResponse->successful()) {
            return response()->json(
                ['message' => 'Falha ao enviar captcha para o 2Captcha.'],
                Response::HTTP_BAD_GATEWAY
            );
        }

        $uploadPayload = $uploadResponse->json();
        if (!is_array($uploadPayload) || ($uploadPayload['status'] ?? 0) !== 1) {
            $errorMessage = $uploadPayload['request'] ?? 'Resposta inesperada do 2Captcha.';
            return response()->json(
                ['message' => '2Captcha respondeu com erro: ' . $errorMessage],
                Response::HTTP_BAD_GATEWAY
            );
        }

        $captchaId = (string) $uploadPayload['request'];
        $solution = $this->retrieveSolution($apiKey, $captchaId);

        if ($solution === null) {
            return response()->json(
                ['message' => 'O 2Captcha não retornou uma solução a tempo.'],
                Response::HTTP_GATEWAY_TIMEOUT
            );
        }

        return response()->json([
            'captcha_id' => $captchaId,
            'solution' => $solution,
        ]);
    }

    private function retrieveSolution(string $apiKey, string $captchaId): ?string
    {
        $maxAttempts = 12;
        $delaySeconds = 5;

        for ($i = 0; $i < $maxAttempts; $i++) {
            sleep($delaySeconds);

            $resultResponse = Http::asForm()->get('https://2captcha.com/res.php', [
                'key' => $apiKey,
                'action' => 'get',
                'id' => $captchaId,
                'json' => 1,
            ]);

            if (!$resultResponse->successful()) {
                continue;
            }

            $resultPayload = $resultResponse->json();
            if (!is_array($resultPayload)) {
                continue;
            }

            if (($resultPayload['status'] ?? 0) === 1) {
                return (string) $resultPayload['request'];
            }

            $error = $resultPayload['request'] ?? '';
            if ($error !== 'CAPCHA_NOT_READY') {
                break;
            }
        }

        return null;
    }
}
