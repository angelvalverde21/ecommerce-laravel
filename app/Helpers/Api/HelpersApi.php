<?php

function responseOk($data = null, $message = "Datos obtenidos con exito")
{

    $array = [
        'status' => 200,
        'success' => true,
        'message' => $message,
        'data' => $data
    ];

    return response()->json($array);
}

function responseError($error = "", $message = "Error al obtener los datos", $status = 500)
{
    return response()->json([
        'status' => $status, //404 perfil no encontrado
        'success' => false,
        'error' => $error,
        'message' => $message,
    ], 500);
}