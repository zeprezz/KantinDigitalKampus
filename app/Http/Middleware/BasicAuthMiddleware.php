<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Models\Customer;
use Closure;
use Illuminate\Support\Facades\Hash;

/**
 * Basic Auth untuk endpoint yang mengubah database (POST, PUT, DELETE).
 * Login memakai username dan password milik customer sendiri (tabel customers).
 * Jika berhasil, customer yang login bisa diambil lewat $request->user().
 */
class BasicAuthMiddleware
{
    public function handle($request, Closure $next)
    {
        [$username, $password] = $this->credentials($request);

        $customer = $username !== null
            ? Customer::where('username', $username)->first()
            : null;

        if (!$customer || !Hash::check((string) $password, $customer->password)) {
            return ApiResponse::error(
                $request,
                'Unauthorized. Endpoint ini membutuhkan Basic Auth dengan username dan password customer yang benar',
                401,
                null,
                ['WWW-Authenticate' => 'Basic realm="Kantin Digital Kampus"']
            );
        }

        // Simpan customer yang sedang login
        $request->setUserResolver(function () use ($customer) {
            return $customer;
        });

        return $next($request);
    }

    private function credentials($request): array
    {
        if ($request->getUser() !== null) {
            return [$request->getUser(), $request->getPassword()];
        }

        // Fallback: baca langsung dari header Authorization: Basic xxxxx
        $header = (string) $request->header('Authorization', '');

        if (stripos($header, 'Basic ') === 0) {
            $decoded = base64_decode(substr($header, 6), true);

            if ($decoded !== false && strpos($decoded, ':') !== false) {
                return explode(':', $decoded, 2);
            }
        }

        return [null, null];
    }
}
