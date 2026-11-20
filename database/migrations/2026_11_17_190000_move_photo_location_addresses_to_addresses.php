<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const OWNER = 'App\\Models\\PhotoLocation';
    public function up(): void
    {
        // Widen shared fields before copying existing venue addresses; never truncate them.
        Schema::table('addresses', function (Blueprint $table) {
            $table->string('primary', 500)->nullable()->change();
            $table->string('url_maps', 1000)->nullable()->change();
            $table->unsignedBigInteger('district_id')->nullable()->change();
        });
        if (!Schema::hasColumn('addresses', 'city')) Schema::table('addresses', fn (Blueprint $table) => $table->string('city')->nullable());
        $districts = DB::table('districts')->get(['id', 'name'])->groupBy(fn ($district) => mb_strtolower(trim($district->name)));
        DB::transaction(function () use ($districts) {
            foreach (DB::table('photo_locations')->orderBy('id')->get() as $location) {
                $old = ['primary' => $location->address, 'district' => $location->district, 'city' => $location->city];
                if (!array_filter($old, fn ($text) => trim((string) $text) !== '')) continue;
                $query = DB::table('addresses')->where('addressable_type', self::OWNER)->where('addressable_id', $location->id);
                if ($query->exists()) {
                    $existing = $query->get();
                    if ($existing->count() === 1 && $existing->first()->primary === $location->address && $existing->first()->city === $location->city) continue;
                    throw new RuntimeException('La locación '.$location->id.' ya tiene una dirección: revisar antes de migrar.');
                }
                $matches = $districts->get(mb_strtolower(trim((string) $location->district)), collect());
                $district = $matches->count() === 1 ? $matches->first()->id : null;
                DB::table('addresses')->insert(['addressable_type' => self::OWNER, 'addressable_id' => $location->id,
                    'name' => $location->name, 'primary' => $location->address, 'city' => $location->city, 'district_id' => $district,
                    'references' => !$district && trim((string) $location->district) !== '' ? 'Distrito/zona anterior: '.$location->district : null,
                    'type' => 'otro', 'purpose' => 'photo_location', 'is_default' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        });
        Schema::table('photo_locations', fn (Blueprint $table) => $table->dropColumn(['address', 'district', 'city']));
    }
    public function down(): void
    {
        Schema::table('photo_locations', function (Blueprint $table) {
            $table->string('address', 500)->nullable(); $table->string('district', 120)->nullable(); $table->string('city', 120)->nullable();
        });
        foreach (DB::table('addresses')->where('addressable_type', self::OWNER)->get() as $address) {
            $district = $address->district_id ? DB::table('districts')->where('id', $address->district_id)->value('name') : null;
            if (!$district && str_starts_with((string) $address->references, 'Distrito/zona anterior: ')) $district = substr($address->references, strlen('Distrito/zona anterior: '));
            DB::table('photo_locations')->where('id', $address->addressable_id)->update(['address' => $address->primary, 'district' => $district, 'city' => $address->city]);
        }
        // Keep address records and widened columns when rolling back; do not discard newer details.
    }
};
