<?php

namespace App\Services\Dashboard\District;

use App\Models\District;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DistrictService
{

    /**
     * Buscar couriers relacionados a un store con filtro
     */
    public function search(string $search, int $perPage = 50)
    {

        $search = pluralToSingular($search);

        $districts = District::where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");

                        if (is_numeric($search)) {
                            $q->orWhere('district_id', (int) $search);
                        }
                    })
                    ->with('provinces.departments')
                    ->limit(25)
                    ->get();

        return $districts->paginate($perPage);

    }
}
