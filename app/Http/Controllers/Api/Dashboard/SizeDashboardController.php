<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Size;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SizeDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Store $store, $product_id)
    {
        //

        try {
        
            DB::beginTransaction();
        
            //Aqui agregar el codigo

            $sizes = Size::where('product_id', $product_id)->orderBy('sort_order')->get();
        
            DB::commit();
        
            return responseOk($sizes, "Se ha procesado correctamente las tallas");
        
        } catch (\Throwable $th) {
        
            DB::rollback();
        
            return responseError($th, "Error recibir las tallas.... ");
        
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
        //

        try {

            DB::beginTransaction();

            $size = Size::create([
                'name' => $request->name,
                'product_id' => $product_id,
                'sort_order' => 1,
            ]);

            DB::commit();

            return responseOk($size, "se agrego correctamente la talla");

        } catch (\Illuminate\Database\QueryException $th) {

            DB::rollback();

            Log::info($th);

            if ($th->errorInfo[1] == 1062) {
                return responseError([], "El size ya existe", 409);
            }

            return responseError([], "Ha ocurrido un error la ingresar el size");
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
    public function destroy(Store $store, $product_id, $size_id)
    {
        try {
            $size = Size::findOrFail($size_id);

            // $this->authorize('delete', $size); // Usa la policy

            $size->delete();

            return responseOk($size, "sizen eliminada correctamente (destroy)");
        } catch (\Throwable $th) {
            Log::error($th);
            return responseError($th, "Error al eliminar la sizen (destroy)");
        }
    }

    public function sort(Store $store, $product_id, Request $request)
    {

        try {

            DB::beginTransaction();

            //
            $sizes = $request->all(); // array con id y sort_order

            Log::info($sizes);

            $order = 0;
            
            foreach ($sizes as $size) {
                DB::table('sizes')
                ->where('id', $size['id'])
                ->update(['sort_order' => $order++]);
            }
            
            

            //Ojjo hace multiples consultas pero es poco

            DB::commit();

            return responseOk($sizes, "Tallas ordenadas correctamente");

        } catch (\Throwable $th) {

            DB::rollback();

            Log::info($th);

            return responseError($th, "Error al ordenar las tallas");
        }
    }
}
