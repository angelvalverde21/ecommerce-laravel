<?php
namespace App\Services\PhotoSessions;

use App\Models\PhotoSessionAsset;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use GuzzleHttp\Promise\Utils;
use Illuminate\Validation\ValidationException;

/** Only control requests cross PHP. Original files and previews go directly to B2. */
class PhotoSessionStorage
{
    private ?S3Client $cachedClient = null;

    public function configured(): bool
    {
        foreach (['key', 'secret', 'bucket', 'endpoint', 'region'] as $field) if (!config('photo-sessions.'.$field)) return false;
        return class_exists(S3Client::class);
    }

    public function client(): S3Client
    {
        if ($this->cachedClient) return $this->cachedClient;
        abort_unless($this->configured(), 503, 'Configura Backblaze B2 antes de subir archivos.');
        $endpoint = config('photo-sessions.endpoint');
        abort_unless(preg_match('~^https://s3\.[a-z0-9-]+\.backblazeb2\.com/?$~', $endpoint), 503, 'El endpoint de B2 no es válido.');
        return $this->cachedClient = new S3Client(['version' => 'latest', 'region' => config('photo-sessions.region'), 'endpoint' => $endpoint,
            'credentials' => ['key' => config('photo-sessions.key'), 'secret' => config('photo-sessions.secret')],
            'use_path_style_endpoint' => true, 'request_checksum_calculation' => 'when_required', 'response_checksum_validation' => 'when_required',
            'http' => ['connect_timeout' => 10, 'timeout' => 60]]);
    }

    private function target(PhotoSessionAsset $asset): array { return ['Bucket' => $asset->bucket, 'Key' => $asset->object_key]; }

    public function begin(PhotoSessionAsset $asset): string
    {
        return $this->client()->createMultipartUpload([...$this->target($asset), 'ContentType' => $asset->mime_type,
            'Metadata' => ['asset-id' => $asset->id]])['UploadId'];
    }

    public function partUrl(PhotoSessionAsset $asset, int $part): string
    {
        $client = $this->client();
        return (string) $client->createPresignedRequest($client->getCommand('UploadPart', [...$this->target($asset),
            'UploadId' => $asset->upload_id, 'PartNumber' => $part]), '+30 minutes')->getUri();
    }

    /** Small originals need one PUT, not a multipart initiation/list/assembly cycle. */
    public function singleUrl(PhotoSessionAsset $asset): array
    {
        $client = $this->client();
        $request = $client->createPresignedRequest($client->getCommand('PutObject', [
            ...$this->target($asset), 'ContentType' => $asset->mime_type,
            'Metadata' => ['asset-id' => $asset->id],
        ]), '+30 minutes');
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            if (in_array(strtolower($name), ['content-type', 'x-amz-meta-asset-id'])) $headers[$name] = implode(', ', $values);
        }
        return ['url' => (string) $request->getUri(), 'headers' => $headers];
    }

    public function finish(PhotoSessionAsset $asset): array
    {
        if (!$asset->upload_id) return $this->verify($asset);
        $client = $this->client();
        // A completion response can be lost: an existing verified object is already complete.
        if ($client->doesObjectExistV2($asset->bucket, $asset->object_key)) return $this->verify($asset);
        $parts = []; $marker = 0; $bytes = 0;
        do {
            $result = $client->listParts([...$this->target($asset), 'UploadId' => $asset->upload_id, 'PartNumberMarker' => $marker]);
            foreach ($result['Parts'] ?? [] as $part) {
                $number = count($parts) + 1;
                $expected = min(config('photo-sessions.part_bytes'), $asset->bytes - $bytes);
                if ($part['PartNumber'] !== $number || (int) $part['Size'] !== $expected) throw ValidationException::withMessages(['file' => 'Las partes del archivo están incompletas. Vuelve a intentar la carga.']);
                $parts[] = ['PartNumber' => $number, 'ETag' => $part['ETag']];
                $bytes += (int) $part['Size'];
            }
            $marker = $result['NextPartNumberMarker'] ?? 0;
        } while ($result['IsTruncated'] ?? false);
        if ($bytes !== $asset->bytes || !$parts) throw ValidationException::withMessages(['file' => 'Faltan partes por subir.']);
        $client->completeMultipartUpload([...$this->target($asset), 'UploadId' => $asset->upload_id, 'MultipartUpload' => ['Parts' => $parts]]);
        return $this->verify($asset);
    }

    private function verify(PhotoSessionAsset $asset): array
    {
        $head = $this->client()->headObject($this->target($asset));
        if ((int) $head['ContentLength'] !== $asset->bytes || ($head['Metadata']['asset-id'] ?? '') !== $asset->id) throw ValidationException::withMessages(['file' => 'El archivo recibido no coincide con el original.']);
        return ['etag' => $head['ETag']];
    }

    public function abort(PhotoSessionAsset $asset): void
    {
        if (!$asset->upload_id) {
            $this->client()->deleteObject($this->target($asset));
            return;
        }
        $this->client()->abortMultipartUpload([...$this->target($asset), 'UploadId' => $asset->upload_id]);
    }

    public function previewUrl(PhotoSessionAsset $asset, string $size, int $bytes): string
    {
        $client = $this->client();
        return (string) $client->createPresignedRequest($client->getCommand('PutObject', [
            'Bucket' => $asset->preview_bucket ?: $asset->bucket, 'Key' => $asset->object_key.'.'.$size.'.webp', 'ContentType' => 'image/webp', 'ContentLength' => $bytes,
        ]), '+15 minutes')->getUri();
    }

    public function confirmPreviews(PhotoSessionAsset $asset): array
    {
        $previews = [];
        $bucket = $asset->preview_bucket ?: $asset->bucket;
        $client = $this->client();
        $requests = [];
        foreach (['thumb', 'medium', 'large'] as $size) {
            $key = $asset->object_key.'.'.$size.'.webp';
            $requests[$size] = $client->headObjectAsync(['Bucket' => $bucket, 'Key' => $key]);
        }
        // HEAD already tells us whether an object exists; no preceding existence request.
        foreach (Utils::settle($requests)->wait() as $size => $result) {
            if ($result['state'] === 'rejected') {
                $error = $result['reason'];
                if ($error instanceof AwsException && $error->getStatusCode() === 404) continue;
                throw $error;
            }
            $head = $result['value'];
            if (($head['ContentType'] ?? '') === 'image/webp' && (int) $head['ContentLength'] > 0 && (int) $head['ContentLength'] <= 10 * 1024 * 1024) $previews[$size] = $asset->object_key.'.'.$size.'.webp';
        }
        return $previews;
    }

    public function readUrl(PhotoSessionAsset $asset, ?string $preview = null): string
    {
        $client = $this->client();
        $args = ['Bucket' => $preview ? ($asset->preview_bucket ?: $asset->bucket) : $asset->bucket, 'Key' => $preview ? $asset->previews[$preview] : $asset->object_key];
        if (!$preview) $args['ResponseContentDisposition'] = 'attachment; filename*=UTF-8\'\''.rawurlencode($asset->name);
        return (string) $client->createPresignedRequest($client->getCommand('GetObject', $args), '+30 minutes')->getUri();
    }
}
