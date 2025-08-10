<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthApiController extends Controller
{

        //
    public function login(Store $store, Request $request)
    {

        Log::info("------------- inicio login --------------------");

        try {


            //Primero validamos los datos
            $validator = Validator::make($request->all(), [
                'email' => 'email|required',
                'password' => 'required'
            ]);

            //Validamos los datos entrantes
            //si ha habido errores paralizamos todo con el return, sino seguimos con las siguientes lineas de codigo
            if ($validator->fails()) {

                return response()->json([
                    'status' => 400, //404 perfil no encontrado
                    'success' => false,
                    'message' => 'El formato de los datos es incorrecto',
                    'error' => [
                        'details' => [$validator->errors()]
                    ]
                ], 400);
            }

            Log::info("------------- datos validados --------------------");

            //recibimos los datos validados y se lo pasamos a la variable $validated

            //Si responde que la validacion fue correcta, negamos este valor para seguir en el siguiente bloque
            $validated = $validator->validated();

            if (!Auth::attempt($validated)) {

                return response()->json([
                    'status' => 404, //404 perfil no encontrado
                    'success' => false,
                    'message' => 'usuario o contrasena incorrecta',
                ], 404);

            }

            Log::info("------------- Inicio de sesion correcto --------------------");
            $user = Auth::user(); // Obtener el usuario autenticado
            Log::info($user);

            //como todo fue correcto y se paso las validaciones entonces creamos el token, ojo las lineas rojas es una alerta
            //pero todo funciona con normalidad

            try {

                


                    //User::hasStore($store->id); //verifica si el usuario pertenece a la tienda (la verificacion se hace en la tabla intermedia store_user)
                    $user_exists = $user->stores()->where('store_id', $store->id)->exists();
                    Log::info($user_exists);
                    if ($user_exists) { //verifica si el usuario pertenece a la tienda (la verificacion se hace en la tabla intermedia store_user)
                        // Crear el token de acceso

                        Log::info("------------- Usuario existe en la tienda --------------------");

                        $accessToken = $user->createToken('authToken')->accessToken;
    
                        // Cargar la relación 'addresses' y asegurarte de que el usuario existe
                        // $user = User::with(['addresses', 'addresses.district', 'addresses.district.province', 'addresses.district.province.department'])->findOrFail($user->id);
    
                        $user_data['name'] = $user->name;
                        $user_data['email'] = $user->email;
                        $user_data['phone'] = $user->phone;
    
                        // $delivery_methods = DeliveryMethod::all();
                        // $origins = Origin::all();
                        // $couriers = Courier::with('addresses')->get();
                        // $gateways = Gateway::all();
    
    
                        // $store['delivery_methods'] = $delivery_methods;
                        // $store['origins'] = $origins;
                        // $store['couriers'] = $couriers;
                        // $store['gateways'] = $gateways;
    
    
                        // $gateways = array_map(function($gateway){
                        //     $gateway['profile_photo_path'] = Storage::url($gateway['profile_photo_path']);
                        //     return $gateway;
                        // }, $gateways->toArray());
    
    
                        try {
                            $data = [
                                "store" => $store, //ojo usamos load y no with, porque $store ya se inyecto en el metodo, sino se hubise inyectado $store usuriamos => $store = Store::where('id', $id)->with('warehouses')->first();
                                "user" => $user_data,
                                'access_token' => $accessToken,
                            ];
    
                            //
    
    
                            // Log::info("imprimiendo el store");
                            // Log::info($store);
                            // Log::info('imprimiendo el store y sus warehouses');
                            // Log::info($store->with(['warehouses'])->get());
    
                            return response()->json([
                                'status' => 200,
                                'success' => true,
                                'message' => 'Inicio de sesion correcto',
                                'data' => $data
                            ]);
                        } catch (\Throwable $th) {
                            //throw $th;
                            return responseError($th, "El usuario no pertenece a la tienda en linea");
                        }
                        }else{
                            return responseError("", "El usuario existe pero no pertenece a esta tienda");
                        }

            } catch (\Throwable $th) {
                return responseError($th, "Ha fallado la obtencion del token");
            }

            // 'products' => $this->_products()->search('poliv')->get() //Esto viene de el ProductTrait

        } catch (\Throwable $th) {
            //throw $th;

            return responseError($th, "ha habido un error al iniciar sesion");
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
