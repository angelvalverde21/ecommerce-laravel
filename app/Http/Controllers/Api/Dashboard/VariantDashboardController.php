<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Variant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VariantDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store, string $variant_id)
    {
        try {
            $variant = Variant::with(['product', 'variant_option_values.option', 'variant_option_values.optionValue', 'kardexes'])
                ->whereHas('product', function ($query) use ($store) {
                    $query->where('store_id', $store->id);
                })
                ->whereKey($variant_id)
                ->first();

            if (! $variant) {
                return responseError([], 'La variante no fue encontrada', 404);
            }

            return responseOk($variant, 'La variante se ha obtenido correctamente');
        } catch (\Throwable $exception) {
            Log::error('Error al obtener la variante', [
                'store_id' => $store->id,
                'variant_id' => $variant_id,
                'exception' => $exception,
            ]);

            return responseError([], 'Error al obtener la variante', 500);
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
    public function update(Store $store, Request $request, string $variant_id)
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer', Rule::in([$variant_id])],
            'direction' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1|max:2147483647',
            'comment' => 'nullable|string|max:255',
        ]);

        try {
            $variant = DB::transaction(function () use ($store, $request, $variant_id, $validated) {
                $variant = Variant::whereHas('product', function ($query) use ($store) {
                    $query->where('store_id', $store->id);
                })
                    ->whereKey($variant_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $quantity = (int) $validated['quantity'];
                $stock = (int) $variant->stock + ($validated['direction'] === 'in' ? $quantity : -$quantity);

                if ($stock < 0) {
                    throw ValidationException::withMessages([
                        'quantity' => 'No hay stock suficiente para retirar esa cantidad.',
                    ]);
                }

                if ($stock > 2147483647) {
                    throw ValidationException::withMessages([
                        'quantity' => 'El stock resultante supera el limite permitido.',
                    ]);
                }

                $comment = trim($validated['comment'] ?? '') ?: 'Conteo manual';

                $variant->update(['stock' => $stock]);

                $inventory = $store->inventories()->create([
                    'name' => $comment,
                    'user_id' => $request->user()->id,
                ]);

                $inventory->kardexes()->create([
                    'store_id' => $store->id,
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->id,
                    'direction' => $validated['direction'],
                    'quantity' => $quantity,
                    'comment' => $comment,
                ]);

                return $variant->refresh()->load([
                    'product',
                    'variant_option_values.option',
                    'variant_option_values.optionValue',
                    'kardexes',
                ]);
            });

            return responseOk($variant, 'El stock y su kardex se han actualizado correctamente');
        } catch (ModelNotFoundException $exception) {
            return responseError([], 'La variante no fue encontrada', 404);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('Error al actualizar el stock de la variante', [
                'store_id' => $store->id,
                'variant_id' => $variant_id,
                'exception' => $exception,
            ]);

            return responseError([], 'Error al actualizar el stock de la variante', 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
