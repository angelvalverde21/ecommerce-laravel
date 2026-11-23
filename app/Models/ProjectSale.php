<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ProjectSale extends Model
{
    protected $table = 'collections_sales';
    protected $guarded = ['id'];
    public function process() { return $this->belongsTo(ProjectProcess::class, 'process_id'); }
    public function order() { return $this->belongsTo(Order::class); }
    public function receipts() { return $this->hasMany(ProjectReceipt::class, 'sale_id'); }
}
