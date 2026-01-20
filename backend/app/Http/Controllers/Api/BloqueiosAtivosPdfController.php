<?php

namespace App\Http\Controllers\Api;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class BloqueiosAtivosPdfController extends Controller
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

        $origin = strtoupper(trim((string) ($request->input('origin', 'DETRAN'))));
        $chassi = trim((string) $request->input('chassi', ''));
        $consulta = is_array($payload['consulta'] ?? null) ? $payload['consulta'] : [];
        $plate = trim((string) ($consulta['placa'] ?? ''));
        $filenameSeed = $plate ?: $chassi ?: $origin ?: 'consulta';
        $sanitized = preg_replace('/[^A-Za-z0-9]/', '', $filenameSeed);
        $filename = sprintf(
            'pesquisa_bloqueios_%s.pdf',
            $sanitized !== '' ? $sanitized : 'consulta'
        );

        $generatedAt = now()->format('d/m/Y - H:i:s');

        $pdf = Pdf::loadView('pdf.bloqueios', [
            'payload' => $payload,
            'origin' => $origin,
            'generatedAt' => $generatedAt,
        ])->setPaper('a4');

        return $pdf->download($filename);
    }
}
