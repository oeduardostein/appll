<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

abstract class BaseAtpvController extends Controller
{
    protected function findUserFromRequest(Request $request): ?User
    {
        $token = $this->extractTokenFromRequest($request);

        if (! $token) {
            return null;
        }

        return User::where('api_token', hash('sha256', $token))->first();
    }

    private function extractTokenFromRequest(Request $request): ?string
    {
        $authHeader = $request->header('Authorization');

        if (is_string($authHeader) && str_starts_with($authHeader, 'Bearer ')) {
            $token = trim(substr($authHeader, 7));
            if ($token !== '') {
                return $token;
            }
        }

        $token = $request->input('token');
        if (is_string($token) && $token !== '') {
            return $token;
        }

        return null;
    }

    protected function unauthorizedResponse(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Não autenticado.',
        ], 401);
    }

    /**
     * @return string[]
     */
    protected function extractErrors(string $html): array
    {
        $html = $this->normalizeEncoding($html);

        $errors = [];
        if (preg_match_all('/errors\[errors\.length\]\s*=\s*[\'"]([^\'"]+)[\'"]\s*;?/u', $html, $matches)) {
            foreach ($matches[1] as $message) {
                $errors[] = html_entity_decode($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        return $errors;
    }

    /**
     * @return string[]
     */
    protected function extractMessages(string $html): array
    {
        $html = $this->normalizeEncoding($html);

        $messages = [];
        if (preg_match_all('/messages\[messages\.length\]\s*=\s*[\'"]([^\'"]+)[\'"]\s*;?/u', $html, $matches)) {
            foreach ($matches[1] as $message) {
                $messages[] = html_entity_decode($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        if (empty($messages) && preg_match_all('/messages\[\d+\]\s*=\s*[\'"]([^\'"]+)[\'"]/u', $html, $matches)) {
            foreach ($matches[1] as $message) {
                $messages[] = html_entity_decode($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        return $messages;
    }

    protected function normalizeEncoding(string $html): string
    {
        if (stripos($html, 'charset=iso-8859-1') !== false || stripos($html, 'charset=iso8859-1') !== false) {
            $html = @mb_convert_encoding($html, 'UTF-8', 'ISO-8859-1');
        }

        return $html;
    }
}
