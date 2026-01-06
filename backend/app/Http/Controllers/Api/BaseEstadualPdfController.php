<?php

namespace App\Http\Controllers\Api;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class BaseEstadualPdfController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $payload = $request->input('payload');

        if (!is_array($payload)) {
            return response()->json(
                ['message' => 'Payload inválido para gerar o PDF.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $veiculo = is_array($payload['veiculo'] ?? null) ? $payload['veiculo'] : [];
        $placa = (string) ($veiculo['placa'] ?? '');
        $sanitized = preg_replace('/[^A-Za-z0-9]/', '', $placa);
        $filename = sprintf(
            'pesquisa_base_estadual_%s.pdf',
            $sanitized !== '' ? $sanitized : 'consulta'
        );

        $pdf = Pdf::loadView('pdf.base-estadual', [
            'payload' => $payload,
            'generatedAt' => now()->format('d/m/Y - H:i:s'),
        ])->setPaper('a4');

        return $pdf->download($filename);
    }
}
