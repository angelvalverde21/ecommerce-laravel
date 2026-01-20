<?php

use App\Models\OptionValue;
use App\Models\Product;
use App\Models\Variant;
use App\Models\VariantOptionValue;
use Illuminate\Support\Facades\Log;
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

/*

devuelve (nxm) array's donde n es el cardinal de options y m de option_values respectivamente

Ejemplo de un elemento

array (
    0 => 
    array (
      'option_id' => 1,
      'option_name' => 'size',
      'option_value_id' => 9,
      'value' => 'XL',
    ),
    1 => 
    array (
      'option_id' => 2,
      'option_name' => 'color',
      'option_value_id' => 5,
      'value' => 'NEGRO',
    ),
  )


*/
if (! function_exists('combinations')) {
  function combinations($options): array
  {
    // 1. Normalizar estructura respetando el orden de options
    $structured = [];

    foreach ($options as $option) {

      if ($option->option_values->isEmpty()) {
        continue;
      }

      $structured[] = [
        'option_id'     => $option->id,
        'option_name'   => $option->name,
        'option_values' => $option->option_values->map(function ($value) {
          return [
            'option_value_id' => $value->id,
            'value'           => $value->value,
          ];
        })->toArray(),
      ];
    }

    // 2. Producto cartesiano
    $result = [[]];

    foreach ($structured as $option) {
      $append = [];

      foreach ($result as $combination) {
        foreach ($option['option_values'] as $option_value) {
          $append[] = array_merge($combination, [[
            'option_id'        => $option['option_id'],
            'option_name'      => $option['option_name'],
            'option_value_id'  => $option_value['option_value_id'],
            'option_value'     => $option_value['value'],
          ]]);
        }
      }

      $result = $append;
    }

    return $result;
  }
}


if (! function_exists('generateCombinations')) {

  /**
   * Genera el producto cartesiano de options y options_values (sus valores de options).
   *
   * @param iterable $options
   * @param iterable $option_values
   * @return array
   */

  function generateCombinations($options, $option_values): array
  {
    //1 Mapa rápido: option_id → option + values
    $values_map = [];

    foreach ($option_values as $option_value) {
      $values_map[$option_value->option_id][] = [
        'option_value_id' => $option_value->id,
        'value'           => $option_value->value,
      ];
    }

    //2 Estructura base respetando el orden de options
    $structured = [];

    foreach ($options as $option) {

      if (!isset($values_map[$option->id])) {
        continue;
      }

      $structured[] = [
        'option_id'     => $option->id,
        'option_name'   => $option->name,
        'option_values' => $values_map[$option->id],
      ];
    }

    //3 Producto cartesiano (CORRECTO)
    $result = [[]];

    foreach ($structured as $option) {
      $append = [];

      foreach ($result as $combination) {
        foreach ($option['option_values'] as $option_value) {
          $append[] = array_merge($combination, [[
            'option_id'        => $option['option_id'],
            'option_name'      => $option['option_name'],
            'option_value_id'  => $option_value['option_value_id'],
            'option_value'     => $option_value['value'],
          ]]);
        }
      }

      $result = $append;
    }

    return $result;
  }
}

//Ejemplo completo de lo que devuelve cuando option son: Size (S,M,L y XL) y Color (Rosado y Negro) el producto nxm seria 8

/*
array (
  0 => 
  array (
    0 => 
    array (
      'option_id' => 1,
      'option_name' => 'size',
      'option_value_id' => 1,
      'value' => 'S',
    ),
    1 => 
    array (
      'option_id' => 2,
      'option_name' => 'color',
      'option_value_id' => 4,
      'value' => 'ROSADO',
    ),
  ),
  1 => 
  array (
    0 => 
    array (
      'option_id' => 1,
      'option_name' => 'size',
      'option_value_id' => 1,
      'value' => 'S',
    ),
    1 => 
    array (
      'option_id' => 2,
      'option_name' => 'color',
      'option_value_id' => 5,
      'value' => 'NEGRO',
    ),
  ),
  2 => 
  array (
    0 => 
    array (
      'option_id' => 1,
      'option_name' => 'size',
      'option_value_id' => 2,
      'value' => 'M',
    ),
    1 => 
    array (
      'option_id' => 2,
      'option_name' => 'color',
      'option_value_id' => 4,
      'value' => 'ROSADO',
    ),
  ),
  3 => 
  array (
    0 => 
    array (
      'option_id' => 1,
      'option_name' => 'size',
      'option_value_id' => 2,
      'value' => 'M',
    ),
    1 => 
    array (
      'option_id' => 2,
      'option_name' => 'color',
      'option_value_id' => 5,
      'value' => 'NEGRO',
    ),
  ),
  4 => 
  array (
    0 => 
    array (
      'option_id' => 1,
      'option_name' => 'size',
      'option_value_id' => 3,
      'value' => 'L',
    ),
    1 => 
    array (
      'option_id' => 2,
      'option_name' => 'color',
      'option_value_id' => 4,
      'value' => 'ROSADO',
    ),
  ),
  5 => 
  array (
    0 => 
    array (
      'option_id' => 1,
      'option_name' => 'size',
      'option_value_id' => 3,
      'value' => 'L',
    ),
    1 => 
    array (
      'option_id' => 2,
      'option_name' => 'color',
      'option_value_id' => 5,
      'value' => 'NEGRO',
    ),
  ),
  6 => 
  array (
    0 => 
    array (
      'option_id' => 1,
      'option_name' => 'size',
      'option_value_id' => 9,
      'value' => 'XL',
    ),
    1 => 
    array (
      'option_id' => 2,
      'option_name' => 'color',
      'option_value_id' => 4,
      'value' => 'ROSADO',
    ),
  ),
  7 => 
  array (
    0 => 
    array (
      'option_id' => 1,
      'option_name' => 'size',
      'option_value_id' => 9,
      'value' => 'XL',
    ),
    1 => 
    array (
      'option_id' => 2,
      'option_name' => 'color',
      'option_value_id' => 5,
      'value' => 'NEGRO',
    ),
  ),
)  
*/

function cartesian($options, $option_values): array
{
  // 1. Agrupar option_values por option_id
  $valuesMap = [];

  foreach ($option_values as $ov) {
    $valuesMap[$ov->option_id][] = [
      'option_value_id' => $ov->id,
      'value'           => $ov->value,
    ];
  }

  // 2. Respetar orden de options
  $structured = [];

  foreach ($options as $option) {
    if (!isset($valuesMap[$option->id])) {
      continue;
    }

    $structured[] = [
      'option_id'     => $option->id,
      'option_name'   => $option->name,
      'option_values' => $valuesMap[$option->id],
    ];
  }

  // 3. Producto cartesiano
  $result = [[]];

  foreach ($structured as $option) {
    $append = [];

    foreach ($result as $combination) {
      foreach ($option['option_values'] as $ov) {
        $append[] = array_merge($combination, [[
          'option_id'       => $option['option_id'],
          'option_name'     => $option['option_name'],
          'option_value_id' => $ov['option_value_id'],
          'option_value'    => $ov['value'],
        ]]);
      }
    }

    $result = $append;
  }

  return $result;
}

function getVariant(array $combinations, int $product_id): array
{
  $variantsBySku = [];

  foreach ($combinations as $combination) {

    $sku = strtoupper(
      implode(
        '-',
        array_map(
          fn($item) => $item['option_value_id'] . substr($item['option_value'], 0, 3),
          $combination
        )
      )
    );

    $variantsBySku[$sku] = [
      'row' => [
        'product_id' => $product_id,
        'sku'        => $sku,
        'price'      => 0,
        'stock'      => 0,
        'created_at' => now(),
        'updated_at' => now(),
      ],
      'combination' => $combination,
    ];
  }

  return $variantsBySku;
}

function updateSkus2($optionValue)
{
  /*
        |--------------------------------------------------------------------------
        | 1. Cargar relaciones necesarias
        |--------------------------------------------------------------------------
        */

  Log::info('Inicio del observer', [
    'id' => $optionValue->id,
  ]);

  $optionValue->load('option.product.options');

  $product = $optionValue->option->product;
  $options = $product->options;

  if ($options->isEmpty()) {
    return;
  }

  /*
        |--------------------------------------------------------------------------
        | 2. Obtener TODOS los option_values (query plana por performance)
        |--------------------------------------------------------------------------
        */

  $optionIds = $options->modelKeys();

  $optionValues = OptionValue::whereIn('option_id', $optionIds)->get();

  if ($optionValues->isEmpty()) {
    return;
  }

  /*
        |--------------------------------------------------------------------------
        | 3. Generar el producto cartesiano de options y option_values
        |--------------------------------------------------------------------------
        */

  $combinations = cartesian($options, $optionValues);

  if (empty($combinations)) {
    return;
  }

  /*
        |--------------------------------------------------------------------------
        | 4. Generar mapa de variantes (SKU como fuente única)
        |--------------------------------------------------------------------------
        */

  $variantMap = getVariant($combinations, $product->id);

  if (empty($variantMap)) {
    return;
  }

  /*
        |--------------------------------------------------------------------------
        | 5. Insertar solo nuevas variantes
        |--------------------------------------------------------------------------
        */

  Variant::insertOrIgnore(
    array_column($variantMap, 'row')
  );

  /*
        |--------------------------------------------------------------------------
        | 6. Obtener variantes existentes + nuevas
        |--------------------------------------------------------------------------
        */

  $variants = Variant::where('product_id', $product->id)
    ->whereIn('sku', array_keys($variantMap))
    ->get()
    ->keyBy('sku');

  /*
        |--------------------------------------------------------------------------
        | 7. Preparar pivote variant_option_value
        |--------------------------------------------------------------------------
        */

  $pivotRows = [];

  foreach ($variants as $sku => $variant) {
    foreach ($variantMap[$sku]['combination'] as $item) {
      $pivotRows[] = [
        'variant_id'      => $variant->id,
        'option_id'       => $item['option_id'],
        'option_value_id' => $item['option_value_id'],
      ];
    }
  }

  /*
        |--------------------------------------------------------------------------
        | 8. Insertar pivote (solo nuevos)
        |--------------------------------------------------------------------------
        */

  VariantOptionValue::insertOrIgnore($pivotRows);

  Log::info('Fin del observer', $pivotRows);
}


function UpdateSkus(Product $product)
{

  $product->load('options');

  Log::info($product->options);

  $optionIds = $product->options->modelKeys();

  $optionValues = OptionValue::whereIn('option_id', $optionIds)->get();

  $combinations = cartesian($product->options, $optionValues);

  if (empty($combinations)) {
    Log::info("combinaciones vacias");
    return;
  }

  /*
        |--------------------------------------------------------------------------
        | 4. Generar mapa de variantes (SKU como fuente única)
        |--------------------------------------------------------------------------
        */

  $variantMap = getVariant($combinations, $product->id);


  Log::info($variantMap);

  if (empty($variantMap)) {
    return;
  }

  /*
        |--------------------------------------------------------------------------
        | 5. Insertar solo nuevas variantes
        |--------------------------------------------------------------------------
        */

  Variant::insertOrIgnore(
    array_column($variantMap, 'row')
  );

  /*
        |--------------------------------------------------------------------------
        | 6. Obtener variantes existentes + nuevas
        |--------------------------------------------------------------------------
        */

  $variants = Variant::where('product_id', $product->id)
    ->whereIn('sku', array_keys($variantMap))
    ->get()
    ->keyBy('sku');

  /*
        |--------------------------------------------------------------------------
        | 7. Preparar pivote variant_option_value
        |--------------------------------------------------------------------------
        */

  $pivotRows = [];

  foreach ($variants as $sku => $variant) {
    foreach ($variantMap[$sku]['combination'] as $item) {
      $pivotRows[] = [
        'variant_id'      => $variant->id,
        'option_id'       => $item['option_id'],
        'option_value_id' => $item['option_value_id'],
      ];
    }
  }

  /*
        |--------------------------------------------------------------------------
        | 8. Insertar pivote (solo nuevos)
        |--------------------------------------------------------------------------
        */

  VariantOptionValue::insertOrIgnore($pivotRows);

  Log::info('Fin del observer', $pivotRows);
}
