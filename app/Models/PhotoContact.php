<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PhotoContact extends Model
{
    protected $guarded = ['id', 'store_id'];
    protected $casts = ['active' => 'boolean'];
    public function images() { return $this->morphMany(Image::class, 'imageable')->latest('id'); }
}
