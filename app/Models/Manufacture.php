<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Manufacture extends Model
{
    //
    protected $guarded = ['id', 'created_at'];

    public function purchases()
    {
        return $this->morphMany(Purchase::class, 'purchaseable');
    }


    // public function products()
    // {
    //     Aqui especificamos la tabla intermedia
    //     return $this->belongsToMany(Product::class, 'manufacture_product')->withPivot('cost', 'capacity');
    // }

    public function products()
    {
        //Aqui no la especificamos porque los modelos Manufacture y Product sigue la convención de Laravel
        return $this->belongsToMany(Product::class)
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function manufactureVariants()
    {
        return $this->hasMany(ManufactureVariant::class);
    }

    public function variants()
    {
        //Aqui no la especificamos porque los modelos Manufacture y Variant sigue la convención de Laravel
        return $this->belongsToMany(Variant::class)
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments(){
        return $this->morphMany(Payment::class, 'paymentable');
    }
}
