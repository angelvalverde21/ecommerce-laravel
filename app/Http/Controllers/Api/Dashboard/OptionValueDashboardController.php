<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Option;
use App\Models\OptionValue;
use App\Models\store;
use App\Models\Variant;
use App\Models\VariantOptionValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OptionValueDashboardController extends Controller
{
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
    public function store(Store $store, Request $request) //create
    {

        $resp = $request->all();

        Log::info('creando OptionValue');

        // $rules = $this->rules;

        // $this->validate($rules);

        try {

            DB::beginTransaction();

            $optionValue = OptionValue::create(
                [
                    'option_id' => $resp['option_id'],
                    'value' => $resp['value'],
                    'sort_order' => 1,
                ]
            );

            $optionValue->load('option.product');

            $option  = $optionValue->option;
            $product = $option->product;

            $options = $product->options;

            Log::info($options);
            $option_values = $option_values = OptionValue::whereIn('option_id', $options->pluck('id'))->get();
            Log::info($option_values);

            $combinations = generate_combinations($options, $option_values);

            Log::info($combinations);

            $variantRows = [];
            $combinationMap = [];

            foreach ($combinations as $combination) {

                $temp_sku =  array_map(
                                fn($item) => $item['option_value_id'] . '-IA' . substr($item['value'], 0, 3),
                                $combination
                );

                $sku = strtoupper(
                    substr($product->name, 0, 3) . '-' . implode('-', $temp_sku)
                );

                $variantRows[] = [
                    'product_id' => $product->id,
                    'sku' => $sku,
                    'price' => 0,
                    'stock' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // 🔑 guardamos la combinación
                $combinationMap[$sku] = $combination;
            }

            Variant::insertOrIgnore($variantRows);

            // Variant::insertOrIgnore($skuInsert);

            //Obtencion de las variantes creadas

            $variants = Variant::where('product_id', $product->id)
                ->whereIn('sku', array_keys($combinationMap))
                ->get()
                ->keyBy('sku');

            //Obtencion de la tabla pivote

            $pivotRows = [];

            foreach ($variants as $sku => $variant) {

                $combination = $combinationMap[$sku];

                foreach ($combination as $item) {
                    $pivotRows[] = [
                        'variant_id'      => $variant->id,
                        'option_id'       => $item['option_id'],
                        'option_value_id' => $item['option_value_id'],
                    ];
                }
            }

            VariantOptionValue::insertOrIgnore($pivotRows);



            // Log::info($variants);

            // foreach ($product->options as $option) {

            //     # code...
            //     foreach ($option->option_values as $option_value) {



            //         Log::info("creando sku");
            //         Log::info($sku);

            //         Variant::create(
            //             [
            //                 'product_id' => $product->id,
            //                 'sku' => $sku,
            //                 'price' => 0,
            //                 'stock' => 0,
            //             ]
            //         );

            //     }
            // }

            DB::commit();

            return responseOk($optionValue->load('option'), "se agrego correctamente el optionValue en create con sus opciones");

        } catch (\Throwable $th) {

            DB::rollback();
            Log::info($th);

            return responseError($th, "Ha sucedido un error interno al crear el optionValue");
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(store $store)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(store $store)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, store $store)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(store $store)
    {
        //
    }
}
