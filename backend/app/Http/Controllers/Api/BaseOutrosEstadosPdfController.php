<?php

namespace App\Http\Controllers\Api;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class BaseOutrosEstadosPdfController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $payload = $request->input('payload');
        $meta = $request->input('meta', []);

        if (!is_array($payload)) {
            return response()->json(
                ['message' => 'Payload inválido para gerar o PDF.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $veiculo = is_array($payload['veiculo'] ?? null) ? $payload['veiculo'] : [];
        $chassi = (string) ($meta['chassi'] ?? ($veiculo['chassi'] ?? ''));
        $sanitized = preg_replace('/[^A-Za-z0-9]/', '', $chassi);
        $filename = sprintf(
            'pesquisa_base_outros_estados_%s.pdf',
            $sanitized !== '' ? $sanitized : 'consulta'
        );

        $pdf = Pdf::loadView('pdf.base-outros-estados', [
            'payload' => $payload,
            'meta' => is_array($meta) ? $meta : [],
            'generatedAt' => now()->format('d/m/Y - H:i:s'),
        ])->setPaper('a4');

        return $pdf->download($filename);
    }
}
