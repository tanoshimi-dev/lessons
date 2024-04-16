<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Symfony\Component\HttpFoundation\Cookie;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
        '/api/mail/send',
    ];

    protected function newCookie($request, $config)
    {
//        $domain = request()->getHost();

        // if (ShopManagement::where('external_domain', $domain)->where('is_enabled_external_domain', 1)->exists()) {
        //     $domain = $this->getEnableDomain();
        // } else {
        //     $domain = config('session.domain');
        // }

        return new Cookie(
            strtoupper(config('app.env'))
            .'-APP-XSRF-TOKEN',
            $request->session()->token(),
            $this->availableAt(60 * $config['lifetime']),
            $config['path'],
            config('session.domain'),
            $config['secure'],
            false,
            false,
            $config['same_site'] ?? null
        );
    }

}
