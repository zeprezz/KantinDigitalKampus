<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CustomersController extends Controller
{
    // GET /customers
    public function index(Request $request)
    {
        $customers = Customer::orderBy('id')->get();

        return ApiResponse::success($request, 'Daftar customer', $customers->toArray(), 200, 'customer');
    }

    // GET /customers/{customerId}
    public function show(Request $request, $customerId)
    {
        $customer = Customer::with('orders')->find($customerId);

        if (!$customer) {
            return ApiResponse::error($request, 'Customer dengan id ' . $customerId . ' tidak ditemukan', 404);
        }

        return ApiResponse::success($request, 'Detail customer', $customer->toArray());
    }

    // POST /customers (harus login sebagai customer)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), Customer::validationRules());

        if ($validator->fails()) {
            return ApiResponse::error($request, 'Validasi gagal', 400, $validator->errors()->toArray());
        }

        $customer = Customer::register($request->only((new Customer)->getFillable()));

        return ApiResponse::success($request, 'Customer berhasil dibuat', $customer->toArray(), 201);
    }

    // PUT /customers/{customerId} (harus login, hanya boleh mengubah akun sendiri)
    public function update(Request $request, $customerId)
    {
        $customer = Customer::find($customerId);

        if (!$customer) {
            return ApiResponse::error($request, 'Customer dengan id ' . $customerId . ' tidak ditemukan', 404);
        }

        if ($request->user()->id !== $customer->id) {
            return ApiResponse::error($request, 'Forbidden. Anda hanya boleh mengubah akun milik sendiri', 403);
        }

        $validator = Validator::make($request->all(), Customer::validationRules($customer->id));

        if ($validator->fails()) {
            return ApiResponse::error($request, 'Validasi gagal', 400, $validator->errors()->toArray());
        }

        $data = $request->only($customer->getFillable());

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $customer->update($data);

        return ApiResponse::success($request, 'Customer berhasil diupdate', $customer->fresh()->toArray());
    }

    // DELETE /customers/{customerId} (harus login, hanya boleh menghapus akun sendiri)
    public function destroy(Request $request, $customerId)
    {
        $customer = Customer::find($customerId);

        if (!$customer) {
            return ApiResponse::error($request, 'Customer dengan id ' . $customerId . ' tidak ditemukan', 404);
        }

        if ($request->user()->id !== $customer->id) {
            return ApiResponse::error($request, 'Forbidden. Anda hanya boleh menghapus akun milik sendiri', 403);
        }

        $customer->delete();

        return ApiResponse::success($request, 'Customer berhasil dihapus');
    }
}
