<?php
namespace App\Services\PhotoSessions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image as Processor;

/** Reference images use the ERP image library and its public storage layout. */
class PhotoModelImages
{
    public function store(UploadedFile $file, int $store, int $contact): array
    {
        $directory = 'photo-models/'.$store.'/'.$contact;
        $id = (string) Str::uuid(); $paths = [];
        try {
            $large = $directory.'/'.$id.'.'.$file->extension();
            $paths['large'] = $large;
            abort_unless($file->storeAs('images/'.$directory, basename($large), 'public'), 500);
            foreach (['thumbnail' => 360, 'medium' => 960] as $variant => $width) {
                $paths[$variant] = $directory.'/'.$id.'-'.$variant.'.webp';
                $bytes = Processor::read($file)->scaleDown(width: $width)->toWebp(quality: 90);
                abort_unless(Storage::disk('public')->put('images/'.$paths[$variant], (string) $bytes), 500);
            }
            return ['name' => mb_substr(basename(str_replace('\\', '/', $file->getClientOriginalName())), 0, 255), ...$paths];
        } catch (\Throwable $error) { $this->remove($paths); throw $error; }
    }
    public function remove(array $paths): void
    {
        $paths = array_intersect_key($paths, array_flip(['thumbnail', 'medium', 'large']));
        Storage::disk('public')->delete(array_map(fn ($path) => 'images/'.$path, array_values($paths)));
    }
}
