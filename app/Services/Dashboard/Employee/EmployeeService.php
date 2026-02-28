<?php

namespace App\Services\Dashboard\Employee;

use App\Models\Store;
use App\Models\Employee;
use App\Services\Shopify\ShopifyOrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;



class EmployeeService
{

    protected ShopifyOrderService $orderShopifyService;

    public function __construct()
    {
        // Pasamos el modelo que vamos a usar
        $this->orderShopifyService = new ShopifyOrderService();
    }

    public function getOrdersByTag($tag, int $limit = 20, $cursor = null): array
    {
        return $this->orderShopifyService->getOrdersByTag($tag, $limit, $cursor);
    }
    
}
