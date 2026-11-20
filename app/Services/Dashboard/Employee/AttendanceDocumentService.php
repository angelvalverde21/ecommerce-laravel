<?php

namespace App\Services\Dashboard\Employee;

use App\Models\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image as ImageProcessor;

class AttendanceDocumentService
{
    public function present(Image $document): array
    {
        return [...$document->only(['id', 'name']), 'is_image' => $this->isImage($document->large)];
    }

    public function store(UploadedFile $file, string $directory): array
    {
        $created = [];
        try {
            $large = $file->store($directory, 'local');
            abort_unless($large, 500, 'No se pudo guardar el documento.');
            $created[] = $large;
            $variants = $this->isImage($large) ? $this->variants($large, $created) : [];
            return ['name' => mb_substr(basename($file->getClientOriginalName()), 0, 255), 'large' => $large, ...$variants];
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($created);
            throw $error;
        }
    }

    public function ensureVariants(Image $document): void
    {
        if (!$this->isImage($document->large)) return;
        $disk = Storage::disk('local');
        if ($document->thumbnail && $document->medium && $disk->exists($document->thumbnail) && $disk->exists($document->medium)) return;
        $created = [];
        try {
            $document->update($this->variants($document->large, $created));
        } catch (\Throwable $error) {
            $disk->delete($created);
            throw $error;
        }
    }

    private function isImage(?string $path): bool
    {
        return in_array(strtolower(pathinfo($path ?? '', PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true);
    }

    private function variants(string $large, array &$created): array
    {
        $disk = Storage::disk('local');
        $bytes = $disk->get($large);
        $paths = [];
        foreach (['thumbnail' => 360, 'medium' => 750] as $variant => $width) {
            $path = dirname($large).'/'.pathinfo($large, PATHINFO_FILENAME).'-'.$variant.'.webp';
            if (!$disk->exists($path)) {
                $created[] = $path;
                $encoded = ImageProcessor::read($bytes)->scaleDown(width: $width)->toWebp(quality: 90);
                abort_unless($disk->put($path, (string) $encoded), 500, 'No se pudo guardar la variante de imagen.');
            }
            $paths[$variant] = $path;
        }
        return $paths;
    }
}
