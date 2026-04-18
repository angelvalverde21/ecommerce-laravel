<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\Payment;
use App\Models\Store;
use App\Traits\UploadImagesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentDashboardController extends Controller
{

    use UploadImagesTrait;

    protected function getParentModel(array $validated): Model
    {
        $map = [
            'manufacture' => \App\Models\Manufacture::class,
            'petty_cash' => \App\Models\PettyCash::class,
            'purchase' => \App\Models\Purchase::class,
            'employee' => \App\Models\Employee::class,
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

        Log::info($request);
        // Log::info($request->allFiles());
        // Log::info($request->headers->all());

        try {

            //
            $validated = $request->validate([
                'paymentable_type' => [
                    'required',
                    Rule::in(['manufacture', 'petty_cash', 'purchase', 'employee']) // Agrega más tipos según sea necesario
                ],
                'paymentable_id' => 'required|integer',
                'gateway_id' => 'required|integer|max:255',
                'amount' => 'required|numeric',
                'direction' => 'required|string|max:255',
                'comment' => 'nullable|string|max:255',
                'date' => 'required|date',
                'images' => 'array',
                'images.*' => 'image|max:2048',
            ]);

            $parentModel = $this->getParentModel($validated);

            // DB::beginTransaction();

            $payment = $parentModel->payments()->create([
                'store_id' => $store->id,
                'user_id' => Auth::id(),
                'gateway_id' => $validated['gateway_id'],
                'amount' => $validated['amount'],
                'direction' => $validated['direction'],
                'comment' => $validated['comment'],
                'status' => 'paid',
                'date' => $validated['date'],
            ]);

            if ($request->hasFile('images')) {

                foreach ($request->file('images') as $file) {
                    $array = $this->getSizeArray($file, Image::DIR_PAYMENT);
                    Log::info($array);
                    $payment->images()->create($array);
                }
            }
            // $request['usage'] = 'images';
            // DB::commit();

            return responseOk($payment->load(['gateway', 'images']), "El pago ha sido registrado correctamente.");
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

        Log::info($request);
        Log::info($request->all());
        Log::info($request->allFiles());
        Log::info($request->headers->all());

        $validated = $request->validate([
            'paymentable_type' => [
                'required',
                Rule::in(['manufacture', 'petty_cash', 'purchase', 'employee']) // Agrega más tipos según sea necesario, por ejemplo pagos de manufacture, pagos de ordenes, pagos de compras, etc
            ],
            'paymentable_id' => 'required|integer',
            'gateway_id' => 'required|integer|max:255',
            'amount' => 'required|numeric',
            'direction' => 'required|string|max:255',
            'comment' => 'string|max:255',
            'date' => 'required|date'
        ]);

        $parentModel = $this->getParentModel($validated);

        $payment = $parentModel->payments()->findOrFail($payment_id); // Verifica que el pago pertenece al modelo padre

        try {

            // DB::beginTransaction();

            $payment->update([
                'gateway_id' => $validated['gateway_id'],
                'amount' => $validated['amount'],
                'direction' => $validated['direction'],
                'comment' => $validated['comment'],
                'status' => 'paid',
                'date' => $validated['date'],
            ]);

            // DB::commit();

            return responseOk($payment->load(['gateway', 'images']), "El pago ha sido actualizado correctamente.");
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
