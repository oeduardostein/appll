<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

if (!function_exists('format_plate_as_mercosul')) {
    require_once __DIR__ . '/../../Support/helpers.php';
}

class NormalizePlateMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $skipPlateNormalization = $this->shouldSkipPlateNormalization($request);
        $normalizedQuery = $this->normalizeArray($request->query->all(), $skipPlateNormalization);
        $request->query->replace($normalizedQuery);

        $normalizedBody = $this->normalizeArray($request->request->all(), $skipPlateNormalization);
        $request->request->replace($normalizedBody);

        return $next($request);
    }

    private function normalizeArray(array $data, bool $skipPlateNormalization): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->normalizeArray($value, $skipPlateNormalization);
                continue;
            }

            if (! $skipPlateNormalization && $this->isPlateKey($key)) {
                $data[$key] = format_plate_as_mercosul($value);
                continue;
            }

            $data[$key] = $value;
        }

        return $data;
    }

    private function isPlateKey(string $key): bool
    {
        $lower = strtolower($key);
        return in_array($lower, ['placa', 'plate'], true);
    }

    private function shouldSkipPlateNormalization(Request $request): bool
    {
        return $request->is('api/emissao-atpv');
    }
}
