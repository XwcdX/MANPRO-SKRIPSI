<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FixFluxUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->headers->get('content-type') === 'text/html; charset=UTF-8') {
            $content = $response->getContent();

            $correctUrl = 'https://pce.petra.ac.id/flux/flux.js';
            $correctCss = 'https://pce.petra.ac.id/flux/flux.css';

            $content = str_replace('src="/flux/flux.js', 'src="' . $correctUrl, $content);
            $content = str_replace('href="/flux/flux.css', 'href="' . $correctCss, $content);

            $response->setContent($content);
        }

        return $response;
    }
}
