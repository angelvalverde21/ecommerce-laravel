<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionStageProgress extends Model
{
    protected $table = 'production_stage_progress';

    protected $guarded = ['id', 'created_at'];

    protected $attributes = ['status' => 'pending', 'revision' => 0, 'sample_review' => 'pending'];

    protected $casts = [
        'expected_end' => 'date:Y-m-d', 'completed_at' => 'datetime', 'reviewed_at' => 'datetime',
        'sample_photos' => 'array', 'sample_quantity' => 'integer', 'revision' => 'integer',
    ];

    public function stage()
    {
        return $this->belongsTo(ProductionStage::class);
    }

    public function manufacture()
    {
        return $this->belongsTo(Manufacture::class);
    }

    public function events()
    {
        return $this->hasMany(ProductionStageEvent::class, 'production_stage_id')->orderByDesc('id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getCodeAttribute(): string
    {
        return $this->stage->code;
    }
}
