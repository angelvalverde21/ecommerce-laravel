<?php

namespace App\Http\Controllers\Api\Dashboard\Images;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\Product;
use App\Models\Store;
use App\Traits\UploadImagesTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImageProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    use UploadImagesTrait;

    public function index(Store $store, $product_id)
    {
        try {
            $product = Product::with('images')->findOrFail($product_id);

            // $images = $product->images()->get();

            // Log::info($images);

            return responseOk($product->images, "Listado de imágenes del product obtenido correctamente");
        } catch (\Throwable $th) {
            Log::info($th);
            return responseError($th, "Error al obtener las imágenes del product");
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
    public function store(Store $store, $product_id, Request $request)
    {

        // return responseOk([], "hola");

        Log::info("hola");
        // Log::info($product_id);


        // Log::info('se llamo a la funcion uploadPayment');

        try {


            $product = Product::findOrFail($product_id);  //(A)

            DB::beginTransaction();

            Log::info($request->all());

            $request->validate([
                'file' => 'required|image|max:10240'  //10 megas
            ]);

            $request['dir'] = Image::DIR_PRODUCT;
            // $request['usage'] = 'images';

            $array = $this->getArrayUpload($request);
            Log::info($array);

            $images = $product->images()->create($array);

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
