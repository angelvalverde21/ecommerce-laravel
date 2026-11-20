<?php

namespace App\Services\Dashboard;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttachmentFileService
{
    public function store(Request $request, Model $owner)
    {
        $formats = 'pdf,jpg,jpeg,png,webp,gif,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,dxf,ai,psd';
        $request->validate(['file' => 'required|file|max:10240|extensions:'.$formats.'|mimes:'.$formats]);
        $file = $request->file('file');
        $name = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        if (mb_strlen($name) > 255) throw ValidationException::withMessages(['file' => 'El nombre del archivo es demasiado largo.']);
        $path = $file->store('attachments/'.Str::snake(class_basename($owner)).'/'.$owner->getKey(), 'local');
        abort_unless($path, 500, 'No se pudo guardar el archivo.');
        try {
            return $owner->attachments()->create([
                'name' => $name, 'path' => $path, 'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(), 'uploaded_by' => Auth::guard('api')->id() ?? Auth::id(),
            ]);
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($path);
            throw $error;
        }
    }

    public function download(Model $owner, int $id)
    {
        $file = $owner->attachments()->findOrFail($id);
        abort_unless(Storage::disk('local')->exists($file->path), 404);
        return Storage::disk('local')->download($file->path, $file->name, [
            'Content-Type' => 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function preview(Model $owner, int $id)
    {
        $file = $owner->attachments()->findOrFail($id);
        abort_unless(in_array($file->mime_type, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true), 422);
        abort_unless(Storage::disk('local')->exists($file->path), 404);
        return Storage::disk('local')->response($file->path, $file->name, [
            'Content-Type' => $file->mime_type, 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
