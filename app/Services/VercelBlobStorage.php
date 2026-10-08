<?php

namespace App\Services;

use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class VercelBlobStorage
{
    public function store(UploadedFile $file, string $directory, string $access): string
    {
        if (!in_array($access, ['public', 'private'], true)) {
            throw new LogicException('Blob access must be public or private.');
        }

        if (!$this->isEnabled()) {
            $disk = $access === 'public' ? 'public' : 'local';
            $path = $file->store($directory, $disk);

            if (!$path) {
                throw new RuntimeException('The uploaded file could not be saved.');
            }

            return $path;
        }

        $path = trim($directory, '/') . '/' . Str::uuid() . '.' . ($file->guessExtension() ?: 'bin');
        $content = file_get_contents($file->getRealPath());

        if ($content === false) {
            throw new RuntimeException('The uploaded file could not be read.');
        }

        $response = $this->request('PUT', $access, [
            'X-Blob-Path' => $path,
            'Content-Type' => $file->getMimeType() ?: 'application/octet-stream',
        ], $content);
        $response->throw();

        $url = $response->json('url');
        if (!is_string($url) || !str_starts_with($url, 'https://')) {
            throw new RuntimeException('The storage service returned an invalid file URL.');
        }

        return $url;
    }

    public function delete(?string $location, string $access): void
    {
        if (!$location) {
            return;
        }

        if (!$this->isBlobUrl($location, $access)) {
            $disk = $access === 'public' ? 'public' : 'local';
            Storage::disk($disk)->delete($location);

            return;
        }

        if (!$this->isEnabled()) {
            throw new LogicException('Vercel Blob storage is not configured for this deployment.');
        }

        $this->request('DELETE', $access, ['X-Blob-URL' => $location])->throw();
    }

    public function publicUrl(?string $location): ?string
    {
        if (!$location) {
            return null;
        }

        if ($this->isBlobUrl($location, 'public')) {
            return $location;
        }

        if ($this->isEnabled() || !Storage::disk('public')->exists($location)) {
            return null;
        }

        return asset('storage/' . $location);
    }

    public function privateFileResponse(?string $location): Response
    {
        abort_unless($location, 404);

        if (!$this->isBlobUrl($location, 'private')) {
            abort_unless(Storage::disk('local')->exists($location), 404);

            return response()->file(Storage::disk('local')->path($location), [
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        if (!$this->isEnabled()) {
            throw new LogicException('Vercel Blob storage is not configured for this deployment.');
        }

        $response = $this->request('GET', 'private', ['X-Blob-URL' => $location]);

        if ($response->status() === 404) {
            abort(404);
        }

        $response->throw();

        return response($response->body(), 200, [
            'Content-Type' => $response->header('Content-Type', 'application/octet-stream'),
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function request(string $method, string $access, array $headers = [], ?string $body = null): HttpResponse
    {
        $url = config('services.vercel_blob.bridge_url');
        $secret = config('services.vercel_blob.bridge_secret');

        if (!is_string($url) || $url === '' || !is_string($secret) || strlen($secret) < 32) {
            throw new LogicException('Vercel Blob bridge URL and a 32-character bridge secret are required.');
        }

        $request = Http::timeout(45)
            ->withHeaders($headers + [
                'X-Blob-Access' => $access,
                'X-Blob-Bridge-Secret' => $secret,
            ]);

        if ($body !== null) {
            $request = $request->withBody($body, $headers['Content-Type'] ?? 'application/octet-stream');
        }

        return match ($method) {
            'PUT' => $request->put($url),
            'GET' => $request->get($url),
            'DELETE' => $request->delete($url),
            default => throw new LogicException('Unsupported Blob operation.'),
        };
    }

    private function isEnabled(): bool
    {
        if (!config('services.vercel_blob.enabled')) {
            return false;
        }

        if (!config('services.vercel_blob.bridge_url') || !config('services.vercel_blob.bridge_secret')) {
            throw new LogicException('Vercel Blob bridge configuration is incomplete.');
        }

        return true;
    }

    private function isBlobUrl(string $location, string $access): bool
    {
        $host = parse_url($location, PHP_URL_HOST);
        $expectedHost = $access === 'private'
            ? '.private.blob.vercel-storage.com'
            : '.public.blob.vercel-storage.com';

        return parse_url($location, PHP_URL_SCHEME) === 'https'
            && is_string($host)
            && str_ends_with($host, $expectedHost);
    }
}
