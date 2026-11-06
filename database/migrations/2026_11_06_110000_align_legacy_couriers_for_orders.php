<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Current installations already have these fields. Upgrade only the legacy schema.
        if (Schema::hasColumn('couriers', 'name')) {
            return;
        }

        Schema::table('couriers', function (Blueprint $table) {
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->boolean('is_cod')->default(false);
            $table->boolean('freight_collect')->default(false);
            $table->enum('delivery_type', ['agency', 'express', 'store_pickup'])->default('agency');
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        DB::table('couriers')->orderBy('id')->each(function (object $courier) {
            $user = DB::table('users')->find($courier->user_id);

            DB::table('couriers')->where('id', $courier->id)->update([
                'name' => $user?->name ?? 'Courier #' . $courier->id,
                'phone' => $user?->phone,
                'status' => $user?->status ?? 1,
                'is_cod' => $courier->is_cash_on_delivery,
                'freight_collect' => $courier->is_freight_collect,
                'delivery_type' => $courier->is_express_shipping ? 'express' : 'agency',
            ]);
        });

        Schema::table('couriers', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        // Preserve couriers created without a legacy user and their order/address references.
        // Restoring the legacy schema requires restoring the backup taken before this upgrade.
    }
};
