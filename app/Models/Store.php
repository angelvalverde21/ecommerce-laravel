<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Store extends Model
{
    /** @use HasFactory<\Database\Factories\StoreFactory> */
    use HasFactory;

    protected $guarded = ['id', 'created_at'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class)->whereNull('parent_id');;
    }

    // Store → User → Courier
    public function couriers()
    {
        // return $this->hasManyThrough( //se traduce como "tiene muchos a traves de" o "tiene muchos mediante"
        //     Courier::class,
        //     User::class,
        //     'store_id',   // FK en users
        //     'user_id',    // FK en couriers
        //     'id',         // PK en stores
        //     'id'          // PK en users
        // );

        /*
            Laravel asume automáticamente:

            users.store_id
            couriers.user_id
            stores.id
            users.id
        */

        return $this->hasManyThrough(Courier::class, User::class); //se traduce como "tiene muchos a traves de" o "tiene muchos mediante"

        //Osea se lee asi: Obtener los couriers (Courier::class) de un Store a través de la tabla users (User::class) asociados a tabla stores

    }

    // public function suppliers()
    // {
    //     return $this->hasManyThrough(Supplier::class, User::class);
    // }

    // public function customers()
    // {
    //     return $this->hasManyThrough(Customer::class, User::class);
    // }

    // public function employees()
    // {
    //     return $this->hasManyThrough(Employee::class, User::class);
    // }

    public function employees()
    {
        return Employee::whereHas('user.stores', function ($q) {
            $q->where('stores.id', $this->id);
        })->with('user');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'store_user');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }
    public function brands()
    {
        return $this->hasMany(Brand::class);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function getOptionsDefaultAttribute()
    {
        return \App\Models\Option::DEFAULT_OPTIONS;
    }

    public function manufactures()
    {
        return $this->hasMany(Manufacture::class);
    }

    public function productions()
    {
        return $this->hasMany(Production::class);
    }

    public function pettyCashes()
    {
        return $this->hasMany(PettyCash::class);
    }

    public function gateways()
    {
        return $this->hasMany(Gateway::class);
    }

    public function suppliers()
    {
        return Supplier::whereHas('user.stores', function ($query) {
            $query->where('stores.id', $this->id);
        });
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    // public function suppliers()
    // {
    //     return $this->hasManyThrough(
    //         Supplier::class,  // Modelo final
    //         User::class,      // Modelo intermedio
    //         'store_id',       // FK en users (relaciona con stores.id)
    //         'user_id',        // FK en suppliers (relaciona con users.id)
    //         'id',             // PK en stores
    //         'id'              // PK en users
    //     );
    // }

    // public function remember(string $suffix, int $days, callable $callback)
    // {
    //     return Cache::remember(
    //         "store:{$this->id}:{$suffix}",
    //         now()->addDays($days),
    //         $callback
    //     );
    // }
}
