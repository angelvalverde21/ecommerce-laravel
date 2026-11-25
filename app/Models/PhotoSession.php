<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PhotoSession extends Model
{
    use \App\Traits\HasTasks;
    protected $guarded = ['id', 'store_id', 'created_by'];
    protected $casts = ['shoot_date' => 'date:Y-m-d', 'delivery_date' => 'date:Y-m-d', 'budget' => 'decimal:2'];
    public function responsible() { return $this->belongsTo(User::class, 'responsible_id'); }
    public function purchases() { return $this->morphMany(Purchase::class, 'purchaseable'); }
    public function payments() { return $this->morphMany(Payment::class, 'paymentable'); }
    public function expenses() { return $this->hasMany(PhotoSessionExpense::class); }
    public function assets() { return $this->hasMany(PhotoSessionAsset::class); }
    public function photoLocation() { return $this->belongsTo(PhotoLocation::class); }
    public function photographerContact() { return $this->belongsTo(PhotoContact::class, 'photographer_contact_id'); }
    public function models() { return $this->belongsToMany(PhotoContact::class, 'photo_session_models'); }
    public function photographers() { return $this->belongsToMany(PhotoContact::class, 'photo_session_photographers'); }
    public function locations() { return $this->belongsToMany(PhotoLocation::class, 'photo_session_locations'); }
}
