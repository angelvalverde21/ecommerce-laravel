<?php
namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\{PhotoContact, Store};
use Illuminate\Http\Request;

class PhotoContactDashboardController extends Controller
{
    public function index(Store $store, Request $request)
    {
        $filters = $request->validate(['kind' => 'required|in:photographer,model', 'q' => 'sometimes|nullable|string|max:150', 'active' => 'sometimes|in:all,1,0', 'page' => 'sometimes|integer|min:1']);
        $query = PhotoContact::where('store_id', $store->id)->where('kind', $filters['kind']);
        if ($filters['kind'] === 'model') $query->withCount('images');
        if (($filters['active'] ?? 'all') !== 'all') $query->where('active', $filters['active']);
        if ($search = trim($filters['q'] ?? '')) $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')->orWhere('company', 'like', '%'.$search.'%'));
        return $query->orderBy('name')->orderBy('id')->paginate(20);
    }
    public function show(Store $store, int $contact_id, Request $request)
    {
        $data = $request->validate(['kind' => 'required|in:photographer,model']);
        $contact = PhotoContact::where('store_id', $store->id)->where('kind', $data['kind'])->findOrFail($contact_id);
        return responseOk($contact->kind === 'model' ? $contact->load('images') : $contact);
    }
    public function store(Store $store, Request $request)
    {
        $contact = new PhotoContact($this->validated($request)); $contact->store_id = $store->id; $contact->save();
        return response()->json(['data' => $contact->fresh(), 'message' => 'Ficha creada.'], 201);
    }
    public function update(Store $store, int $contact_id, Request $request)
    {
        $data = $this->validated($request);
        $contact = PhotoContact::where('store_id', $store->id)->where('kind', $data['kind'])->findOrFail($contact_id);
        $contact->update($data);
        return responseOk($contact->kind === 'model' ? $contact->load('images') : $contact, 'Ficha actualizada.');
    }
    private function validated(Request $request): array
    {
        return $request->validate(['kind' => 'required|in:photographer,model', 'name' => 'required|string|max:180',
            'phone' => 'nullable|string|max:40', 'email' => 'nullable|email|max:255', 'instagram' => 'nullable|string|max:120',
            'portfolio_url' => 'nullable|url:http,https|max:500', 'company' => 'nullable|string|max:255', 'notes' => 'nullable|string|max:5000', 'active' => 'required|boolean']);
    }
}
