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
        $renavam = trim($request->query('renavam', ''));
        $uf = strtoupper($request->query('uf', ''));
        $captcha = strtoupper($request->query('captcha', ''));

        if ($placa === '' || $renavam === '' || $uf === '' || $captcha === '') {
            return response()->json(
                ['message' => 'Informe placa, renavam, UF e captcha para realizar a consulta.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ($uf === 'SP') {
            $result = $this->queryBaseEstadual($placa, $renavam, $captcha);
            $origin = 'base_estadual';
        } else {
            $result = $this->queryAnotherBase($placa, $renavam, $uf, $captcha);
            $origin = 'another_base_estadual';
        }

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

    /**
     * @return array<string, mixed>
     */
    private function queryAnotherBase(
        string $placa,
        string $renavam,
        string $uf,
        string $captcha
    ): array {
        $binData = $this->queryBin($placa, $renavam, $captcha);

        if (isset($binData['error'])) {
            return $binData;
        }

        $binPayload = $binData['data'] ?? [];
        $chassi = $binPayload['normalized']['identificacao_do_veiculo_na_bin']['chassi'] ?? null;

        if (!$chassi || !is_string($chassi)) {
            return [
                'error' => 'Não foi possível identificar o chassi do veículo para consultar outros estados.',
                'status' => Response::HTTP_BAD_GATEWAY,
            ];
        }

        $proxyRequest = Request::create(
            '/api/another-base-estadual',
            'GET',
            [
                'chassi' => strtoupper($chassi),
                'uf' => strtoupper($uf),
                'captcha' => $captcha,
            ]
        );

        /** @var \Illuminate\Http\Response $response */
        $response = app(AnotherBaseStateController::class)($proxyRequest);

        if ($response->getStatusCode() !== Response::HTTP_OK) {
            return [
                'error' => $this->extractErrorMessage($response),
                'status' => $response->getStatusCode(),
            ];
        }

        $content = json_decode((string) $response->getContent(), true);
        if (!is_array($content)) {
            return [
                'error' => 'Resposta da base de outros estados em formato inválido.',
                'status' => Response::HTTP_BAD_GATEWAY,
            ];
        }

        return ['data' => $content];
    }

    /**
     * @return array<string, mixed>
     */
    private function queryBin(string $placa, string $renavam, string $captcha): array
    {
        $proxyRequest = Request::create(
            '/api/bin',
            'GET',
            [
                'placa' => $placa,
                'renavam' => $renavam,
                'captcha' => $captcha,
            ]
        );

        /** @var \Illuminate\Http\Response $response */
        $response = app(BinController::class)($proxyRequest);

        if ($response->getStatusCode() !== Response::HTTP_OK) {
            return [
                'error' => $this->extractErrorMessage($response),
                'status' => $response->getStatusCode(),
            ];
        }

        $content = json_decode((string) $response->getContent(), true);
        if (!is_array($content)) {
            return [
                'error' => 'Resposta do BIN em formato inválido.',
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
