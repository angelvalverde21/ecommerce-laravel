<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prices', function (Blueprint $table) {
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('price', 14, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);
        });

        DB::table('prices')->select('id', 'value')->orderBy('id')->chunkById(200, function ($prices) {
            foreach ($prices as $price) {
                $value = is_numeric($price->value) ? round((float) $price->value, 2) : 0;
                DB::table('prices')->where('id', $price->id)->update(['price' => $value, 'total' => $value]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('prices', fn (Blueprint $table) => $table->dropColumn(['quantity', 'price', 'total']));
    }
};
