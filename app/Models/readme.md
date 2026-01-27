# Consideraciones importantes

La tabla users es la tabla base.
Los siguientes modelos dependen de user en relacion 1-1 (hasOne)

Customer
Supplier
Courier
Employee

Todos estos modelos comparten en comun los siguientes campos

name
address
email
store_id
phone
identity_id
document_number

entonces en cada modelo se le agrega protected $with = ['user']; para que cargue siempre la relacion con su user respectivo y no "olvidarnos"

osea quedaria asi

# ejemplo del codigo en el modelo correspondiente (Lo mismo para las cuatro models)

protected $with = ['user'];

public function user()
{
    return $this->belongsTo(User::class);
}

# como hacer los registros

1ero se crea el user
2do Este usuario creado se asigna a la tienda en cuestion
3ro se crea el registro del modelo correspondiente

# consideraciones importantes al hacer la obtencion de registros de los diferentes modelos desde el modelo Store

Store → User → Courier

Se debe usar hasManyThrough.

Entonces en el modelo Store quedaria asi

public function couriers()
{
    return $this->hasManyThrough(
        Courier::class,
        User::class,
        'store_id',   // FK en users
        'user_id',    // FK en couriers
        'id',         // PK en stores
        'id'          // PK en users
    );
}

# Importante

Los datos que se muestran esta en app\Http\Resources\FlatUserResource.php

Aqui nos devuelve

```php
        return [
            'id' => $this->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'address' => $user->address,
            'status' => $user->status,
            'phone' => $user->phone,
            'email' => $user->email,
            'document_number' => $user->document_number,
        ];

```

donde id es el id del modelo que hace join con user

# Ejemplo: Creación de usuario y asignación a tienda

Este ejemplo muestra el flujo completo para crear un usuario, asociarlo a una tienda y crear su registro de courier.

```php

// 1. Crear usuario

$user = User::create([
    'name'            => $validated['name'],
    'email'           => $validated['email'],
    'phone'           => $validated['phone'],
    'document_number' => $validated['document_number'],
    'identity_id'     => 2, // 2 = RUC
    'password'        => bcrypt($validated['document_number']),
]);

// 2. ASIGNAR USUARIO A LA TIENDA
$user->stores()->attach($store->id);

// 3. CREAR COURIER
$courier = $user->courier()->create(); //Crear el courier relacionado al user