<?php


// /dev/sdd3: BLOCK_SIZE="512" UUID="92F83DA7F83D8A8D" TYPE="ntfs" PARTUUID="cc99c697-9ae0-487a-96d2-c7d59f7eb4ce" //Personales
// /dev/sdd4: BLOCK_SIZE="512" UUID="82B0939EB093976F" TYPE="ntfs" PARTUUID="f0349bbf-4c3d-4a2a-b866-b314dedd1475"
// /dev/sdb2: LABEL="Nuevo vol" BLOCK_SIZE="512" UUID="0600535F005354B1" TYPE="ntfs" PARTLABEL="Basic data partition" PARTUUID="7fbf5e21-053d-4488-85e3-7427572c385a" //Disco 1TB 3.5
// /dev/sde1: BLOCK_SIZE="512" UUID="5ADE318166755837" TYPE="ntfs" PARTUUID="52b61e2c-dec4-476f-a1ce-5364804b8393" //Disco 4tb, 3.5
// /dev/sdc1: LABEL="Nuevo vol" BLOCK_SIZE="512" UUID="5C76824F768229BA" TYPE="ntfs" PARTUUID="7d2b233a-01" //Disco 2


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
