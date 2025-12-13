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

            $content = str_replace('src="/flux/flux.js', 'src="/flux/flux.js', $content);
            $content = str_replace('href="/flux/flux.css', 'href="/flux/flux.css', $content);
            
            $response->setContent($content);
        }

        return $response;
    }
}
