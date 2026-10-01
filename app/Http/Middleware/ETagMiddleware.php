<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ETagMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only handle GET and HEAD requests and successful responses
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            if ($response->isSuccessful()) {
                $content = $response->getContent();
                // Generate ETag based on response content
                $etag = '"'.md5($content).'"';

                $response->setEtag($etag);

                // If client sends If-None-Match header matching the ETag, return 304 Not Modified
                $requestEtag = str_replace('-gzip', '', $request->header('If-None-Match', ''));

                if ($requestEtag && $requestEtag === $etag) {
                    $response->setNotModified();
                }
            }
        }

        return $response;
    }
}
