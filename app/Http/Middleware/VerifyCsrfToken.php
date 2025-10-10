<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpFoundation\Cookie;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     */
    protected $except = [];

    /**
     * Add the CSRF token to the response cookies.
     * @param mixed $request
     * @param mixed $response
     */
    protected function addCookieToResponse($request, $response)
    {
        if (!$this->shouldAddXsrfTokenCookie()) {
            return $response;
        }

        $config = config('session');
        $cookieName = $this->getCsrfCookieName();

        $response->headers->setCookie(new Cookie(
            $cookieName,
            $request->session()->token(),
            now()->addMinutes($config['lifetime'] ?? 120),
            $config['path'] ?? '/',
            $config['domain'] ?? null,
            $config['secure'] ?? true,
            false, // HttpOnly = false para JavaScript
            false,
            $config['same_site'] ?? 'lax'
        ));

        return $response;
    }

    /**
     * Get the CSRF cookie name for this application.
     */
    protected function getCsrfCookieName(): string
    {
        return env('XSRF_COOKIE_NAME', 'XSRF-TOKEN');
    }

    /**
     * Determine if the cookie should be added to the response.
     */
    public function shouldAddXsrfTokenCookie(): bool
    {
        return config('sanctum.stateful', []) !== []
            && $this->isReading(request())
            && $this->runningUnitTests() === false;
    }
}
