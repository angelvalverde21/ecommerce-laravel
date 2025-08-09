<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductDashboardController extends Controller
{
    //
    public function index(Store $store, Request $request)
    {
        // Implementa la lógica para recuperar y devolver productos para el panel de control
        // Esto podría implicar consultar la base de datos por productos relacionados con la tienda
        // y devolverlos en un formato paginado o como una colección.

        try {
            Log::info('exito');
            //selectFields esta en el modelo Product
            return responseOk($store->products, "El listado de productos private ha sido obtenido correctamente (dashboard)");
        } catch (\Throwable $th) {
            //throw $th;
            Log::info($th);
        }
    }


    public function search(Store $store, $search)
    {
        //

        try {
            // $products = $store->productDetails($warehouse)->get();

            $search = pluralToSingular($search);

            $products = $store->products()->withSkusQuantity()->search($search)->limit(10)->get();

            Log::info($products);

            return responseOk($products, "Datos obtenidos con exito de search");
        } catch (\Throwable $th) {
            Log::info($th);
            return responseError($th);
        }

        // $products = $store->products;


    }

    public function show(Store $store, $product_id)
    {
        //

        try {
            return responseOk($store->productById($product_id)
                ->with([
                    'sizes',
                    'category',
                    'colors',
                    'colors' => function ($query) {
                        $query->where('status', 1)
                            ->withCount('images as count_images');
                    },
                ])
                ->first(), "Datos obtenidos con exito (dashboard)");
        } catch (\Throwable $th) {
            Log::info($th);
            return responseError($th, "Error al obtener el producto x");
        }

        // return $query;
    }

    public function store(Store $store, Request $request) //create
    {

        $resp = $request->all();

        Log::info('creando producto');

        // $rules = $this->rules;

        // $this->validate($rules);

        try {


            DB::beginTransaction();


            $product = Product::create(
                [
                    'name' => $resp['name'],
                    'body' => $resp['body'],
                    'tags' => $resp['tags'],
                    'price' => $resp['price'],
                    'slug' => Str::slug($resp['name']),
                    'extract' => '',
                    'user_id' => Auth()->user()->id,
                    'category_id' => $resp['category_id'],
                    'store_id' => $store->id,
                    'status' => 1,
                ]
            );


            // return redirect()->route('erp.products.edit', ['store' => $this->store, 'product' => $product]);

            DB::commit();

            return responseOk($product, "se agrego correctamente el product en create");
        } catch (\Throwable $th) {

            DB::rollback();
            Log::info($th);
            return responseError($th, "Ha sucedido un error interno al crear el producto store");
        }
    }

    public function update(Store $store, $product_id, Request $request)
    {
        // Implementa la lógica para actualizar un producto existente
        // Esto podría implicar validar los datos de la solicitud,
        // actualizar el producto en la base de datos y devolver el producto actualizado.
        try {
            Log::info('updatex');
            Log::info($request->all());

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'body' => 'nullable|string',
                'slug' => 'nullable|string',
                'tags' => 'nullable|string',
                'body' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'over_sale' => '',
                'sell_size_unique' => '',
            ]);

            // Usa el operador de fusión de null (??) para manejar el caso cuando 'slug' es nulo
            $validatedData['slug'] = $validatedData['slug'] ?? Str::slug($validatedData['name']);

            $product = Product::updateOrCreate(
                ['id' => $product_id],  // El campo 'id' indica si se actualiza o crea
                $validatedData
            );
            return responseOk($product, "Datos guardados correctamente update");
        } catch (\Throwable $th) {
            //throw $th;
            Log::info($th);
            return responseError("Error al guardar los datos del producto desde Product Private controller - > update", $th);
        }
    }

    public function destroy(Store $store, $product_id)
    {
        try {

            $product = Product::findOrFail($product_id);

            $product->status = 0;

            $product->save();

            return responseOk($product, "Producto archivado correctamente (destroy)");

        } catch (\Throwable $th) {

            Log::error($th);
            return responseError($th, "Error al archiviar el product (destroy)");

        }
    }
}
