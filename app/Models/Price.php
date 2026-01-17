<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    //
    const ETIQUETA = 'compare_at';
    const OFERTA = 'final';
    const WHOLESALER = 'wholesaler';
    const LIVE = 'live';
    const BLACKFRIDAY = 'blackfriday';
    const FERIA = 'feria';


    const DEFAULT_OPTIONS = [

        [
            'name' => PRICE::OFERTA,
            'label' => 'Final',
        ],
        [
            'name' => PRICE::ETIQUETA,
            'label' => 'Etiqueta',
        ],
        [
            'name' => PRICE::WHOLESALER,
            'label' => 'Mayorista',
        ],
        [
            'name' => PRICE::LIVE,
            'label' => 'Live TikTok',
        ],
        [
            'name' => PRICE::BLACKFRIDAY,
            'label' => 'BlackFriday',
        ],
        [
            'name' => PRICE::FERIA,
            'label' => 'Feria',
        ],

    ];

    protected $guarded = ['id', 'created_at'];

    protected $casts = [
        'value' => 'decimal:2', //Queire decir que cuando se solicite ese campo se tratara como decimal:2, o tambien "Cuando lea o escriba este campo, trátalo como este tipo"
    ];

    /* --------------------------------------------
     | Relaciones
     |---------------------------------------------*/
    public function priceable()
    {
        return $this->morphTo();
    }

    /* --------------------------------------------
     | Helpers de dominio
     |---------------------------------------------*/

    public function isBase(): bool
    {
        return $this->type === 'etiqueta';
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
