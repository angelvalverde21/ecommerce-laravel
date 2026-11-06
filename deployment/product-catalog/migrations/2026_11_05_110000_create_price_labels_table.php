<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_labels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->timestamps();
        });
        Schema::table('prices', function (Blueprint $table) {
            $table->foreignId('price_label_id')->nullable()->constrained('price_labels')->restrictOnDelete();
        });

        DB::table('prices')->select('id', 'name')->orderBy('id')->chunkById(200, function ($prices) {
            foreach ($prices as $price) {
                $name = Str::squish($price->name);
                $code = Str::slug($name, '_') ?: 'precio_sin_nombre_'.$price->id;
                $label = DB::table('price_labels')->where('code', $code)->first();
                $labelId = $label?->id ?? DB::table('price_labels')->insertGetId([
                    'name' => $name ?: 'Precio sin nombre '.$price->id, 'code' => $code,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table('prices')->where('id', $price->id)->update(['price_label_id' => $labelId]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('prices', fn (Blueprint $table) => $table->dropConstrainedForeignId('price_label_id'));
        Schema::dropIfExists('price_labels');
    }
};
