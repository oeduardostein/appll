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
        $normalizedQuery = $this->normalizeArray($request->query->all());
        $request->query->replace($normalizedQuery);

        $normalizedBody = $this->normalizeArray($request->request->all());
        $request->request->replace($normalizedBody);

        return $next($request);
    }

    private function normalizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->normalizeArray($value);
                continue;
            }

            if ($this->isPlateKey($key)) {
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
}
