<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PhotoLocation extends Model
{
    protected $guarded = ['id', 'store_id'];
    protected $casts = ['active' => 'boolean', 'rate' => 'decimal:2'];
    public function sessions() { return $this->belongsToMany(PhotoSession::class, 'photo_session_locations'); }
    public function address() { return $this->morphOne(Address::class, 'addressable'); }
}
