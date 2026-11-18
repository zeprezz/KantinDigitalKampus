<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;

/**
 * Content Negotiation (global middleware, berlaku untuk semua endpoint):
 * 1. Header Accept hanya boleh application/json atau application/xml (selain itu 406).
 *    Header ini menentukan format response. Jika tidak dikirim, default JSON.
 * 2. Untuk POST dan PUT, header Content-Type hanya boleh application/json (selain itu 415).
 */
class ContentNegotiationMiddleware
{
    public function handle($request, Closure $next)
    {
        if (ApiResponse::detect($request) === null) {
            return ApiResponse::error(
                $request,
                'Not Acceptable. Header Accept hanya boleh application/json atau application/xml',
                406
            );
        }

        if (in_array($request->method(), ['POST', 'PUT'])) {
            $contentType = strtolower((string) $request->header('Content-Type', ''));

            if (strpos($contentType, 'application/json') !== 0) {
                return ApiResponse::error(
                    $request,
                    'Unsupported Media Type. Header Content-Type harus application/json',
                    415
                );
            }
        }

        return $next($request);
    }
}
