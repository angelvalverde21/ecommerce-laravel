<?php

namespace App\Services\Dashboard\Supplier;

use App\Models\{Address, Store, Supplier};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierAddressService
{
    public function supplier(Store $store, int $id): Supplier
    {
        return $store->suppliers()->findOrFail($id);
    }

    public function makeDefault(Supplier $supplier, Address $address): Address
    {
        return DB::transaction(function () use ($supplier, $address) {
            Supplier::whereKey($supplier->id)->lockForUpdate()->firstOrFail();
            $owned = $supplier->addresses()->where('status', 1)->findOrFail($address->id);
            $supplier->addresses()->where('is_default', true)->update(['is_default' => false]);
            $owned->update(['is_default' => true]);
            return $owned;
        });
    }

    public function ensureDefault(Supplier $supplier): void
    {
        DB::transaction(function () use ($supplier) {
            Supplier::whereKey($supplier->id)->lockForUpdate()->firstOrFail();
            $addresses = $supplier->addresses()->where('status', 1)->orderByDesc('is_default')->orderBy('id')->get();
            if ($addresses->isNotEmpty() && ($addresses->where('is_default', true)->count() !== 1)) {
                $this->makeDefault($supplier, $addresses->first());
            }
        });
    }

    public function resolve(Store $store, ?int $supplierId, ?int $addressId): ?int
    {
        if (!$supplierId) {
            if ($addressId) throw ValidationException::withMessages(['supplier_address_id' => 'Selecciona primero un proveedor.']);
            return null;
        }
        $supplier = $this->supplier($store, $supplierId);
        $addresses = $supplier->addresses()->where('status', 1)->get();
        if ($addressId) {
            if (!$addresses->contains('id', $addressId)) throw ValidationException::withMessages(['supplier_address_id' => 'La dirección no pertenece al proveedor o no está disponible.']);
            return $addressId;
        }
        if ($addresses->isEmpty()) return null; // Legacy suppliers can be completed from their profile.
        if ($addresses->count() === 1) return $addresses->first()->id;
        $default = $addresses->firstWhere('is_default', true);
        if ($default) return $default->id;
        throw ValidationException::withMessages(['supplier_address_id' => 'Selecciona una dirección del proveedor.']);
    }
}
