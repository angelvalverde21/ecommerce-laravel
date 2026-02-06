<?php

namespace App\Http\Controllers\Api\Dashboard\images;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\Payment;
use App\Models\Store;
use App\Traits\UploadImagesTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImagePaymentController extends Controller
{

    use UploadImagesTrait;


    /**
     * Display a listing of the resource.
     */
    public function index(Store $store, $payment_id)
    {
        try {
            $payment = Payment::with('images')->findOrFail($payment_id);

            // $images = $payment->images()->get();

            // Log::info($images);

            return responseOk($payment->images, "Listado de imágenes del payment obtenido correctamente");
        } catch (\Throwable $th) {
            Log::info($th);
            return responseError($th, "Error al obtener las imágenes del payment");
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
    public function store(Store $store, $payment_id, Request $request)
    {

        // return responseOk([], "hola");

        Log::info("hola");
        // Log::info($payment_id);


        // Log::info('se llamo a la funcion uploadPayment');

        try {


            $payment = Payment::findOrFail($payment_id);  //(A)

            DB::beginTransaction();

            Log::info($request->all());

            $request->validate([
                'file' => 'required|image|max:10240'  //10 megas
            ]);

            $request['dir'] = Image::DIR_PAYMENT;
            // $request['usage'] = 'images';

            $array = $this->getArrayUpload($request);
            Log::info($array);

            $images = $payment->images()->create($array);

            Log::info($images);

            // Log::info("---------------------------- se termino correctamente");

            DB::commit();

            return responseOk($images, "Imagen subida correctamente store");
        } catch (\Throwable $th) {

            Log::info($th);
            return responseError($th, "ha ocurrido un error al subir las imagenes store");
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
    public function update(Request $request, Store $store)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store, $image_id)
    {
        try {
            $image = Image::findOrFail($image_id);

            // $this->authorize('delete', $image); // Usa la policy

            $image->delete();

            return responseOk($image, "Imagen eliminada correctamente (destroy)");

        } catch (\Throwable $th) {
            Log::error($th);
            return responseError($th, "Error al eliminar la imagen (destroy)");
            
        }
    }
}
