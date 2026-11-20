<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PhotoSessionAsset extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $hidden = ['bucket', 'preview_bucket', 'object_key', 'upload_id'];
    protected $casts = ['bytes' => 'integer', 'selected' => 'boolean', 'previews' => 'array', 'completed_at' => 'datetime'];
    public function session() { return $this->belongsTo(PhotoSession::class, 'photo_session_id'); }
}
