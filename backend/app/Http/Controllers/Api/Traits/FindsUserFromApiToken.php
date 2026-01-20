<?php

namespace App\Http\Controllers\Api\Traits;

use App\Models\User;
use Illuminate\Http\Request;

trait FindsUserFromApiToken
{
    protected function findUserFromRequest(Request $request, array $with = []): ?User
    {
        $token = $this->extractTokenFromRequest($request);

        if (! $token) {
            return null;
        }

        return User::findByApiToken($token, $with);
    }

    protected function extractTokenFromRequest(Request $request): ?string
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
}
