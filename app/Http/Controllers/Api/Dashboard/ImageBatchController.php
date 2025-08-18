<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Image;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\UploadImagesTrait;

class ImageBatchController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    use UploadImagesTrait;

    public function index(Store $store, $batch_id)
    {
        //
        try {
            $batch = Batch::with('images')->findOrFail($batch_id);

            // $images = $batch->images()->get();

            // Log::info($images);

            return responseOk($batch->images, "Listado de imágenes del batch obtenido correctamente");
        } catch (\Throwable $th) {
            Log::info($th);
            return responseError($th, "Error al obtener las imágenes del batch");
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
    public function store(Store $store, $batch_id, Request $request)
    {

        // return responseOk([], "hola");

        Log::info("hola");
        // Log::info($product_id);


        // Log::info('se llamo a la funcion uploadPayment');

        try {


            $batch = Batch::findOrFail($batch_id);  //(A)

            DB::beginTransaction();

            Log::info($request->all());

            $request->validate([
                'file' => 'required|image|max:10240'  //10 megas
            ]);

            $request['dir'] = Image::DIR_BATCH;
            // $request['usage'] = 'images';

            $array = $this->getArrayUpload($request);
            Log::info($array);

            $images = $batch->images()->create($array);

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
