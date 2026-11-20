<?php
namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\{PhotoLocation, Store};
use App\Services\PhotoSessions\PhotoLocationAddresses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Adapter for the shared addresses API, with photo-module permissions and scope. */
class PhotoLocationAddressDashboardController extends Controller
{
    public function __construct(private PhotoLocationAddresses $addresses) {}
    private function location(Store $store, Request $request): PhotoLocation
    {
        $user = $request->user();
        abort_unless($user && $user->hasAnyRole(['master', 'ceo', 'admin', 'marketing']), 403);
        abort_unless($user->hasRole('master') || $user->stores()->whereKey($store->id)->exists(), 403);
        $data = $request->validate(['addressable_id' => 'required|integer|min:1']);
        return PhotoLocation::where('store_id', $store->id)->lockForUpdate()->findOrFail($data['addressable_id']);
    }
    public function store(Store $store, Request $request)
    {
        return DB::transaction(function () use ($store, $request) {
            $location = $this->location($store, $request);
            if ($location->address()->exists()) throw ValidationException::withMessages(['addressable_id' => 'Esta locación ya tiene una dirección. Edita la existente.']);
            $data = $request->validate($this->addresses->rules());
            if (!array_filter($data, fn ($value) => $value !== null && $value !== '')) throw ValidationException::withMessages(['primary' => 'Ingresa los datos de la dirección.']);
            $this->addresses->save($location, $data);
            return responseOk($location->address()->with('district.province.department')->first(), 'Dirección creada.');
        });
    }
    public function update(Store $store, Request $request, int $address_id)
    {
        return DB::transaction(function () use ($store, $request, $address_id) {
            $location = $this->location($store, $request);
            $location->address()->whereKey($address_id)->firstOrFail();
            $this->addresses->save($location, $request->validate($this->addresses->rules()));
            return responseOk($location->address()->with('district.province.department')->first(), 'Dirección actualizada.');
        });
    }
    public function show(Store $store, Request $request, int $address_id)
    {
        return DB::transaction(function () use ($store, $request, $address_id) {
            $location = $this->location($store, $request);
            return responseOk($location->address()->with('district.province.department')->whereKey($address_id)->firstOrFail());
        });
    }
}
