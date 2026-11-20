<?php
namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\{PhotoLocation, Store};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PhotoSessions\PhotoLocationAddresses;

class PhotoLocationDashboardController extends Controller
{
    public function __construct(private PhotoLocationAddresses $addresses) {}
    private const TYPES = 'airbnb,cafe,studio,hotel,house,outdoor,other';
    public function index(Store $store, Request $request)
    {
        $filters = $request->validate(['q' => 'sometimes|nullable|string|max:150', 'type' => 'sometimes|in:all,'.self::TYPES, 'active' => 'sometimes|in:all,1,0', 'page' => 'sometimes|integer|min:1']);
        $query = PhotoLocation::where('store_id', $store->id)->with(PhotoLocationAddresses::RELATION)->withCount('sessions');
        if (($filters['type'] ?? 'all') !== 'all') $query->where('type', $filters['type']);
        if (($filters['active'] ?? 'all') !== 'all') $query->where('active', $filters['active']);
        if ($search = trim($filters['q'] ?? '')) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')->orWhereHas('address', function ($address) use ($search) {
                    $address->where(function ($fields) use ($search) {
                        $fields->where('primary', 'like', '%'.$search.'%')->orWhere('city', 'like', '%'.$search.'%')
                            ->orWhere('references', 'like', '%'.$search.'%')
                            ->orWhereHas('district', fn ($district) => $district->where('name', 'like', '%'.$search.'%'));
                    });
                });
            });
        }
        return $query->orderBy('name')->orderBy('id')->paginate(20)->through(fn ($location) => $this->addresses->present($location));
    }
    public function show(Store $store, int $photo_location_id)
    {
        return responseOk($this->addresses->present(PhotoLocation::where('store_id', $store->id)->findOrFail($photo_location_id)));
    }
    public function store(Store $store, Request $request)
    {
        $data = $this->validated($request);
        $location = DB::transaction(function () use ($store, $data) {
            $location = new PhotoLocation(collect($data)->except('address')->all()); $location->store_id = $store->id; $location->save();
            if (array_key_exists('address', $data)) $this->addresses->save($location, $data['address']);
            return $location;
        });
        return response()->json(['data' => $this->addresses->present($location), 'message' => 'Locación creada.'], 201);
    }
    public function update(Store $store, int $photo_location_id, Request $request)
    {
        $data = $this->validated($request);
        $location = DB::transaction(function () use ($store, $photo_location_id, $data) {
            $location = PhotoLocation::where('store_id', $store->id)->lockForUpdate()->findOrFail($photo_location_id);
            $location->update(collect($data)->except('address')->all());
            if (array_key_exists('address', $data)) $this->addresses->save($location, $data['address']);
            else $location->address()->update(['name' => $location->name]);
            return $location;
        });
        return responseOk($this->addresses->present($location), 'Locación actualizada.');
    }
    private function validated(Request $request): array
    {
        return $request->validate(['name' => 'required|string|max:180', 'type' => 'required|in:'.self::TYPES,
            'address' => 'sometimes|nullable|array:primary,secondary,references,district_id,url_maps,latitud,longitud,city',
            'contact_name' => 'nullable|string|max:180', 'phone' => 'nullable|string|max:40', 'email' => 'nullable|email|max:255',
            'reference_url' => 'nullable|url:http,https|max:1000', 'rate' => 'nullable|numeric|min:0|max:9999999999.99',
            'rate_unit' => 'required|in:hour,day,session', 'notes' => 'nullable|string|max:5000', 'active' => 'required|boolean',
            ...$this->addresses->rules('address.')]);
    }
}
