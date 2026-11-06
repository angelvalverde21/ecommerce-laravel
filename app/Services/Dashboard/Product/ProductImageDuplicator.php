<?php

namespace App\Services\Dashboard\Product;

use App\Models\Image;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductImageDuplicator
{
    private array $copies = [];

    public function copy(?string $source, int $productId, array &$files): ?string
    {
        if (!$source) { return $source; }
        if (isset($this->copies[$source])) { return $this->copies[$source]; }

        $disk = Storage::disk('public');
        $relative = $source;
        if (filter_var($source, FILTER_VALIDATE_URL)) {
            $path = parse_url($source, PHP_URL_PATH);
            $host = parse_url($source, PHP_URL_HOST);
            $localHost = parse_url(config('app.url'), PHP_URL_HOST);
            if ($host === $localHost && str_starts_with($path ?? '', '/storage/images/')) {
                $relative = substr($path, strlen('/storage/images/'));
            } else {
                $bytes = $this->download($source);
                $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'image/avif' => 'avif'];
                $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
                if (!isset($extensions[$mime])) { $this->fail(); }
                $relative = 'products/'.$productId.'/'.Str::uuid().'.'.$extensions[$mime];
                $target = Image::DIR_ROOT.'/'.$relative;
                $files[] = $target;
                if (!$disk->put($target, $bytes)) { $this->fail(); }

                return $this->copies[$source] = $relative;
            }
        }

        $relative = str_replace('\\', '/', $relative);
        if (str_starts_with($relative, '/') || in_array('..', explode('/', $relative), true)) { $this->fail(); }
        $path = Image::DIR_ROOT.'/'.$relative;
        if (!$disk->exists($path)) { $this->fail(); }
        $extension = pathinfo($relative, PATHINFO_EXTENSION);
        $targetRelative = 'products/'.$productId.'/'.Str::uuid().($extension ? '.'.$extension : '');
        $target = Image::DIR_ROOT.'/'.$targetRelative;
        $files[] = $target;
        if (!$disk->copy($path, $target)) { $this->fail(); }

        return $this->copies[$source] = $targetRelative;
    }

    private function download(string $url): string
    {
        $parts = parse_url($url);
        $host = $parts['host'] ?? '';
        if (!in_array($parts['scheme'] ?? '', ['https', 'http'], true) || isset($parts['user']) || isset($parts['pass'])) { $this->fail(); }
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
        if (!$ips) { $this->fail(); }
        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) { $this->fail(); }
        }
        $port = $parts['port'] ?? ($parts['scheme'] === 'https' ? 443 : 80);
        // Pin DNS resolution and disallow redirects to prevent requests to private services.
        try {
            $response = Http::timeout(30)->connectTimeout(10)->withOptions([
                'allow_redirects' => false,
                'stream' => true,
                'curl' => [CURLOPT_RESOLVE => [$host.':'.$port.':'.implode(',', $ips)]],
            ])->get($url);
            if (!$response->successful()) { $this->fail(); }
            $stream = $response->toPsrResponse()->getBody();
            $bytes = '';
            try {
                while (!$stream->eof()) {
                    $bytes .= $stream->read(65536);
                    if (strlen($bytes) > 20 * 1024 * 1024) { $this->fail(); }
                }
            } finally {
                $stream->close();
            }
        } catch (\Illuminate\Http\Client\ConnectionException $exception) {
            $this->fail();
        }

        return $bytes;
    }

    private function fail(): never
    {
        throw ValidationException::withMessages(['images' => 'No se pudo copiar una imagen del producto. Revisa que sus archivos o enlaces estén disponibles.']);
    }
}
