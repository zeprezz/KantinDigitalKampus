<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // POST /auth/register (publik): customer mendaftarkan akunnya sendiri
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), Customer::validationRules());

        if ($validator->fails()) {
            return ApiResponse::error($request, 'Validasi gagal', 400, $validator->errors()->toArray());
        }

        $customer = Customer::register($request->only((new Customer)->getFillable()));

        return ApiResponse::success($request, 'Registrasi berhasil. Silakan login dengan Basic Auth', $customer->toArray(), 201);
    }

    // GET /auth/me (Basic Auth): cek login, menampilkan data customer yang sedang login
    public function me(Request $request)
    {
        return ApiResponse::success($request, 'Login berhasil', $request->user()->toArray());
    }
}
