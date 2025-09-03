<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Image;
use App\Models\Store;
use App\Traits\UploadImagesTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CategoryDashboardController extends Controller
{
 
        /**
     * Display a listing of the resource.
     */
    function buildCategoryTree($categories)
    {
        return $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'is_size' => $category->is_size,
                'is_color' => $category->is_color,
                'status' => $category->status,
                'created_at' => $category->created_at,
                'children' => $this->buildCategoryTree($category->children)
            ];
        });
    }

    public function index(Store $store)
    {
        //
        $categories = $store->categories()
            ->whereNull('parent_id') // Solo categorías principales
            ->active() //filtra las categorias activas
            ->with(['children' => function ($q) {
                $q->active(); // también filtra subcategorías activas
            }])
            ->get();

        Log::info($categories);

        // Construir la estructura en árbol (buildCategoryTree)
        return responseOk($this->buildCategoryTree($categories), 'categorias obtenidas con exito (index) buildCategoryTree');
    }

    /**
     * Show the form for creating a new resource.
     */

    /**
     * Store a newly created resource in storage.
     */
    public function store(Store $store,  Request $request)
    {
        //
        $resp = $request->all();

        Log::info('creando category');

        // $rules = $this->rules;

        // $this->validate($rules);

        try {

            $category = Category::create(
                [
                    'name' => $resp['name'],
                    'slug' => Str::slug($resp['name']),
                    'parent_id' =>  is_numeric($request->parent_id) && (int)$request->parent_id > 0 ? (int)$request->parent_id : null,
                    'is_color'   => filter_var($resp['is_color'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'is_size'    => filter_var($resp['is_size'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'store_id' => $store->id,
                    'user_id' => Auth::id(),
                ]
            );

            return responseOk($category, "La categoria ha sido creada correctamente (create)");
        } catch (\Throwable $th) {
            //throw $th;
            Log::info($th);
            return responseError($th, "Ha ocurrido un error interno al crear la categoria");
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store, $category_id)
    {
        //
        try {

            $category = Category::where("store_id", $store->id)->findOrFail($category_id);

            return responseOk($category, "Categoria obtenida correctamente (show)");
        } catch (\Throwable $th) {
            Log::error($th);
            return responseError($th, "Error al obtener la categoria (show)");
        }
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
    public function update(Store $store, $category_id, Request $request)
    {

        try {

            $validatedData = $request->validate([
                'name' => 'required|string|max:255'
            ]);

            // Usa el operador de fusión de null (??) para manejar el caso cuando 'slug' es nulo
            $validatedData['slug'] = $validatedData['slug'] ?? Str::slug($validatedData['name']);

            $category = Category::updateOrCreate(
                ['id' => $category_id],  // El campo 'id' indica si se actualiza o crea
                $validatedData
            );

            return responseOk($category, "Categoria actualizada correctamente (update)");
        } catch (\Throwable $th) {
            //throw $th;
            Log::info($th);
            return responseError("Error al actualizar la categoria", $th);
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store, string $category_id)
    {
        //
        try {

            $category = Category::where('store_id', $store->id)
                ->where('id', $category_id)
                ->firstOrFail();

            $category->status = 0;
            $category->save();

            return responseOk($category, "Se ha eliminado correctamente la categoria");
        } catch (\Throwable $th) {

            Log::error($th);

            return responseError($th, "Ha ocurrido un error al intentar eliminar la categoria desde el servidor");
        }
    }

    public function showListBySlug(Store $store, $slug)
    {
        //
        try {
            $products = $store->products()
                ->whereHas('category', function ($query) use ($slug) {
                    $query->where('slug', $slug);
                })
                ->with('category')
                ->get();

            return responseOk($products, "Productos obtenidos correctamente por slug: $slug");
        } catch (\Throwable $th) {
            //throw $th;
            return responseError($th, "Error al obtener productos por slug: $slug");
        }
    }

}
