<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Store $store)
    {
        try {
            $orders = Order::with([
                'addresses.district.province.department',
                'courierAddress.district',
                'courierAddress.addressable',
            ])
                ->where('store_id', $store->id)
                ->orderByDesc('id')
                ->get();

            $orders->each(function (Order $order) {
                $order->courierAddress?->makeVisible(['addressable_type', 'addressable_id']);
            });

            return responseOk($orders, 'El listado de ordenes se ha obtenido correctamente');
        } catch (\Throwable $exception) {
            Log::error('Error al obtener el listado de ordenes', [
                'store_id' => $store->id,
                'exception' => $exception,
            ]);

            return responseError([], 'Error al obtener el listado de ordenes', 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Store $store, Request $request)
    {

        Log::info($request);

        $validated = $request->validate([
            'address' => 'required|array:name,identity_id,document_number,phone,primary,secondary,references,district_id',
            'address.name' => 'required|string|max:255',
            'address.identity_id' => 'nullable|integer|exists:identities,id',
            'address.document_number' => 'nullable|string|max:255',
            'address.phone' => 'required|string|max:255',
            'address.primary' => 'required|string|max:255',
            'address.secondary' => 'nullable|string|max:255',
            'address.references' => 'nullable|string|max:255',
            'address.district_id' => 'required|integer|exists:districts,id',

            'shipping' => 'required|array:courier_address_id,delivery_cost,is_cod,freight_collect',
            'shipping.courier_address_id' => 'required|integer|exists:addresses,id',
            'shipping.delivery_cost' => 'required|numeric|min:0',
            'shipping.is_cod' => 'nullable|boolean',
            'shipping.freight_collect' => 'nullable|boolean',

            'variants' => 'required|array|list|min:1',
            'variants.*' => 'required|array:id,sku,name,variant_option_values,quantity,price',
            'variants.*.id' => 'required|integer|distinct|exists:variants,id',
            'variants.*.sku' => 'required|string|max:255',
            'variants.*.name' => 'required|string|max:255',
            'variants.*.variant_option_values' => 'present|array|list',
            'variants.*.variant_option_values.*' => 'required|array:id,variant_id,option_id,option_value_id,option_value',
            'variants.*.variant_option_values.*.id' => 'required|integer|min:1',
            'variants.*.variant_option_values.*.variant_id' => 'required|integer|min:1',
            'variants.*.variant_option_values.*.option_id' => 'required|integer|min:1',
            'variants.*.variant_option_values.*.option_value_id' => 'required|integer|min:1',
            'variants.*.variant_option_values.*.option_value' => 'required|array:id,option_id,value,sort_order,created_at,updated_at',
            'variants.*.variant_option_values.*.option_value.id' => 'required|integer|min:1',
            'variants.*.variant_option_values.*.option_value.option_id' => 'required|integer|min:1',
            'variants.*.variant_option_values.*.option_value.value' => 'required|string|max:255',
            'variants.*.variant_option_values.*.option_value.sort_order' => 'sometimes|integer|min:0',
            'variants.*.variant_option_values.*.option_value.created_at' => 'nullable|date',
            'variants.*.variant_option_values.*.option_value.updated_at' => 'nullable|date',
            'variants.*.quantity' => 'required|integer|min:1',
            'variants.*.price' => 'required|numeric|min:0',
        ]);

        try {
            $order = DB::transaction(function () use ($store, $request, $validated) {
                $subtotal = round(collect($validated['variants'])->sum(function ($variant) {
                    return $variant['quantity'] * $variant['price'];
                }), 2);
                $deliveryCost = round($validated['shipping']['delivery_cost'], 2);

                $order = Order::create([
                    'store_id' => $store->id,
                    'user_id' => $request->user()?->id,
                    'variants' => $validated['variants'],
                    'courier_address_id' => $validated['shipping']['courier_address_id'],
                    'delivery_cost' => $deliveryCost,
                    'is_cod' => $validated['shipping']['is_cod'] ?? false,
                    'freight_collect' => $validated['shipping']['freight_collect'] ?? false,
                    'subtotal' => $subtotal,
                    'igv' => 0,
                    'total' => round($subtotal + $deliveryCost, 2),
                ]);

                $order->addresses()->create([
                    ...$validated['address'],
                    'purpose' => 'shipping',
                ]);

                return $order->refresh()->load('addresses');
            });

            return responseOk($order, 'La orden y su direccion se han creado correctamente');

        } catch (\Throwable $exception) {


            Log::info($exception);

            return responseError([], 'Error al crear la orden y su direccion', 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store, string $order_id)
    {
        try {
            $order = Order::with(['addresses.district.province.department', 'courierAddress.district', 'courierAddress.addressable'])
                ->where('store_id', $store->id)
                ->whereKey($order_id)
                ->first();

            if (! $order) {
                return responseError([], 'La orden no fue encontrada', 404);
            }

            $order->courierAddress?->makeVisible(['addressable_type', 'addressable_id']);

            return responseOk($order, 'La orden se ha obtenido correctamente');
        } catch (\Throwable $exception) {
            Log::error('Error al obtener la orden', [
                'store_id' => $store->id,
                'order_id' => $order_id,
                'exception' => $exception,
            ]);

            return responseError([], 'Error al obtener la orden', 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
