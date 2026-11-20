<?php
namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\{PhotoContact, Store};
use App\Services\PhotoSessions\PhotoModelImages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PhotoModelImageDashboardController extends Controller
{
    private function model(Store $store, Request $request): PhotoContact
    {
        $user = $request->user();
        abort_unless($user && $user->hasAnyRole(['master', 'ceo', 'admin', 'marketing']), 403);
        abort_unless($user->hasRole('master') || $user->stores()->whereKey($store->id)->exists(), 403);
        $values = $request->validate(['imageable_id' => 'required|integer|min:1']);
        return PhotoContact::where('store_id', $store->id)->where('kind', 'model')->findOrFail($values['imageable_id']);
    }
    public function store(Store $store, Request $request, PhotoModelImages $files)
    {
        $model = $this->model($store, $request);
        $request->validate(['images' => 'required|array|min:1|max:10', 'images.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240|dimensions:max_width=10000,max_height=10000']);
        $created = [];
        try {
            $images = DB::transaction(function () use ($request, $files, $store, $model, &$created) {
                $images = [];
                foreach ($request->file('images') as $file) {
                    $values = $files->store($file, $store->id, $model->id); $created[] = $values;
                    $images[] = $model->images()->create($values);
                }
                return $images;
            });
            return responseOk($images, 'Imágenes referenciales guardadas.');
        } catch (\Throwable $error) { foreach ($created as $paths) $files->remove($paths); throw $error; }
    }
    public function destroy(Store $store, Request $request, int $image_id, PhotoModelImages $files)
    {
        $model = $this->model($store, $request);
        $image = $model->images()->findOrFail($image_id);
        $paths = $image->only(['thumbnail', 'medium', 'large']);
        // Only delete files owned by this reference library, never unrelated image paths.
        $prefix = 'photo-models/'.$store->id.'/'.$model->id.'/';
        foreach ($paths as $path) abort_unless(is_string($path) && str_starts_with($path, $prefix) && !str_contains($path, '..'), 422);
        $files->remove($paths); $image->delete();
        return responseOk(null, 'Imagen eliminada.');
    }
}
