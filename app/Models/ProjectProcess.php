<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ProjectProcess extends Model
{
    protected $table = 'collections_processes';
    protected $guarded = ['id'];
    public function collection() { return $this->belongsTo(Collection::class, 'collection_id'); }
    public function purchases() { return $this->morphMany(Purchase::class, 'purchaseable'); }
    public function sales() { return $this->hasMany(ProjectSale::class, 'process_id'); }
    public function responsible() { return $this->belongsTo(User::class, 'responsible_id'); }
}
