<?php

namespace App\Http\Controllers\Api;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class RenainfPdfController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $payload = $request->input('payload');
        $meta = $request->input('meta', []);

        if (!is_array($payload)) {
            return response()->json([
                'message' => 'Payload inválido para gerar o PDF.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $plate = trim((string) ($meta['plate'] ?? $payload['plate'] ?? ''));
        $sanitized = preg_replace('/[^A-Za-z0-9]/', '', $plate);
        $filename = sprintf(
            'pesquisa_renainf_%s.pdf',
            $sanitized !== '' ? $sanitized : 'consulta'
        );

        $pdf = Pdf::loadView('pdf.renainf', [
            'payload' => $payload,
            'meta' => is_array($meta) ? $meta : [],
            'generatedAt' => now()->format('d/m/Y - H:i:s'),
        ])->setPaper('a4');

        return $pdf->download($filename);
    }
}
