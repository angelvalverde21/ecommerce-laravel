<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Manufacture extends Model
{
    //
    protected $guarded = ['id', 'created_at'];

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

    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    public function kardexes()
    {
        return $this->morphMany(Kardex::class, 'kardexable')->orderBy('id', 'desc');;
    }

    public function supplier()
    {

        return $this->belongsTo(Supplier::class);
    }

    public function scopeSearch(Builder $query, $term)
    {

        // return $query->where('products.name', 'LIKE', '%' . $term . '%')
        //     ->orWhere('products.tags', 'LIKE', '%' . $term . '%');
        // Esto da como resultado una consulta sin los parentesis
        // WHERE products.name LIKE '%term%' OR products.tags LIKE '%term%'



        //Es mejor usar esta consulta porque encapsula el query por si se concatena con otra consulta, esta no se vera afectara
        // porque el resultado final tendra los parentesis
        // WHERE (products.name LIKE '%term%' OR products.tags LIKE '%term%')

        return $query->where(function ($query) use ($term) {
            $query->where('manufactures.name', 'LIKE', '%' . $term . '%');
            // ->orWhere('products.tags', 'LIKE', '%' . $term . '%');
        });
    }



    public function scopeWithFinancialSummary(Builder $query): Builder
    {
        return $query
            ->select('*')

            // compras
            ->selectSub(function ($q) {
                $q->from('purchase_items')
                    ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
                    ->whereColumn('purchases.purchaseable_id', 'manufactures.id')
                    ->where('purchases.purchaseable_type', self::class)
                    ->selectRaw('COALESCE(SUM(purchase_items.subtotal), 0)');
            }, 'sum_purchases')

            // variantes
            ->withSum('manufactureVariants as sum_variants', 'quantity')
            ->withSum('payments as sum_payments', 'amount')
            ->withCount('payments as count_payments')
            ->withCount('manufactureVariants as count_variants')

            // kardex
            ->selectSub(function ($q) {
                $q->from('kardexes')
                    ->whereColumn('kardexes.kardexable_id', 'manufactures.id')
                    ->where('kardexes.kardexable_type', self::class)
                    ->selectRaw("
                    COALESCE(SUM(
                        CASE 
                            WHEN direction = 'in' THEN quantity
                            WHEN direction = 'out' THEN -quantity
                            ELSE 0
                        END
                    ), 0)
                ");
            }, 'sum_kardexes');
    }

    public function purchases()
    {
        return $this->morphMany(Purchase::class, 'purchaseable');   
    }
}
