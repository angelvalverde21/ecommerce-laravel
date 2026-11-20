<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryReception extends Model
{
    protected $guarded = ['id', 'created_at'];
    protected $hidden = ['payload_hash'];
    protected $casts = ['number' => 'integer', 'received_on' => 'date:Y-m-d', 'reversed_at' => 'datetime', 'posted_at' => 'datetime', 'revision' => 'integer', 'is_historical' => 'boolean', 'stock_applied' => 'boolean'];

    // Call only after locking the source document inside the creation transaction.
    public static function nextNumber(Manufacture $source): int
    {
        return 1 + (int) static::where('store_id', $source->store_id)->where('source_type', $source->getMorphClass())
            ->where('source_id', $source->id)->max('number');
    }

    public function source() { return $this->morphTo(); }
    public function items() { return $this->hasMany(InventoryReceptionItem::class); }
    public function receiver() { return $this->belongsTo(User::class, 'received_by'); }
    public function reverser() { return $this->belongsTo(User::class, 'reversed_by'); }
    public function poster() { return $this->belongsTo(User::class, 'posted_by'); }
    public function kardexes() { return $this->hasManyThrough(Kardex::class, InventoryReceptionItem::class); }
}
