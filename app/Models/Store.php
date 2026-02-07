<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function suppliers()
    {
        return $this->hasManyThrough(Supplier::class, User::class);
    }
    
    public function customers()
    {
        return $this->hasManyThrough(Customer::class, User::class);
    }
    
    public function employees()
    {
        return $this->hasManyThrough(Employee::class, User::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
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

    public function manufactures(){
        return $this->hasMany(Manufacture::class);
    }

    public function pettyCashes()
    {
        return $this->hasMany(PettyCash::class);
    }

    public function gateways(){
        return $this->hasMany(Gateway::class);  
    }
}
