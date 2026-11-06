<?php

namespace App\Services\Dashboard\Product;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductDuplicationService
{
    public function duplicate(Store $store, int $productId, string $name): Product
    {
        $files = [];
        try {
            return DB::transaction(function () use ($store, $productId, $name, &$files) {
                $source = $store->products()->lockForUpdate()->findOrFail($productId);
                $source->load(['allOptions.option_values', 'attributes.attribute_values', 'prices', 'images', 'colors.sizes', 'sizes.prices']);
                $copy = $source->replicate();
                $copy->setRelations([]);
                $copy->name = $name;
                $copy->slug = Str::limit(Str::slug($name) ?: 'producto', 210, '').'-'.Str::uuid();
                $copy->sku = $source->sku ? 'P-'.Str::uuid() : null;
                // External synchronization must never overwrite the new, independent product.
                $copy->origin = null;
                $copy->online_store_url = null;
                $copy->barcode = null;
                $copy->quantity = 0;
                $copy->save();

                $options = [];
                $values = [];
                $valueNames = [];
                foreach ($source->allOptions as $option) {
                    $newOption = $option->replicate(['active_name'])->setRelations([]);
                    $newOption->parent_id = null;
                    // Observers normally generate variants; here we copy the exact existing set.
                    $copy->allOptions()->saveQuietly($newOption);
                    $options[$option->id] = $newOption->id;
                    foreach ($option->option_values as $value) {
                        $newValue = $value->replicate();
                        $newOption->option_values()->saveQuietly($newValue);
                        $values[$value->id] = $newValue->id;
                        $valueNames[$value->id] = $value->value;
                    }
                }
                foreach ($source->allOptions as $option) {
                    if ($option->parent_id !== null) {
                        if (!isset($options[$option->parent_id])) {
                            throw ValidationException::withMessages(['product' => 'Una opción depende de otro producto. Revisa sus opciones antes de duplicar.']);
                        }
                        $copy->allOptions()->whereKey($options[$option->id])->update(['parent_id' => $options[$option->parent_id]]);
                    }
                }

                // Include inactive variants too, retaining their status.
                $usedSkus = [];
                $optionOrder = $source->allOptions->pluck('id')->flip();
                foreach ($source->variants()->withoutGlobalScopes()->with('variant_option_values')->get() as $variant) {
                    $combination = [];
                    foreach ($variant->variant_option_values->sortBy(fn ($link) => $optionOrder[$link->option_id] ?? PHP_INT_MAX) as $link) {
                        if (!isset($options[$link->option_id], $values[$link->option_value_id])) {
                            throw ValidationException::withMessages(['product' => 'Una variante tiene opciones ajenas al producto. Revisa sus opciones antes de duplicar.']);
                        }
                        $combination[] = [
                            'option_id' => $options[$link->option_id],
                            'option_value_id' => $values[$link->option_value_id],
                            'option_value' => $valueNames[$link->option_value_id],
                        ];
                    }
                    $newVariant = $variant->replicate()->setRelations([]);
                    $newVariant->sku = 'V-'.Str::uuid();
                    if ($combination && (int) $variant->status === 1) {
                        $sku = array_key_first(getVariant([$combination], $copy->id));
                        if (!isset($usedSkus[$sku])) { $newVariant->sku = $sku; }
                    }
                    $usedSkus[$newVariant->sku] = true;
                    $newVariant->stock = 0;
                    $copy->variants()->save($newVariant);
                    foreach ($combination as $link) {
                        $newVariant->variant_option_values()->create([
                            'option_id' => $link['option_id'],
                            'option_value_id' => $link['option_value_id'],
                        ]);
                    }
                }

                foreach ($source->attributes as $attribute) {
                    $newAttribute = $attribute->replicate()->setRelations([]);
                    $copy->attributes()->save($newAttribute);
                    foreach ($attribute->attribute_values as $value) {
                        $newValue = $value->replicate();
                        $newValue->timestamps = false;
                        $newAttribute->attribute_values()->save($newValue);
                    }
                }
                foreach ($source->prices as $price) { $copy->prices()->save($price->replicate()); }

                // Preserve legacy sizes/colors and their own prices without sharing IDs.
                $sizes = [];
                foreach ($source->sizes as $size) {
                    $newSize = $size->replicate()->setRelations([]);
                    $newSize->sku = $size->sku ? 'S-'.Str::uuid() : null;
                    $newSize->quantity = 0;
                    $newSize->origin = null;
                    $copy->sizes()->save($newSize);
                    $sizes[$size->id] = $newSize->id;
                    foreach ($size->prices as $price) { $newSize->prices()->save($price->replicate()); }
                }
                foreach ($source->colors as $color) {
                    $newColor = $color->replicate()->setRelations([]);
                    $newColor->sku = $color->sku ? 'C-'.Str::uuid() : null;
                    $newColor->quantity = 0;
                    $newColor->origin = null;
                    $copy->colors()->save($newColor);
                    foreach (DB::table('color_size')->where('color_id', $color->id)->get() as $pivot) {
                        if (!isset($sizes[$pivot->size_id])) { continue; }
                        $attributes = (array) $pivot;
                        unset($attributes['id']);
                        $attributes['color_id'] = $newColor->id;
                        $attributes['size_id'] = $sizes[$pivot->size_id];
                        $attributes['sku'] = $pivot->sku ? 'CS-'.Str::uuid() : null;
                        $attributes['quantity'] = 0;
                        $attributes['origin'] = null;
                        DB::table('color_size')->insert($attributes);
                    }
                }

                $copier = new ProductImageDuplicator();
                foreach ($source->images as $image) {
                    $newImage = $image->replicate();
                    foreach (['thumbnail', 'medium', 'large'] as $field) {
                        $newImage->$field = $copier->copy($image->$field, $copy->id, $files);
                    }
                    $copy->images()->save($newImage);
                }

                return $copy->load(['category', 'image', 'options.option_values', 'variants.variant_option_values.optionValue', 'prices']);
            });
        } catch (\Throwable $exception) {
            // File storage does not participate in the SQL transaction.
            Storage::disk('public')->delete($files);
            throw $exception;
        }
    }
}
