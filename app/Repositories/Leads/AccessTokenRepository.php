<?php

namespace App\Repositories\Leads;

use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class AccessTokenRepository
{
    /**
     * Get the basic token from the request headers.
     */
    public function basicToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');

        $position = strrpos($header, 'Basic');

        if ($position !== false) {
            $header = substr($header, $position + 6);

            return str_contains($header, ',') ? strstr($header, ',', true) : $header;
        }

        return null;
    }

    /**
     * Summary of getPersonalAccessToken.
     */
    public function getPersonalAccessToken(string $token): ?PersonalAccessToken
    {
        return PersonalAccessToken::findToken($token);
    }

    /**
     * Summary of findAccessToken.
     */
    public function updatedAccessToken(PersonalAccessToken $personalAccessToken): PersonalAccessToken
    {
        $personalAccessToken->forceFill([
            'last_used_at' => now(),
            'expires_at' => now()->addDays(1),
        ])->save();

        return $personalAccessToken;
    }

    /**
     * Summary of deleteAccessToken.
     */
    public function deleteAccessToken(PersonalAccessToken $personalAccessToken): void
    {
        PersonalAccessToken::where('name', 'api-' . $personalAccessToken->tokenable->email)->delete();
    }

    /**
     * Summary of createAccessToken.
     */
    public function createAccessToken(PersonalAccessToken $personalAccessToken): string
    {
        return $personalAccessToken->tokenable->createToken('api-' . $personalAccessToken->tokenable->email, ['lead.api:read', 'lead.api:create'], now()->addDays(1))->plainTextToken;
    }

    /**
     * Summary of response.
     */
    public function response(string|bool $token = false, bool $success = false): array
    {
        if ($success && $token) {
            return [
                'status' => 'success',
                'message' => __('Logged in successfully.'),
                'token_code' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 86400,
            ];
        }

        return [
            'status' => 'errors',
            'message' => 'The provided credentials are incorrect.',
        ];
    }
}
