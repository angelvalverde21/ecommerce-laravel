<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\Store;
use App\Services\Dashboard\Courier\CourierService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourierDashboardController extends Controller
{
    public function __construct(protected CourierService $service)
    {
    }

    public function index(Store $store, Request $request)
    {
        return $this->service->index($store, 25, $request->only(['search', 'status', 'delivery_type', 'with_addresses']));
    }

    public function agency(Store $store)
    {
        return $this->service->agency($store, 100);
    }

    public function express(Store $store)
    {
        return $this->service->express($store, 100);
    }

    public function active(Store $store)
    {
        return responseOk($this->service->active($store));
    }

    public function blocked(Store $store)
    {
        return responseOk($this->service->blocked($store));
    }

    public function search(Store $store, string $search = '')
    {
        return responseOk($this->service->search($store, trim($search)));
    }

    public function store(Store $store, Request $request)
    {
        $validated = $request->validate($this->rules());
        $courier = Courier::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Courier creado correctamente.',
            'data' => $courier->loadCount('addresses'),
        ], 201);
    }

    public function show(Store $store, int $courier_id)
    {
        return responseOk($this->service->show($store, $courier_id));
    }

    public function addresses(Store $store, Request $request, int $courier_id)
    {
        $request->validate(['search' => ['nullable', 'string', 'max:255']]);

        return $this->service->addresses($courier_id, $request->input('search') ?? '');
    }

    public function update(Request $request, Store $store, int $courier_id)
    {
        $courier = Courier::findOrFail($courier_id);
        $validated = $request->validate($this->rules(partial: true));
        $courier->update($validated);

        return responseOk($courier->fresh()->loadCount('addresses'), 'Courier actualizado correctamente.');
    }

    public function destroy(Store $store, int $courier_id)
    {
        // Blocking preserves addresses and the shipping history of existing orders.
        $courier = Courier::findOrFail($courier_id);
        $courier->update(['status' => 0]);

        return responseOk($courier, 'Courier bloqueado. Se conservan sus oficinas y pedidos.');
    }

    private function rules(bool $partial = false): array
    {
        $presence = $partial ? 'sometimes' : 'required';

        return [
            'name' => [$presence, 'required', 'string', 'max:255', 'not_regex:/^\s*$/u'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'status' => ['sometimes', 'required', 'integer', Rule::in([0, 1])],
            'is_cod' => [$presence, 'boolean'],
            'freight_collect' => [$presence, 'boolean'],
            'delivery_type' => [$presence, Rule::in(['agency', 'express', 'store_pickup'])],
        ];
    }
}
