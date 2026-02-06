<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    //

    protected $guarded = ['id', 'created_at'];

    protected $hidden = [
        'paymentable_type',
        'paymentable_id',
    ];

    const STATUS = [
        [
            'id' => 1,
            'name' => 'unpaid',
            'title' => 'Pago no realizado',
        ],
        [
            'id' => 2,
            'name' => 'pending',
            'title' => 'Pendiente de pago',

        ],
        [
            'id' => 3,
            'name' => 'failed',
            'title' => 'Error al hacer el pago',
        ],
        [
            'id' => 4,
            'name' => 'expired',
            'title' => 'Expirado',
        ],
        [
            'id' => 5,
            'name' => 'paid',
            'title' => 'Pagado',
        ],
        [
            'id' => 6,
            'name' => 'refunding',
            'title' => 'En proceso de reembolso',
        ],
        [
            'id' => 7,
            'name' => 'refunded',
            'title' => 'Reembolsado',
        ],
    ];

    const METHODS = [
        [
            'id' => 1,
            'name' => 'cash',
            'title' => 'Efectivo',
        ],
        [
            'id' => 2,
            'name' => 'yape',
            'title' => 'Yape',
        ],
        [
            'id' => 3,
            'name' => 'plin',
            'title' => 'Plin',
        ],
        [
            'id' => 4,
            'name' => 'credit_card',
            'title' => 'Tarjeta de crédito',
        ],
        [
            'id' => 5,
            'name' => 'bank_transfer',
            'title' => 'Transferencia bancaria',
        ],
        [
            'id' => 6,
            'name' => 'paypal',
            'title' => 'PayPal',
        ],
    ];

    public function paymentable()
    {
        return $this->morphTo();
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    // public function getStatusAttribute()
    // {
    //     return Payment::STATUS;
    // }

    // public function getMethodsAttribute()
    // {
    //     return Payment::METHODS;
    // }
}
