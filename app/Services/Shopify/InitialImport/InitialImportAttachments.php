<?php

namespace App\Services\Shopify\InitialImport;

use App\Models\ImportShopifyRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/** Keeps original evidence files private and independently verifiable. */
class InitialImportAttachments
{
    private const MAX_BYTES = 100 * 1024 * 1024;

    public function stage(ImportShopifyRun $run, array $event, string $order): void
    {
        foreach ($event['attachments'] ?? [] as $file) {
            if (empty($file['id']) || empty($file['url']) || !isset($file['size'])) {
                throw new RuntimeException('Shopify devolvió un adjunto de timeline sin identificación, URL o tamaño.');
            }
            $key = hash('sha256', $event['id']."\0".$file['id']);
            $identity = ['run_id' => $run->id, 'task_key' => $key];
            $existing = DB::table('import_shopify_event_attachments')->where($identity)->first();
            $payload = json_encode($file, JSON_THROW_ON_ERROR);
            DB::table('import_shopify_event_attachments')->updateOrInsert($identity, [
                'shopify_id' => $file['id'], 'parent_shopify_id' => $event['id'], 'order_shopify_id' => $order,
                'payload' => $payload, 'created_at' => $existing?->created_at ?? now(), 'updated_at' => now(),
            ]);
            // Refreshing signed URLs must not discard an already archived original.
            DB::table('import_shopify_tasks')->insertOrIgnore([
                'run_id' => $run->id, 'kind' => 'event_attachments', 'owner_id' => $key,
                'status' => 'queued', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function archive(ImportShopifyRun $run, string $key): void
    {
        if (!Str::isUuid($run->id) || !preg_match('/^[a-f0-9]{64}$/', $key)) throw new RuntimeException('Identificación de adjunto inválida.');
        $record = DB::table('import_shopify_event_attachments')->where('run_id', $run->id)->where('task_key', $key)->first();
        if (!$record) throw new RuntimeException('No se encontró el adjunto del timeline.');
        $relative = 'import-shopify/'.$run->id.'/attachments/'.$key.'.original';
        $path = storage_path('app/private/'.$relative);
        $payload = json_decode($record->payload, true, 512, JSON_THROW_ON_ERROR);
        $expected = (int) $payload['size'];
        if ($record->status === 'downloaded' && is_file($path) && filesize($path) === (int) $record->bytes && hash_file('sha256', $path) === $record->sha256) return;
        if ($expected <= 0 || $expected > self::MAX_BYTES) throw new RuntimeException('El tamaño del adjunto requiere revisión; no se omitirá silenciosamente.');
        if (!is_dir(dirname($path)) && !mkdir(dirname($path), 0700, true) && !is_dir(dirname($path))) throw new RuntimeException('No se pudo crear el archivo privado.');

        $temporary = $path.'.download';
        try {
            $headers = $this->download($payload['url'], $temporary);
            clearstatcache(true, $temporary);
            $actual = is_file($temporary) ? filesize($temporary) : 0;
            if ($actual <= 0 || $actual > self::MAX_BYTES || (is_numeric($headers['content_length']) && (int) $headers['content_length'] !== $actual)) {
                throw new RuntimeException('El tamaño de la descarga del adjunto está incompleto.');
            }
            // Shopify's image CDN can recompress the source. Never confuse the
            // API's upload size with HTTP Content-Length; validate full resolution.
            $dimensions = null;
            if ($actual !== $expected) {
                $dimensions = @getimagesize($temporary);
                $width = (int) ($payload['image']['width'] ?? 0);
                $height = (int) ($payload['image']['height'] ?? 0);
                if (!$dimensions || !str_starts_with($headers['content_type'], 'image/')
                    || !is_numeric($headers['content_length'])
                    || ($width && $dimensions[0] !== $width) || ($height && $dimensions[1] !== $height)
                    || $dimensions[0] * $dimensions[1] > 64000000 || !function_exists('imagecreatefromstring')) {
                    throw new RuntimeException('El adjunto difiere de su tamaño original y no se pudo verificar la imagen completa.');
                }
                $image = @imagecreatefromstring(file_get_contents($temporary));
                if (!$image) throw new RuntimeException('La imagen adjunta no se pudo decodificar completamente.');
                imagedestroy($image);
            }
            $hash = hash_file('sha256', $temporary);
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($temporary);
            if (!rename($temporary, $path)) throw new RuntimeException('No se pudo conservar el adjunto original.');
            DB::table('import_shopify_event_attachments')->where('id', $record->id)->update([
                'status' => 'downloaded', 'local_path' => $relative, 'bytes' => $actual,
                'sha256' => $hash, 'mime_type' => $mime ?: 'application/octet-stream', 'downloaded_at' => now(),
                'download_metadata' => json_encode([...$headers, 'source_size_matches' => $actual === $expected,
                    'width' => $dimensions[0] ?? null, 'height' => $dimensions[1] ?? null], JSON_THROW_ON_ERROR),
                'error_message' => null, 'updated_at' => now(),
            ]);
        } finally {
            if (is_file($temporary)) unlink($temporary);
        }
    }

    private function download(string $url, string $path): array
    {
        for ($redirect = 0; $redirect < 4; $redirect++) {
            $parts = parse_url($url);
            $host = strtolower($parts['host'] ?? '');
            if (($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass'])
                || (isset($parts['port']) && $parts['port'] !== 443)
                || !($host === 'cdn.shopify.com' || $host === 'shopify-private-shop-assets.storage.googleapis.com' || str_ends_with($host, '.shopifycdn.com'))) {
                throw new RuntimeException('El adjunto usa un destino no reconocido de Shopify; requiere revisión.');
            }
            try {
                // No API token/cookies are forwarded to the file host. Validate every redirect.
                $response = Http::withHeaders(['Accept' => 'application/octet-stream', 'Accept-Encoding' => 'identity'])->connectTimeout(10)->timeout(120)->withOptions([
                    'allow_redirects' => false, 'sink' => $path,
                    'progress' => static function ($total, $received) {
                        if ($total > self::MAX_BYTES || $received > self::MAX_BYTES) throw new RuntimeException('El adjunto excede el límite de descarga.');
                    },
                ])->get($url);
            } catch (\Throwable) {
                // Never put a signed URL (or a payment receipt) in logs/API errors.
                throw new RuntimeException('Se interrumpió la descarga de un adjunto del timeline.');
            }
            if ($response->redirect()) { $url = (string) $response->header('Location'); continue; }
            if (!$response->successful()) throw new RuntimeException('No se pudo descargar el adjunto (HTTP '.$response->status().').');
            return ['content_length' => $response->header('Content-Length'), 'content_type' => $response->header('Content-Type'),
                'etag' => $response->header('ETag'), 'last_modified' => $response->header('Last-Modified')];
        }
        throw new RuntimeException('El adjunto supera el límite de redirecciones.');
    }
}
