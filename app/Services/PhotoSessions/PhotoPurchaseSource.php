<?php
namespace App\Services\PhotoSessions;

use App\Models\PhotoSession;

class PhotoPurchaseSource
{
    public function resolve(PhotoSession $session, string $kind, int $id): array
    {
        $relation = match ($kind) {
            'photographer' => $session->photographers(), 'model' => $session->models(),
            'location' => $session->locations(), 'task' => $session->tasks(),
            default => abort(422, 'Origen de compra no válido.'),
        };
        $record = $relation->where('store_id', $session->store_id)->findOrFail($id);
        if (in_array($kind, ['photographer', 'model'], true)) abort_unless($record->kind === $kind, 404);
        return ['kind' => $kind, 'id' => $record->id, 'name' => $record->name ?? $record->title,
            'recipient_name' => $kind === 'task' ? null : $record->name];
    }
}
