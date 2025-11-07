<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class GravamePesquisaController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $placa = strtoupper($request->query('placa', ''));
        $captcha = strtoupper($request->query('captcha', ''));

        if ($placa === '' || $captcha === '') {
            return response()->json(
                ['message' => 'Informe placa e captcha para realizar a consulta.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $result = $this->queryBaseEstadual($placa, '', $captcha);
        $origin = 'base_estadual';

        if (isset($result['error'])) {
            return response()->json(
                ['message' => $result['error']],
                $result['status'] ?? Response::HTTP_BAD_GATEWAY
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
            Response::HTTP_OK,
            [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function queryBaseEstadual(string $placa, string $renavam, string $captcha): array
    {
        $proxyRequest = Request::create(
            '/api/base-estadual',
            'GET',
            [
                'placa' => $placa,
                'renavam' => $renavam,
                'captcha' => $captcha,
            ]
        );

        /** @var \Illuminate\Http\Response $response */
        $response = app(BaseEstadualController::class)($proxyRequest);

        if ($response->getStatusCode() !== Response::HTTP_OK) {
            return [
                'error' => $this->extractErrorMessage($response),
                'status' => $response->getStatusCode(),
            ];
        }

        $content = json_decode((string) $response->getContent(), true);
        if (!is_array($content)) {
            return [
                'error' => 'Resposta da base estadual em formato inválido.',
                'status' => Response::HTTP_BAD_GATEWAY,
            ];
        }

        return ['data' => $content];
    }

    private function extractErrorMessage(\Illuminate\Http\Response $response): string
    {
        $content = (string) $response->getContent();
        $decoded = json_decode($content, true);
        if (is_array($decoded) && isset($decoded['message'])) {
            return (string) $decoded['message'];
        }

        return $content !== '' ? $content : 'Falha ao consultar serviço externo.';
    }
}
