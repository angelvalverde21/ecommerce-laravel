<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    /** @use HasFactory<\Database\Factories\ImageFactory> */
    use HasFactory;

    protected $guarded = ['id', 'created_at'];

    const DIR_PURCHASE = "purchases";
    const DIR_BATCH = "batches";
    const DIR_ROOT = 'images';
    const DIR_PRODUCT = 'products';

    protected $appends = [
        'url_thumbnail',
        'url_medium',
        'url_large',
    ];

    public function imageable()
    {
        return $this->morphTo();
    }


    public function getUrlThumbnailAttribute()
    {
        // return  Storage::disk('public')->url($this->original);
        if (strlen($this->thumbnail) > 1 && !(substr($this->thumbnail, 0, 4) === 'http')) {

            return asset(Storage::url(Image::DIR_ROOT . '/' . $this->thumbnail));
        }else{
            
            return $this->thumbnail;
        }
    }

    public function getUrlMediumAttribute()
    {
        // return  Storage::disk('public')->url($this->original);
        if (strlen($this->medium) > 1 && !(substr($this->thumbnail, 0, 4) === 'http')) {

            return asset(Storage::url(Image::DIR_ROOT . '/' . $this->medium));
        }else{
            return $this->medium;
        }
    }

    public function getUrlLargeAttribute()
    {
        // return  Storage::disk('public')->url($this->original);
        if (strlen($this->large) > 1 && !(substr($this->thumbnail, 0, 4) === 'http')) {

            return asset(Storage::url(Image::DIR_ROOT . '/' . $this->large));
        }else{
            return $this->large;
        }
    }
}
