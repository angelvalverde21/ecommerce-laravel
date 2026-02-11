<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\Store;
use App\Traits\UploadImagesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class ImageDashboardController extends Controller
{

    use UploadImagesTrait;

    protected function getParentModel(array $validated): Model
    {
        $map = [
            'payment' => \App\Models\Payment::class,
            // 'order' => \App\Models\Order::class,
            // 'purchase' => \App\Models\Purchase::class,
        ];

        if (! isset($map[$validated['imageable_type']])) { //ojo imageable_type viene del request (angular), no es un campo de la tabla addresses
            throw ValidationException::withMessages([
                'imageable_type' => 'Tipo de modelo no válido',
            ]);
        }

        return $map[$validated['imageable_type']]::findOrFail($validated['imageable_id']);
    }

    public function index(Store $store, $product_id)
    {
        //
        try {

            $images = $store->products()->find($product_id)->images()->get();

            return responseOk($images, "Se ha procesado correctamente");
        } catch (\Throwable $th) {

            Log::error($th);

            return responseError($th, "Error al eliminar.... ");
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
        //
        Log::info($request->all());

        try {
            $validated = $request->validate([
                'imageable_type' => [
                    'required',
                    Rule::in(['payment']) // Agrega más tipos según sea necesario
                ],
                'imageable_id' => 'required|integer',
                'images.*' => 'required|image|max:2048',
            ]);

            $payment = $this->getParentModel($validated);

            foreach ($request->file('images') as $file) {

                $array = $this->getSizeArray($file, Image::DIR_PAYMENT);
                Log::info($array);
                $images[] = $payment->images()->create($array);

            }

            return responseOk($images, "Imágenes creadas correctamente");

        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
    public function destroy(Store $store, Request $request, $image_id)
    {


        Log::info($request->all());

        try {

            $parentModel = $this->getParentModel($request->all());

            $image = $parentModel->images()->findOrFail($image_id); // Verifica que la imagen pertenece al modelo padre

            $image->delete();

            return responseOk($image, "Imagen eliminada correctamente (destroy)");
        } catch (\Throwable $th) {
            Log::error($th);
            return responseError($th, "Error al eliminar la imagen (destroy)");
        }
    }
}
