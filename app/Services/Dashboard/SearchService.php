<?php

namespace App\Services\Dashboard\Crud;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SearchService
{
    protected string $modelClass;

    /**
     * Constructor: pasas el modelo (Supplier, Courier, etc)
     */
    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
    }



}
