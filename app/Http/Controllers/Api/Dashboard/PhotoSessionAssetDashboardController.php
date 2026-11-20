<?php
namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\{PhotoSession, PhotoSessionAsset, Store};
use App\Services\PhotoSessions\PhotoSessionStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PhotoSessionAssetDashboardController extends Controller
{
    public function __construct(private PhotoSessionStorage $storage) {}

    private function session(Store $store, int $id): PhotoSession { return PhotoSession::where('store_id', $store->id)->findOrFail($id); }
    private function asset(Store $store, int $session, string $id): PhotoSessionAsset { return $this->session($store, $session)->assets()->findOrFail($id); }

    public function index(Store $store, int $photo_session, Request $request)
    {
        $filters = $request->validate(['kind' => 'sometimes|in:all,photo,video', 'category' => 'sometimes|in:all,original,edited,final', 'selected' => 'sometimes|boolean', 'page' => 'sometimes|integer|min:1']);
        $query = $this->session($store, $photo_session)->assets()->where('status', 'ready');
        foreach (['kind', 'category'] as $field) if (($filters[$field] ?? 'all') !== 'all') $query->where($field, $filters[$field]);
        if ($request->boolean('selected')) $query->where('selected', true);
        return $query->latest()->paginate(24)->through(fn ($asset) => $this->present($asset));
    }

    public function store(Store $store, int $photo_session, Request $request)
    {
        $session = $this->session($store, $photo_session);
        $data = $request->validate(['name' => 'required|string|max:255', 'bytes' => 'required|integer|min:1|max:'.config('photo-sessions.max_bytes'), 'category' => 'required|in:original,edited,final']);
        $extension = strtolower(pathinfo($data['name'], PATHINFO_EXTENSION));
        $video = in_array($extension, ['mp4', 'mov', 'm4v', 'webm', 'avi', 'mkv', 'mxf']);
        if (!$video && !in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'avif', 'heic', 'heif', 'tif', 'tiff', 'dng', 'cr2', 'cr3', 'nef', 'arw', 'rw2', 'raf', 'orf'])) throw ValidationException::withMessages(['name' => 'Selecciona una foto, RAW o video compatible.']);
        abort_unless($this->storage->configured(), 503, 'Backblaze B2 no está configurado.');
        $id = (string) Str::uuid();
        $asset = $session->assets()->create([...$data, 'id' => $id, 'uploaded_by' => $request->user()->id,
            'name' => basename(str_replace('\\', '/', $data['name'])), 'kind' => $video ? 'video' : 'photo',
            'bucket' => config('photo-sessions.bucket'), 'preview_bucket' => config('photo-sessions.preview_bucket') ?: config('photo-sessions.bucket'), 'mime_type' => 'application/octet-stream',
            'object_key' => 'photo-sessions/'.$store->id.'/'.$session->id.'/'.$id.'/original.'.$extension]);
        try {
            if ($asset->bytes > config('photo-sessions.single_put_bytes')) $asset->update(['upload_id' => $this->storage->begin($asset)]);
        }
        catch (\Throwable $error) { $asset->delete(); return $this->storageError(); }
        return response()->json(['data' => ['id' => $asset->id, 'part_bytes' => config('photo-sessions.part_bytes'), 'parts' => (int) ceil($asset->bytes / config('photo-sessions.part_bytes'))]], 201);
    }

    public function part(Store $store, int $photo_session, string $asset_id, int $part)
    {
        $asset = $this->asset($store, $photo_session, $asset_id);
        abort_unless($asset->status === 'uploading' && $part >= 1 && $part <= ceil($asset->bytes / config('photo-sessions.part_bytes')), 422);
        try { return responseOk($asset->upload_id ? ['url' => $this->storage->partUrl($asset, $part)] : $this->storage->singleUrl($asset)); } catch (\Throwable) { return $this->storageError(); }
    }

    public function complete(Store $store, int $photo_session, string $asset_id)
    {
        $asset = $this->asset($store, $photo_session, $asset_id);
        return Cache::lock('photo-asset:'.$asset->id, 180)->block(5, function () use ($asset) {
            $asset->refresh();
            if ($asset->status === 'ready') return responseOk($this->present($asset));
            abort_unless($asset->status === 'uploading', 422);
            try { $metadata = $this->storage->finish($asset); }
            catch (ValidationException $error) { throw $error; }
            catch (\Throwable) { return $this->storageError(); }
            $asset->update([...$metadata, 'status' => 'ready', 'upload_id' => null, 'completed_at' => now()]);
            return responseOk($this->present($asset));
        });
    }

    public function abort(Store $store, int $photo_session, string $asset_id)
    {
        $asset = $this->asset($store, $photo_session, $asset_id);
        return Cache::lock('photo-asset:'.$asset->id, 180)->block(5, function () use ($asset) {
            $asset->refresh();
            if ($asset->status === 'cancelled') return responseOk(null, 'Carga cancelada.');
            abort_unless($asset->status === 'uploading', 422);
            try { $this->storage->abort($asset); } catch (\Throwable) { return $this->storageError(); }
            $asset->update(['status' => 'cancelled', 'upload_id' => null]);
            return responseOk(null, 'Carga cancelada.');
        });
    }

    public function previews(Store $store, int $photo_session, string $asset_id, Request $request)
    {
        $asset = $this->asset($store, $photo_session, $asset_id);
        abort_unless($asset->status === 'ready', 422);
        $data = $request->validate(['size' => 'required|in:thumb,medium,large', 'bytes' => 'required|integer|min:1|max:10485760']);
        try { return responseOk(['url' => $this->storage->previewUrl($asset, $data['size'], $data['bytes'])]); } catch (\Throwable) { return $this->storageError(); }
    }

    public function confirmPreviews(Store $store, int $photo_session, string $asset_id)
    {
        $asset = $this->asset($store, $photo_session, $asset_id); abort_unless($asset->status === 'ready', 422);
        try { $asset->update(['previews' => $this->storage->confirmPreviews($asset)]); } catch (\Throwable) { return $this->storageError(); }
        return responseOk($this->present($asset));
    }

    public function previewBatch(Store $store, int $photo_session, string $asset_id, Request $request)
    {
        $asset = $this->asset($store, $photo_session, $asset_id);
        abort_unless($asset->status === 'ready', 422);
        $data = $request->validate(['previews' => 'required|array|min:1|max:3',
            'previews.*.size' => 'required|in:thumb,medium,large|distinct',
            'previews.*.bytes' => 'required|integer|min:1|max:10485760']);
        try {
            return responseOk(['uploads' => array_map(fn ($preview) => ['size' => $preview['size'],
                'url' => $this->storage->previewUrl($asset, $preview['size'], $preview['bytes'])], $data['previews'])]);
        } catch (\Throwable) { return $this->storageError(); }
    }

    public function update(Store $store, int $photo_session, string $asset_id, Request $request)
    {
        $asset = $this->asset($store, $photo_session, $asset_id);
        $asset->update($request->validate(['selected' => 'sometimes|boolean', 'category' => 'sometimes|in:original,edited,final', 'caption' => 'nullable|string|max:2000']));
        return responseOk($this->present($asset));
    }

    public function download(Store $store, int $photo_session, string $asset_id)
    {
        $asset = $this->asset($store, $photo_session, $asset_id); abort_unless($asset->status === 'ready', 404);
        try { return responseOk(['url' => $this->storage->readUrl($asset)]); } catch (\Throwable) { return $this->storageError(); }
    }

    private function present(PhotoSessionAsset $asset): array
    {
        $data = $asset->toArray(); unset($data['previews']);
        $data['image'] = null;
        if ($asset->status === 'ready' && isset($asset->previews['thumb'], $asset->previews['large'])) {
            $data['image'] = ['url_thumbnail' => $this->storage->readUrl($asset, 'thumb'), 'url_medium' => $this->storage->readUrl($asset, isset($asset->previews['medium']) ? 'medium' : 'large'), 'url_large' => $this->storage->readUrl($asset, 'large')];
        }
        return $data;
    }

    private function storageError() { return response()->json(['message' => 'No se pudo completar la operación en Backblaze B2. Revisa la conexión y los permisos e inténtalo de nuevo.'], 503); }
}
