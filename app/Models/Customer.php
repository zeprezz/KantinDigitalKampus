<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class Customer extends Model
{
    protected $table = 'customers';

    protected $fillable = [
        'username',
        'password',
        'nim',
        'name',
        'email',
        'phone',
        'major',
        'gender',
        'default_address',
        'favorite_location',
        'status',
    ];

    // Password tidak pernah ditampilkan di response
    protected $hidden = ['password'];

    // Relasi: satu customer punya banyak order (has_many)
    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    // Format waktu created_at dan updated_at pada response: 05-11-2018 08:40:30
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d-m-Y H:i:s');
    }

    // Membuat customer baru, password disimpan dalam bentuk hash (bcrypt)
    public static function register(array $data): self
    {
        $data['password'] = Hash::make($data['password']);

        return static::create($data)->refresh();
    }

    // Rule validasi. Saat update ($ignoreId diisi), field boleh dikirim sebagian (sometimes).
    public static function validationRules($ignoreId = null): array
    {
        $required = $ignoreId ? ['sometimes', 'required'] : ['required'];

        return [
            'username' => array_merge($required, ['alpha_dash', 'min:4', 'max:50', Rule::unique('customers', 'username')->ignore($ignoreId)]),
            'password' => array_merge($required, ['string', 'min:6', 'max:100']),
            'nim' => array_merge($required, ['string', 'max:20', Rule::unique('customers', 'nim')->ignore($ignoreId)]),
            'name' => array_merge($required, ['string', 'min:3', 'max:100']),
            'email' => array_merge($required, ['email', 'max:100', Rule::unique('customers', 'email')->ignore($ignoreId)]),
            'phone' => array_merge($required, ['regex:/^[0-9+]{8,20}$/']),
            'major' => array_merge($required, ['string', 'max:100']),
            'gender' => array_merge($required, [Rule::in(['Laki-laki', 'Perempuan'])]),
            'default_address' => array_merge($required, ['string', 'max:255']),
            'favorite_location' => array_merge($required, ['string', 'max:100']),
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
    }
}
