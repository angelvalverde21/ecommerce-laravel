<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhotoSessionExpense extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['payload_hash', 'request_key'];
    public function payment() { return $this->belongsTo(Payment::class); }
}
