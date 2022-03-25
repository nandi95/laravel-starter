<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class VerifyCsrfToken extends Middleware
{
    /**
     * Add the CSRF token to the response cookies.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    public function addCookieToResponse($request, $response): Response
    {
        $response = parent::addCookieToResponse($request, $response);

        if ($request->routeIs('api.auth.logout')) {
            /** @var Cookie $cookie */
            $cookie = collect($response->headers->getCookies())
                ->first(fn (Cookie $cookie) => $cookie->getName() === 'XSRF-TOKEN')
                ->withExpires(time() - 1);

            // unset the cookie in the browser
            $response->headers->setCookie($cookie);
        }

        return $response;
    }
}
