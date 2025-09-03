<?php

namespace App\Http\Controllers\Api\public;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\StoreUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StorePublicController extends Controller
{

    public function register(Request $request)
    {


        try {

            DB::beginTransaction();


            //Creando la tienda

            //Creando el usuario

            //Asignando el usuario a la tienda

            Log::info("Empezando a crear la tienda");


            $validatedData = $request->validate([
                'store' => ['required', 'string', 'min:2'],
                'name' => ['required', 'string', 'min:3'],
                'phone' => ['required', 'regex:/^\d{7,15}$/'],
                'email' => ['required', 'email', 'unique:users,email'],
                'password' => ['required', 'string', 'min:6'],
            ], [
                'store.required' => 'El nombre de la tienda es obligatorio.',
                'store.min' => 'El nombre de la tienda debe tener al menos 2 caracteres.',
                'name.required' => 'El nombre es obligatorio.',
                'name.min' => 'El nombre debe tener al menos 3 caracteres.',
                'phone.required' => 'El teléfono es obligatorio.',
                'phone.regex' => 'El teléfono debe tener entre 7 y 15 dígitos.',
                'email.required' => 'El correo electrónico es obligatorio.',
                'email.email' => 'El correo electrónico no es válido.',
                'email.unique' => 'El correo electrónico ya está registrado.',
                'password.required' => 'La contraseña es obligatoria.',
                'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            ]);

            Log::info("se paso la validacion");

            $slug = Str::slug($validatedData['store']);

            // Verificar si ya existe la tienda
            if (Store::where('slug', $slug)->exists()) {
                
                return responseError("error", "La tienda " . $validatedData['store'] . " ya existe", 409);

                DB::rollback();

            }

            // Inserción de datos
            $store = Store::create([
                'name' => $validatedData['store'],
                'phone' => $validatedData['phone'],
                'email' => $validatedData['email'],
                'slug' => Str::slug($validatedData['store']),
            ]);

            Log::info("se creo el store");

            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'password' => Hash::make($validatedData['password']),
                'store_id' => $store->id,
            ]);

            Log::info("se creo el user");

            $store_user = StoreUser::create(
                [
                    "store_id" => $store->id,
                    "user_id" => $user->id,
                ]
            );

            Log::info("se creo el store_user");

            $accessToken = $user->createToken('authToken')->accessToken;

            $user_data['name'] = $user->name;
            $user_data['email'] = $user->email;
            $user_data['phone'] = $user->phone;

            $data = [
                "store" => $store, //ojo usamos load y no with, porque $store ya se inyecto en el metodo, sino se hubise inyectado $store usuriamos => $store = Store::where('id', $id)->with('warehouses')->first();
                "user" => $user_data,
                'access_token' => $accessToken,
            ];

            DB::commit();

            return responseOk($data, "El registro ha sido exitoso");

        } catch (\Illuminate\Database\QueryException $e) {

            DB::rollback();

            return responseError($e, "Error al crear la tienda " . $e->getMessage(), 400);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Devuelve los errores de validación
            return responseError($e, collect($e->errors())->flatten()->all(), 422);

            DB::rollback();

        } catch (\Exception $e) {
            // Otros errores inesperados
            return responseError($e,  'Ocurrió un error inesperado: ' . $e->getMessage()); //error 500

            DB::rollback();
        }
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show($store)
    {
        //

        Log::info($store);

        try {
            $store = Store::where('slug', $store)->first();
            return responseOk($store, "Se encontro la tienda");
        } catch (\Throwable $th) {
            //throw $th;
            Log::info($th);
            return responseError($th->getMessage(), "No se encontro la tienda");
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
