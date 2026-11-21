<?php

namespace App\Services\Shopify\Catalog;

use App\Models\Product;
use Illuminate\Validation\ValidationException;

class CatalogPayload
{
    public function build(Product $product, string $status): array
    {
        $fail = fn ($message) => throw ValidationException::withMessages(['product' => $message]);
        $title = trim((string) $product->name);
        if ($title === '' || mb_strlen($title) > 255) $fail('El producto necesita un título de hasta 255 caracteres.');
        if ((int) $product->status !== 1) $fail('Solo se pueden enviar productos activos del ERP.');
        $product->loadMissing(['options' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'), 'variants.variant_option_values.optionValue']);
        $variants = $product->variants;
        if ($variants->isEmpty() || $variants->count() > 100) $fail('Selecciona productos con entre 1 y 100 variantes activas.');
        $options = $product->options;
        if ($options->count() > 3) $fail('Shopify admite hasta tres opciones por producto.');
        $names = $options->map(fn ($option) => trim($option->label ?: $option->name));
        if ($names->contains('') || $names->map(fn ($name) => mb_strtolower($name))->unique()->count() !== $names->count()) $fail('Revisa los nombres de las opciones del producto.');
        $optionValues = []; $combinations = []; $skus = []; $local = [];
        foreach ($variants as $variant) {
            $links = $variant->variant_option_values->keyBy('option_id');
            $values = [];
            foreach ($options as $index => $option) {
                $link = $links->get($option->id);
                if (!$link?->optionValue || $link->optionValue->option_id !== $option->id) $fail('Una variante no tiene una combinación válida de opciones.');
                $value = trim((string) $link->optionValue->value);
                if ($value === '' || mb_strlen($value) > 255) $fail('Las tallas y opciones deben tener un valor válido.');
                $values[] = ['optionName' => $names[$index], 'name' => $value];
                $optionValues[$index][$value] = true;
            }
            if ($links->count() !== $options->count()) $fail('Hay variantes asociadas a opciones archivadas. Revísalas antes de publicar.');
            if (!$values) {
                if ($variants->count() !== 1) $fail('Configura las tallas u opciones de las variantes.');
                $values = [['optionName' => 'Title', 'name' => 'Default Title']];
            }
            $key = json_encode($values, JSON_UNESCAPED_UNICODE);
            if (isset($combinations[$key])) $fail('Hay variantes con la misma combinación de tallas u opciones.');
            $combinations[$key] = true;
            $sku = trim((string) $variant->sku) ?: 'ERP-'.$product->store_id.'-'.$variant->id;
            if (isset($skus[$sku])) $fail('Las variantes tienen SKU repetidos. Corrígelos antes de publicar.');
            $skus[$sku] = true;
            $local[] = ['id' => $variant->id, 'sku' => $sku, 'optionValues' => $values];
        }
        $productOptions = $options->map(fn ($option, $index) => ['name' => $names[$index], 'position' => $index + 1,
            'values' => array_map(fn ($value) => ['name' => (string) $value], array_keys($optionValues[$index] ?? []))])->values()->all();
        if (!$productOptions) $productOptions = [['name' => 'Title', 'position' => 1, 'values' => [['name' => 'Default Title']]]];
        $input = ['title' => $title, 'status' => $status, 'productOptions' => $productOptions,
            'variants' => array_map(fn ($variant) => ['sku' => $variant['sku'], 'optionValues' => $variant['optionValues']], $local)];
        return ['input' => $input, 'variants' => $local, 'hash' => hash('sha256', json_encode([$input, array_column($local, 'id')], JSON_UNESCAPED_UNICODE))];
    }
}
