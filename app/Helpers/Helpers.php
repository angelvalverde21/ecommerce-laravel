<?php

use Illuminate\Support\Str;

function pluralToSingular($word)
{
    if (preg_match('/(ces)$/', $word)) {
        return preg_replace('/ces$/', 'z', $word); // peces → pez, luces → luz
    }
    if (preg_match('/(iones)$/', $word)) {
        return preg_replace('/iones$/', 'ión', $word); // acciones → acción
    }
    if (preg_match('/(es)$/', $word) && strlen($word) > 3) {
        return preg_replace('/es$/', '', $word); // canciones → canción, mares → mar
    }
    if (preg_match('/(os|as)$/', $word)) {
        return substr($word, 0, -1); // gatos → gato, casas → casa
    }
    return $word; // Si no cumple ninguna regla, devolver igual
}


if (! function_exists('match_courier')) {

    function match_courier(string $text, $patterns): ?string
    {

        // Ordenamos para priorizar coincidencias más largas
        usort($patterns, fn($a, $b) => strlen($b) <=> strlen($a));

        $normalized = Str::lower($text);

        foreach ($patterns as $pattern) {
            if (Str::contains($normalized, Str::lower($pattern))) {
                return $pattern;
            }
        }

        return null;
    }
}

if (! function_exists('generate_combinations2')) {

    function generate_combinations2($options, $option_values)
    {

        // Paso 1: Mapear option_values a un array asociativo para búsqueda rápida (O(1))
        // en lugar de usar un bucle anidado lento (O(n)) dentro del bucle de options.
        $values_map = [];

        foreach ($option_values as $value_item) {
            $values_map[$value_item['option_id']][] = $value_item['value'];
        }

        // Paso 2: Construir la estructura principal iterando sobre 'options'
        $structured_attributes = [];

        foreach ($options as $option) {
            $option_id = $option['id'];
            $option_name = $option['name'];

            // Obtenemos los valores asociados usando el ID del mapa que creamos
            if (isset($values_map[$option_id])) {
                $structured_attributes[$option_name] = $values_map[$option_id];
            }
        }

        // Paso 3: Aplicar el algoritmo de producto cartesiano a la estructura final
        $result = [[]];

        foreach ($structured_attributes as $attribute_name => $attribute_values) {
            $append = [];
            foreach ($result as $combination_so_far) {
                foreach ($attribute_values as $value) {
                    $new_combination = $combination_so_far;
                    $new_combination[$attribute_name] = $value;
                    $append[] = $new_combination;
                }
            }
            $result = $append;
        }

        return $result;
    }
}

if (! function_exists('generate_combinations')) {

    function generate_combinations($options, $option_values)
    {
        //1 Mapa rápido: option_id → option + values
        $values_map = [];

        foreach ($option_values as $value) {
            $values_map[$value->option_id][] = [
                'option_value_id' => $value->id,
                'value'           => $value->value,
            ];
        }

        //2 Estructura base respetando el orden de options
        $structured = [];

        foreach ($options as $option) {
            if (!isset($values_map[$option->id])) {
                continue;
            }

            $structured[] = [
                'option_id'   => $option->id,
                'option_name' => $option->name,
                'values'      => $values_map[$option->id],
            ];
        }

        //3 Producto cartesiano (CORRECTO)
        $result = [[]];

        foreach ($structured as $option) {
            $append = [];

            foreach ($result as $combination) {
                foreach ($option['values'] as $value) {
                    $append[] = array_merge($combination, [[
                        'option_id'        => $option['option_id'],
                        'option_name'      => $option['option_name'],
                        'option_value_id'  => $value['option_value_id'],
                        'value'            => $value['value'],
                    ]]);
                }
            }

            $result = $append;
        }

        return $result;
    }
}
