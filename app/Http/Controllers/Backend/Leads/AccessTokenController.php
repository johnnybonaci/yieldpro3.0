<?php

namespace App\Http\Controllers\Backend\Leads;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Repositories\Leads\AccessTokenRepository;

class AccessTokenController extends Controller
{
    /**
     * __construct.
     */
    public function __construct(private AccessTokenRepository $access_token_repository)
    {
    }

    /**
     * Summary of token.
     */
    public function token(Request $request): JsonResponse
    {
        $response = $this->access_token_repository->response();
        $code = 401;
        $token = $this->access_token_repository->basicToken($request);
        if ($token) {
            $accessToken = $this->access_token_repository->getPersonalAccessToken($token);

            if ($accessToken) {
                if ($accessToken->can('token:read')) {
                    $this->access_token_repository->deleteAccessToken($accessToken);
                    $newToken = $this->access_token_repository->createAccessToken($accessToken);
                    $response = $this->access_token_repository->response($newToken, true);
                    $code = 200;
                }
            }
        }

        return response()->json($response, $code);
    }
}
