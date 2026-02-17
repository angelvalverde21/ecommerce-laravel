<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Option;
use App\Models\Product;
use App\Models\Store;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductDashboardController extends Controller
{
    //

    public function setup(Store $store)
    {
        // Implementa la lógica para recuperar y devolver productos para el panel de control
        // Esto podría implicar consultar la base de datos por productos relacionados con la tienda
        // y devolverlos en un formato paginado o como una colección.

        try {
            Log::info('exito');
            //selectFields esta en el modelo Product
            return responseOk($store->with(['categories', 'brands'])->get(), "La informacion de la tienda ha sido obtenida satisfactoriamente");
        } catch (\Throwable $th) {
            //throw $th;
            Log::info($th);
        }
    }
    public function index(Store $store, Request $request)
    {
        // Implementa la lógica para recuperar y devolver productos para el panel de control
        // Esto podría implicar consultar la base de datos por productos relacionados con la tienda
        // y devolverlos en un formato paginado o como una colección.

        try {
            Log::info('exito');
            //selectFields esta en el modelo Product
            return responseOk($store->products()->with('category')->get(), "El listado de productos private ha sido obtenido correctamente (dashboard)");
        } catch (\Throwable $th) {
            //throw $th;
            Log::info($th);
            return responseError("Ocurrio un error al traer los productos");
        }
    }

    public function search(Store $store, $search)
    {
        //

        try {
            // $products = $store->productDetails($warehouse)->get();

            $search = pluralToSingular($search);

            $products = $store->products()->with(['image', 'variants.product', 'variants.variant_option_values.optionValue'])->search($search)->limit(10)->get();

            Log::info($products);

            return responseOk($products, "Datos obtenidos con exito de search");
        } catch (\Throwable $th) {
            Log::info($th);
            return responseError("Error al obtener los datos de search");
        }

        // $products = $store->products;

    }

    public function show(Store $store, $product_id)
    {
        //

        $product = $store->products()->with(['category', 'attributes', 'options.option_values', 'variants.product',  'variants.variant_option_values.optionValue'])->find($product_id);

        if (!$product) {
            return responseError([], "Error al obtener el producto x", 404);
        }

        return responseOk($product, "Datos obtenidos con exito del producto");

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
                    'slug' => Str::slug($resp['name']),
                    'body' => $resp['body'],
                    'category_id' => $resp['category_id'],
                    // 'tags' => $resp['tags'],
                    // 'price' => $resp['price'],
                    // 'compare_at_price' => $resp['compare_at_price'],
                    // 'quantity' => $resp['quantity'],
                    'user_id' => Auth::id(),
                    'store_id' => $store->id,
                    'status' => 1,
                ]
            );

            // $rows = array_map(function ($option) use ($product, $store) {
            //     return [
            //         'product_id' => $product->id,
            //         'store_id'   => $store->id,
            //         'name'       => $option['name'],
            //         'sort_order' => $option['sort_order'],
            //         'created_at' => now(),
            //         'updated_at' => now(),
            //     ];
            // }, Option::DEFAULT_OPTIONS);

            // Option::insert($rows);4

            Variant::create(
                [
                'product_id' => $product->id,
                'sku' => Str::upper(substr($product->name, 0, 3)) . "-" . $product->id,
                'price' => 0,
                'stock' => 0,
                ]
            );


            // $rows = array_map(function ($option) use ($product, $store) {
            //     return [
            //         'product_id' => $product->id,
            //         'store_id'   => $store->id,
            //         'name'       => $option['name'],
            //         'sort_order' => $option['sort_order']
            //     ];
            // }, Attribute::DEFAULT_OPTIONS);

            // Attribute::insert($rows);
            //creamos los attributes

            // $options_default = Option::DEFAULT_OPTIONS;

            // foreach ($options_default as $option) {
            //     Option::create([
            //         'product_id' => $product->id,
            //         'store_id' => $store->id,
            //         'name' => $option['name'],
            //         'sort_order' => $option['sort_order'],
            //     ]);
            // }

            // Option::insert(
            //     [
            //         [
            //             'product_id' => $product->id,
            //             'name' => 'Color',
            //             'created_at' => now(),
            //             'updated_at' => now(),
            //         ],
            //         [
            //             'product_id' => $product->id,
            //             'name' => 'Talla',
            //             'created_at' => now(),
            //             'updated_at' => now(),
            //         ],
            //         [
            //             'product_id' => $product->id,
            //             'name' => 'Marca',
            //             'created_at' => now(),
            //             'updated_at' => now(),
            //         ],
            //         [
            //             'product_id' => $product->id,
            //             'name' => 'Modelo',
            //             'created_at' => now(),
            //             'updated_at' => now(),
            //         ],
            //         [
            //             'product_id' => $product->id,
            //             'name' => 'Material',
            //             'created_at' => now(),
            //             'updated_at' => now(),
            //         ]
            //     ]
            // );

            // return redirect()->route('erp.products.edit', ['store' => $this->store, 'product' => $product]);

            DB::commit();

            return responseOk($product, "se agrego correctamente el product en create con sus opciones");
        } catch (\Throwable $th) {

            DB::rollback();
            Log::info($th);

            return responseError("Ha sucedido un error interno al crear el producto store x");
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


            $validated = $request->validate([
                'name'        => 'required|string|max:255',
                'body'        => 'nullable|string',
                'category_id' => 'required|exists:categories,id',
                'status'      => 'nullable|in:0,1', // opcional: 0=inactivo, 1=activo
            ]);

            $product = Product::findOrFail($product_id);

            // 🔹 Actualizar producto
            $product->update([
                'name'        => $validated['name'],
                'slug'        => $validated['slug'] ?? Str::slug($validated['name']),
                'body'        => $validated['body'] ?? $product->body,
                'category_id' => $validated['category_id'],
                // 'user_id'     => Auth::id(), // se actualiza con el usuario logueado
                'status'      => $validated['status'] ?? $product->status,
            ]);

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
            return responseError("Error al archiviar el product (destroy)");
        }
    }
}
