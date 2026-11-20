<?php
namespace App\Services\PhotoSessions;

use App\Models\PhotoLocation;
use Illuminate\Validation\Rule;

class PhotoLocationAddresses
{
    public const RELATION = 'address.district.province.department';

    public function rules(string $prefix = ''): array
    {
        return [
            $prefix.'primary' => 'nullable|string|max:500', $prefix.'secondary' => 'nullable|string|max:255',
            $prefix.'references' => 'nullable|string|max:255', $prefix.'district_id' => ['nullable', 'integer', Rule::exists('districts', 'id')],
            $prefix.'url_maps' => 'nullable|url:http,https|max:1000',
            $prefix.'latitud' => 'nullable|numeric|between:-90,90', $prefix.'longitud' => 'nullable|numeric|between:-180,180',
            $prefix.'city' => 'nullable|string|max:255',
        ];
    }

    /** Call inside the transaction holding the venue row lock: one canonical address. */
    public function save(PhotoLocation $location, ?array $data): void
    {
        if ($data === null || !array_filter($data, fn ($value) => $value !== null && $value !== '')) {
            $location->address()->delete(); $location->unsetRelation('address'); return;
        }
        $values = array_intersect_key($data, $this->rules());
        $address = $location->address()->first();
        $values = [...$values, 'name' => $location->name, 'purpose' => 'photo_location', 'type' => 'otro', 'is_default' => true];
        if ($address) $address->update($values); else $location->address()->create($values);
        $location->unsetRelation('address');
    }

    public function present(PhotoLocation $location): array
    {
        $location->loadMissing(self::RELATION);
        $address = $location->address;
        $display = implode(' · ', array_filter([$address?->primary, $address?->secondary]));
        return [...$location->toArray(), 'display_address' => $display ?: null,
            'district_name' => $address?->district?->name,
            'city_name' => $address?->city ?: $address?->district?->province?->name];
    }
}
