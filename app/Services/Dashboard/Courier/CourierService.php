<?php

namespace App\Services\Dashboard\Courier;

use App\Models\Courier;
use App\Models\Store;

class CourierService
{
    // Couriers form a shared catalog; they are independent of users and store memberships.
    public function index(Store $store, int $perPage = 20, array $filters = [])
    {
        return Courier::query()
            ->when($filters['with_addresses'] ?? false, function ($query) {
                $query->with('addresses');
            })
            ->withCount('addresses')
            ->when(trim($filters['search'] ?? '') !== '', function ($query) use ($filters) {
                $query->search(trim($filters['search']));
            })
            ->when(isset($filters['status']) && $filters['status'] !== '', function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when(!empty($filters['delivery_type']), function ($query) use ($filters) {
                $query->where('delivery_type', $filters['delivery_type']);
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function search(Store $store, string $search, int $perPage = 50)
    {
        return Courier::query()
            ->search($search)
            ->withCount('addresses')
            ->orderBy('name')
            ->limit($perPage)
            ->get();
    }

    public function agency(Store $store, int $perPage = 20)
    {
        return Courier::active()
            ->with('addresses')
            ->where('delivery_type', 'agency')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function express(Store $store, int $perPage = 20)
    {
        return Courier::active()
            ->with('addresses')
            ->where('delivery_type', 'express')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function active(Store $store, int $perPage = 100)
    {
        return Courier::active()->withCount('addresses')->orderBy('name')->limit($perPage)->get();
    }

    public function blocked(Store $store, int $perPage = 100)
    {
        return Courier::blocked()->withCount('addresses')->orderBy('name')->limit($perPage)->get();
    }

    public function show(Store $store, int $id)
    {
        return Courier::withCount('addresses')->findOrFail($id);
    }

    public function addresses(int $courierId, string $search = '')
    {
        return Courier::findOrFail($courierId)
            ->addresses()
            ->with('district')
            ->when(trim($search) !== '', function ($query) use ($search) {
                $term = '%' . trim($search) . '%';
                $query->where(function ($query) use ($term) {
                    $query->where('name', 'LIKE', $term)
                        ->orWhere('primary', 'LIKE', $term)
                        ->orWhere('phone', 'LIKE', $term)
                        ->orWhereHas('district', function ($query) use ($term) {
                            $query->where('name', 'LIKE', $term);
                        });
                });
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(20);
    }
}
