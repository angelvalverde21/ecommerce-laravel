<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $guarded = ['id'];
    protected $attributes = ['status' => 'pending', 'version' => 1];
    protected $casts = ['assigned_to' => 'integer', 'due_date' => 'date:Y-m-d', 'completed_at' => 'datetime', 'version' => 'integer'];
    public function taskable() { return $this->morphTo(); }
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function events() { return $this->hasMany(TaskEvent::class); }
}
