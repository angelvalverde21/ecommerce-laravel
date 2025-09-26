<?php

namespace App\Http\Controllers\Api\Dashboard\Images;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\Purchase;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\UploadImagesTrait;

class ImagePurchaseController extends Controller
{

    use UploadImagesTrait;

    /**
     * Display a listing of the resource.
     */
    public function index(Store $store, $purchase_id)
    {
        try {
            $purchase = Purchase::with('images')->findOrFail($purchase_id);

            // $images = $purchase->images()->get();

            // Log::info($images);

            return responseOk($purchase->images, "Listado de imágenes del purchase obtenido correctamente");
        } catch (\Throwable $th) {
            Log::info($th);
            return responseError($th, "Error al obtener las imágenes del purchase");
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
    public function store(Store $store, $purchase_id, Request $request)
    {

        // return responseOk([], "hola");

        Log::info("hola");
        // Log::info($product_id);


        // Log::info('se llamo a la funcion uploadPayment');

        try {


            $purchase = Purchase::findOrFail($purchase_id);  //(A)

            DB::beginTransaction();

            Log::info($request->all());

            $request->validate([
                'file' => 'required|image|max:10240'  //10 megas
            ]);

            $request['dir'] = Image::DIR_PURCHASE;
            // $request['usage'] = 'images';

            $array = $this->getArrayUpload($request);
            Log::info($array);

            $images = $purchase->images()->create($array);

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
    public function destroy(string $id)
    {
        //
    }
}
