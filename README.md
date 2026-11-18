# Kantin Digital Kampus (REST API)

UTS Interoperability. REST API pemesanan makanan kantin kampus, dibuat dengan PHP Lumen.
Customer mendaftar, login, lalu memesan makanan dan menentukan lokasi pengantaran di sekitar kampus.

## Tabel

- `customers` (tabel utama, 14 kolom): id, username, password, nim, name, email, phone, major, gender, default_address, favorite_location, status, created_at, updated_at
- `orders` (tabel kedua, 10 kolom): id, customer_id, menu_name, quantity, total_price, delivery_location, notes, order_status, created_at, updated_at
- Relasi: `customers` has_many `orders` (`orders.customer_id` -> `customers.id`)

## Endpoint

| No | Method | Endpoint | Keterangan | Login |
|----|--------|----------|------------|-------|
| 1 | GET | /customers | Get list of all customers | - |
| 2 | GET | /customers/{customerId} | Get single customer (beserta orders) | - |
| 3 | POST | /customers | Create new customer | Ya |
| 4 | PUT | /customers/{customerId} | Update customer (akun sendiri) | Ya |
| 5 | DELETE | /customers/{customerId} | Delete customer (akun sendiri) | Ya |
| 6 | GET | /orders | Get all orders, atau filter `?customer_id=1` | - |
| 7 | GET | /orders/{orderId} | Get single order | - |
| 8 | POST | /orders | Create new order (milik customer yang login) | Ya |
| 9 | PUT | /orders/{orderId} | Update order (milik sendiri) | Ya |
| 10 | DELETE | /orders/{orderId} | Delete order (milik sendiri) | Ya |
| 11 | POST | /auth/register | Register akun customer baru | - |
| 12 | GET | /auth/me | Cek login (data customer yang login) | Ya |

## Autentikasi

- Endpoint yang mengubah database (POST, PUT, DELETE) wajib login dengan **Basic Auth** memakai username dan password milik customer sendiri (tabel `customers`).
- Customer baru mendaftar lewat `POST /auth/register` (publik). Password disimpan dalam bentuk hash bcrypt dan tidak pernah tampil di response.
- Order yang dibuat otomatis milik customer yang login (`customer_id` diambil dari login, tidak dikirim di body).
- Customer hanya boleh mengubah atau menghapus akun dan order miliknya sendiri. Jika bukan miliknya dibalas 403.
- Tanpa login, password salah, atau username tidak terdaftar dibalas 401.

## Aturan response

- Format response (JSON atau XML) ditentukan header `Accept` (`application/json` atau `application/xml`). Berlaku untuk semua endpoint, termasuk response error. Jika `Accept` tidak dikirim, default JSON. Accept lain dibalas 406.
- POST dan PUT wajib `Content-Type: application/json` (selain itu 415).
- `created_at` dan `updated_at` ditampilkan dengan format `d-m-Y H:i:s`, contoh `05-11-2018 08:40:30`.
- Validasi gagal dibalas 400 Bad Request, data tidak ada dibalas 404.

## Cara menjalankan

1. `composer install`
2. Salin `.env.example` menjadi `.env`, lalu sesuaikan setting database (default: database `kantin_digital`, user `root`, password kosong).
3. Buat database `kantin_digital` di MySQL (phpMyAdmin).
4. `php artisan migrate` (jika sebelumnya sudah pernah migrate versi lama, pakai `php artisan migrate:fresh`)
5. `php -S localhost:8000 -t public`

## Postman

Import `postman/KantinDigitalApp.postman_collection.json` (Ctrl+O di Postman). Berisi semua endpoint (JSON dan XML, login customer, filter, negative case). Akun login ada di Variables collection (`username1`, `password1`, dst). Jalankan berurutan pada database kosong karena ada ketergantungan id.

## Struktur folder

- `app/Models` : Customer, Order
- `app/Http/Controllers` : AuthController, CustomersController, OrdersController
- `app/Http/Middleware` : ContentNegotiationMiddleware, BasicAuthMiddleware
- `app/Http/Responses` : ApiResponse (pembuat response JSON/XML)
- `app/Exceptions/Handler.php` : error handling dalam format JSON/XML
- `database/migrations` : create_customers_table, create_orders_table
- `routes/web.php` : routing
