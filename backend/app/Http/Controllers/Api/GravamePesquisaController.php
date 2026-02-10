<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class GravamePesquisaController extends Controller
{
    public function __invoke(Request $request): SymfonyResponse
    {
        $placa = strtoupper((string) $request->query('placa', ''));
        $chassi = strtoupper((string) $request->query('chassi', ''));
        $captcha = strtoupper((string) $request->query('captcha', ''));

        $placa = preg_replace('/[^A-Za-z0-9]/', '', $placa) ?? '';
        $chassi = preg_replace('/[^A-Za-z0-9]/', '', $chassi) ?? '';
        $captcha = preg_replace('/[^A-Za-z0-9]/', '', $captcha) ?? '';

        if (($placa === '' && $chassi === '') || $captcha === '') {
            return response()->json(
                ['message' => 'Informe placa ou chassi e captcha para realizar a consulta.'],
                SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ($placa !== '') {
            $chassi = '';
        } else {
            $placa = '';
        }

        $result = $this->queryBaseEstadual($placa, '', $chassi, $captcha);
        $origin = 'base_estadual';

        if (isset($result['error'])) {
            return response()->json(
                ['message' => $result['error']],
                $result['status'] ?? SymfonyResponse::HTTP_BAD_GATEWAY
            );
        }

        $payload = $result['data'] ?? [];
        $gravames = $payload['gravames'] ?? null;
        $gravamesDatas = [];
        if (is_array($gravames) && isset($gravames['datas']) && is_array($gravames['datas'])) {
            $gravamesDatas = $gravames['datas'];
        }

        $responsePayload = [
            'origin' => $origin,
            'fonte' => $payload['fonte'] ?? null,
            'veiculo' => $payload['veiculo'] ?? null,
            'gravames' => $gravames,
            'gravames_datas' => $gravamesDatas,
            'intencao_gravame' => $payload['intencao_gravame'] ?? null,
        ];

        return response()->json(
            $responsePayload,
            SymfonyResponse::HTTP_OK,
            [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function queryBaseEstadual(string $placa, string $renavam, string $chassi, string $captcha): array
    {
        $proxyRequest = Request::create(
            '/api/base-estadual',
            'GET',
            [
                'placa' => $placa,
                'renavam' => $renavam,
                'chassi' => $chassi,
                'captcha' => $captcha,
            ]
        );

        /** @var SymfonyResponse $response */
        $response = app(BaseEstadualController::class)($proxyRequest);

        if ($response->getStatusCode() !== SymfonyResponse::HTTP_OK) {
            return [
                'error' => $this->extractErrorMessage($response),
                'status' => $response->getStatusCode(),
            ];
        }

        $content = json_decode((string) $response->getContent(), true);
        if (!is_array($content)) {
            return [
                'error' => 'Resposta da base estadual em formato inválido.',
                'status' => SymfonyResponse::HTTP_BAD_GATEWAY,
            ];
        }

        return ['data' => $content];
    }

    private function extractErrorMessage(SymfonyResponse $response): string
    {
        $content = (string) $response->getContent();
        $decoded = json_decode($content, true);
        if (is_array($decoded) && isset($decoded['message'])) {
            return (string) $decoded['message'];
        }

        return $content !== '' ? $content : 'Falha ao consultar serviço externo.';
    }
}
