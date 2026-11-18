<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'customer_id',
        'menu_name',
        'quantity',
        'total_price',
        'delivery_location',
        'notes',
        'order_status',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'quantity' => 'integer',
        'total_price' => 'integer',
    ];

    // Relasi: setiap order dimiliki satu customer (belongs_to)
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    // Format waktu created_at dan updated_at pada response: 05-11-2018 08:40:30
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d-m-Y H:i:s');
    }
}
