<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrdersController extends Controller
{
    // GET /orders                 -> semua order
    // GET /orders?customer_id=1   -> order milik customer tertentu (filter by customer_id)
    public function index(Request $request)
    {
        $query = Order::orderBy('id');

        if ($request->has('customer_id')) {
            $customerId = $request->query('customer_id');

            if (!ctype_digit((string) $customerId)) {
                return ApiResponse::error($request, 'Parameter customer_id harus berupa angka', 400);
            }

            if (!Customer::find($customerId)) {
                return ApiResponse::error($request, 'Customer dengan id ' . $customerId . ' tidak ditemukan', 404);
            }

            $query->where('customer_id', $customerId);
        }

        return ApiResponse::success($request, 'Daftar order', $query->get()->toArray(), 200, 'order');
    }

    // GET /orders/{orderId}
    public function show(Request $request, $orderId)
    {
        $order = Order::find($orderId);

        if (!$order) {
            return ApiResponse::error($request, 'Order dengan id ' . $orderId . ' tidak ditemukan', 404);
        }

        return ApiResponse::success($request, 'Detail order', $order->toArray());
    }

    // POST /orders (harus login). Order otomatis milik customer yang sedang login,
    // jadi customer_id tidak dikirim lewat body.
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return ApiResponse::error($request, 'Validasi gagal', 400, $validator->errors()->toArray());
        }

        $data = $request->only([
            'menu_name', 'quantity', 'total_price', 'delivery_location', 'notes', 'order_status',
        ]);
        $data['customer_id'] = $request->user()->id;

        $order = Order::create($data);
        $order->refresh();

        return ApiResponse::success($request, 'Order berhasil dibuat', $order->toArray(), 201);
    }

    // PUT /orders/{orderId} (harus login, hanya boleh mengubah order milik sendiri)
    public function update(Request $request, $orderId)
    {
        $order = Order::find($orderId);

        if (!$order) {
            return ApiResponse::error($request, 'Order dengan id ' . $orderId . ' tidak ditemukan', 404);
        }

        if ($order->customer_id !== $request->user()->id) {
            return ApiResponse::error($request, 'Forbidden. Anda hanya boleh mengubah order milik sendiri', 403);
        }

        $validator = Validator::make($request->all(), $this->rules(true));

        if ($validator->fails()) {
            return ApiResponse::error($request, 'Validasi gagal', 400, $validator->errors()->toArray());
        }

        $order->update($request->only([
            'menu_name', 'quantity', 'total_price', 'delivery_location', 'notes', 'order_status',
        ]));

        return ApiResponse::success($request, 'Order berhasil diupdate', $order->fresh()->toArray());
    }

    // DELETE /orders/{orderId} (harus login, hanya boleh menghapus order milik sendiri)
    public function destroy(Request $request, $orderId)
    {
        $order = Order::find($orderId);

        if (!$order) {
            return ApiResponse::error($request, 'Order dengan id ' . $orderId . ' tidak ditemukan', 404);
        }

        if ($order->customer_id !== $request->user()->id) {
            return ApiResponse::error($request, 'Forbidden. Anda hanya boleh menghapus order milik sendiri', 403);
        }

        $order->delete();

        return ApiResponse::success($request, 'Order berhasil dihapus');
    }

    // Rule validasi. Saat update ($isUpdate = true), field boleh dikirim sebagian (sometimes).
    private function rules(bool $isUpdate = false): array
    {
        $required = $isUpdate ? ['sometimes', 'required'] : ['required'];

        return [
            'menu_name' => array_merge($required, ['string', 'min:3', 'max:100']),
            'quantity' => array_merge($required, ['integer', 'min:1']),
            'total_price' => array_merge($required, ['integer', 'min:0']),
            'delivery_location' => array_merge($required, ['string', 'max:150']),
            'notes' => ['nullable', 'string', 'max:255'],
            'order_status' => ['sometimes', Rule::in(['pending', 'processing', 'delivered', 'cancelled'])],
        ];
    }
}
