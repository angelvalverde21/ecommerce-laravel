<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attachment extends Model
{
    protected $guarded = ['id', 'attachable_type', 'attachable_id'];
    protected $hidden = ['path'];
    protected $casts = ['size' => 'integer'];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
