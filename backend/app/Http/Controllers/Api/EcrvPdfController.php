<?php

namespace App\Http\Controllers\Api;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class EcrvPdfController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $placa = strtoupper(trim((string) $request->input('placa', '')));
        $numeroFicha = trim((string) $request->input('numeroFicha', ''));
        $anoFicha = trim((string) $request->input('anoFicha', ''));
        $fichaPayload = $request->input('fichaPayload');
        $andamentoPayload = $request->input('andamentoPayload');

        if (!is_array($fichaPayload) || !is_array($andamentoPayload)) {
            return response()->json(
                ['message' => 'Payloads da ficha e do andamento são obrigatórios.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $andamentoNormalized = is_array($andamentoPayload['payload']['normalized'] ?? null)
            ? $andamentoPayload['payload']['normalized']
            : [];
        $andamentoInfo = is_array($andamentoNormalized['andamento_do_processo'] ?? null)
            ? $andamentoNormalized['andamento_do_processo']
            : [];
        $datasInfo = is_array($andamentoNormalized['datas'] ?? null)
            ? $andamentoNormalized['datas']
            : [];
        $anexosInfo = is_array($andamentoNormalized['documentos_anexos'] ?? null)
            ? $andamentoNormalized['documentos_anexos']
            : [];

        $status = trim((string) ($andamentoInfo['status_registro'] ?? $andamentoInfo['status'] ?? ''));
        $retorno = trim((string) ($andamentoInfo['retorno_consistencia'] ?? ''));

        $filenameSeed = sprintf('%s_%s_%s', $placa, $numeroFicha, $anoFicha);
        $sanitized = preg_replace('/[^A-Za-z0-9]/', '', $filenameSeed);
        $filename = sprintf(
            'processo_ecrv_%s.pdf',
            $sanitized !== '' ? $sanitized : 'consulta'
        );

        $generatedAt = now()->format('d/m/Y - H:i:s');

        $docFichaData = is_array($fichaPayload['payload']['normalized']['dados_da_ficha_cadastral'] ?? null)
            ? $fichaPayload['payload']['normalized']['dados_da_ficha_cadastral']
            : [];

        $pdf = Pdf::loadView('pdf.ecrv', [
            'placa' => $placa,
            'numeroFicha' => $numeroFicha,
            'anoFicha' => $anoFicha,
            'status' => $status ?: null,
            'retornoConsistencia' => $retorno ?: null,
            'fichaData' => $docFichaData,
            'andamentoInfo' => $andamentoInfo,
            'datasInfo' => $datasInfo,
            'anexosInfo' => $anexosInfo,
            'generatedAt' => $generatedAt,
            'responsePayload' => [
                'ficha' => $fichaPayload,
                'andamento' => $andamentoPayload,
            ],
        ])->setPaper('a4');

        return $pdf->download($filename);
    }
}
