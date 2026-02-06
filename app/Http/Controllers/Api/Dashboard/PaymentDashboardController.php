<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentDashboardController extends Controller
{

    protected function getParentModel(array $validated): Model
    {
        $map = [
            'manufacture' => \App\Models\Manufacture::class,
            // 'order' => \App\Models\Order::class,
            // 'purchase' => \App\Models\Purchase::class,
        ];

        if (! isset($map[$validated['paymentable_type']])) {
            throw ValidationException::withMessages([
                'paymentable_type' => 'Tipo de modelo no válido',
            ]);
        }

        return $map[$validated['paymentable_type']]::findOrFail($validated['paymentable_id']);
    }

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
    public function store(Store $store, Request $request)
    {
        //
        $validated = $request->validate([
            'paymentable_type' => [
                'required',
                Rule::in(['manufacture']) // Agrega más tipos según sea necesario
            ],
            'paymentable_id' => 'required|integer',
            'method' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'direction' => 'required|string|max:255',
            'date' => 'required|date',
        ]);

        $parentModel = $this->getParentModel($validated);

        try {

            // DB::beginTransaction();

            $payment = $parentModel->payments()->create([
                'store_id' => $store->id,
                'user_id' => Auth::id(),
                'method' => $validated['method'],
                'amount' => $validated['amount'],
                'direction' => $validated['direction'],
                'status' => 'paid',
                'date' => $validated['date'],
            ]);

            // DB::commit();

            return responseOk($payment, "El pago ha sido registrado correctamente.");
        } catch (\Exception $e) {
            // DB::rollBack();
            Log::info($e);
            return responseError("Error al crear el pago");
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Store $store, Request $request, $payment_id)
    {
        $validated = $request->validate([
            'paymentable_type' => [
                'required',
                Rule::in(['manufacture']) // Agrega más tipos según sea necesario
            ],
            'paymentable_id' => 'required|integer',
            'method' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'date' => 'required',
            'direction' => 'required|string|max:255',
        ]);

        $payment = Payment::findOrFail($payment_id);

        try {

            // DB::beginTransaction();

            $payment->update([
                'method' => $validated['method'],
                'amount' => $validated['amount'],
                'direction' => $validated['direction'],
                'date' => $validated['date'],
            ]);

            // DB::commit();

            return responseOk($payment, "El pago ha sido actualizado correctamente.");
        } catch (\Exception $e) {
            // DB::rollBack();
            Log::info($e);
            return responseError("Error al actualizar el pago");
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store, $payment_id)
    {
        //

        try {
            $payment = Payment::findOrFail($payment_id);
            $payment->delete();

            return responseOk($payment, "El pago ha sido eliminado correctamente.");
        } catch (\Exception $e) {
            Log::info($e);
            return responseError("Error al eliminar el pago");
        }
    }
}
