<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Exception;

class FileUploadService
{
    protected string $provider;

    public function __construct()
    {
        // Get storage provider from config (local, s3, r2, google-drive)
        $this->provider = config('services.file_upload.provider', 'local');
    }

    /**
     * Upload a file using the configured provider.
     *
     * @param UploadedFile $file
     * @param string $directory
     * @return array ['path' => string, 'url' => string]
     */
    public function upload(UploadedFile $file, string $directory = 'uploads'): array
    {
        return match ($this->provider) {
            's3' => $this->uploadToS3($file, $directory),
            'r2' => $this->uploadToR2($file, $directory),
            'google-drive' => $this->uploadToGoogleDrive($file, $directory),
            default => $this->uploadToLocal($file, $directory),
        };
    }

    /**
     * Upload from a stored file path.
     *
     * @param string $storagePath Path relative to storage disk
     * @param string $disk Storage disk name
     * @return array
     */
    public function uploadFromStorage(string $storagePath, string $disk = 'public'): array
    {
        $fullPath = Storage::disk($disk)->path($storagePath);
        $mimeType = Storage::disk($disk)->mimeType($storagePath);
        $fileName = basename($storagePath);
        $content = Storage::disk($disk)->get($storagePath);
        $directory = dirname($storagePath);

        return match ($this->provider) {
            's3' => $this->uploadToS3FromPath($fullPath, $fileName, $mimeType, $directory),
            'r2' => $this->uploadToR2FromPath($fullPath, $fileName, $mimeType, $directory),
            'google-drive' => $this->uploadToGoogleDriveFromPath($fullPath, $fileName, $mimeType),
            default => [
                'path' => $storagePath,
                'url' => Storage::disk($disk)->url($storagePath),
            ],
        };
    }

    /**
     * Delete a file from storage.
     *
     * @param string $path
     * @return bool
     */
    public function delete(string $path): bool
    {
        return match ($this->provider) {
            's3' => Storage::disk('s3')->delete($path),
            'r2' => Storage::disk('r2')->delete($path),
            'google-drive' => $this->deleteFromGoogleDrive($path),
            default => Storage::disk('public')->delete($path),
        };
    }

    /**
     * Generate a unique filename.
     */
    protected function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Ymd_His');
        $random = Str::random(8);
        
        return "{$timestamp}_{$random}.{$extension}";
    }

    // ═══════════════════════════════════════════════════════════════════════
    // LOCAL STORAGE
    // ═══════════════════════════════════════════════════════════════════════

    protected function uploadToLocal(UploadedFile $file, string $directory): array
    {
        $filename = $this->generateFilename($file);
        $path = $file->storeAs($directory, $filename, 'public');
        
        return [
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // AMAZON S3
    // ═══════════════════════════════════════════════════════════════════════

    protected function uploadToS3(UploadedFile $file, string $directory): array
    {
        $filename = $this->generateFilename($file);
        $path = $file->storeAs($directory, $filename, 's3');
        
        return [
            'path' => $path,
            'url' => Storage::disk('s3')->url($path),
        ];
    }

    protected function uploadToS3FromPath(string $fullPath, string $fileName, string $mimeType, string $directory): array
    {
        $timestamp = now()->format('Ymd_His');
        $random = Str::random(8);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFilename = "{$timestamp}_{$random}.{$extension}";
        $path = $directory . '/' . $newFilename;

        Storage::disk('s3')->put($path, file_get_contents($fullPath), [
            'ContentType' => $mimeType,
        ]);

        return [
            'path' => $path,
            'url' => Storage::disk('s3')->url($path),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // CLOUDFLARE R2 (S3-compatible)
    // ═══════════════════════════════════════════════════════════════════════

    protected function uploadToR2(UploadedFile $file, string $directory): array
    {
        $filename = $this->generateFilename($file);
        $path = $file->storeAs($directory, $filename, 'r2');
        
        // R2 public URL format
        $publicUrl = config('services.cloudflare.r2_public_url');
        $url = $publicUrl ? "{$publicUrl}/{$path}" : Storage::disk('r2')->url($path);
        
        return [
            'path' => $path,
            'url' => $url,
        ];
    }

    protected function uploadToR2FromPath(string $fullPath, string $fileName, string $mimeType, string $directory): array
    {
        $timestamp = now()->format('Ymd_His');
        $random = Str::random(8);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFilename = "{$timestamp}_{$random}.{$extension}";
        $path = $directory . '/' . $newFilename;

        Storage::disk('r2')->put($path, file_get_contents($fullPath), [
            'ContentType' => $mimeType,
        ]);

        $publicUrl = config('services.cloudflare.r2_public_url');
        $url = $publicUrl ? "{$publicUrl}/{$path}" : Storage::disk('r2')->url($path);

        return [
            'path' => $path,
            'url' => $url,
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // GOOGLE DRIVE
    // ═══════════════════════════════════════════════════════════════════════

    protected function uploadToGoogleDrive(UploadedFile $file, string $directory): array
    {
        return $this->uploadToGoogleDriveFromPath(
            $file->getRealPath(),
            $file->getClientOriginalName(),
            $file->getMimeType()
        );
    }

    protected function uploadToGoogleDriveFromPath(string $fullPath, string $fileName, string $mimeType): array
    {
        $accessToken = $this->getGoogleAccessToken();
        $folderId = config('services.google.drive_folder_id', '');
        
        // Generate unique filename
        $uniqueName = time() . '-' . uniqid() . '-' . $fileName;

        // Prepare metadata
        $metadata = ['name' => $uniqueName];
        if ($folderId) {
            $metadata['parents'] = [$folderId];
        }

        // Read file content
        $content = file_get_contents($fullPath);

        // Create multipart body
        $boundary = '-------' . uniqid();
        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
        $body .= json_encode($metadata) . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: {$mimeType}\r\n\r\n";
        $body .= $content . "\r\n";
        $body .= "--{$boundary}--";

        // Upload file
        $httpClient = Http::withToken($accessToken)
            ->withHeaders(['Content-Type' => "multipart/related; boundary={$boundary}"])
            ->withBody($body, "multipart/related; boundary={$boundary}");
        
        if (app()->environment('local')) {
            $httpClient = $httpClient->withOptions(['verify' => false]);
        }
        
        $response = $httpClient->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id');

        if (!$response->successful()) {
            throw new Exception('Failed to upload to Google Drive: ' . $response->body());
        }

        $fileId = $response->json('id');

        // Set file permission to public
        $this->setGoogleDrivePublicPermission($fileId);

        return [
            'path' => $fileId,
            'url' => "https://lh3.googleusercontent.com/d/{$fileId}",
        ];
    }

    protected function getGoogleAccessToken(): string
    {
        $httpClient = Http::asForm();
        if (app()->environment('local')) {
            $httpClient = $httpClient->withOptions(['verify' => false]);
        }

        $response = $httpClient->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => config('services.google.refresh_token'),
            'grant_type' => 'refresh_token',
        ]);

        if (!$response->successful()) {
            throw new Exception('Failed to get Google access token: ' . $response->body());
        }

        return $response->json('access_token');
    }

    protected function setGoogleDrivePublicPermission(string $fileId): void
    {
        try {
            $accessToken = $this->getGoogleAccessToken();
            
            $httpClient = Http::withToken($accessToken);
            if (app()->environment('local')) {
                $httpClient = $httpClient->withOptions(['verify' => false]);
            }
            
            $httpClient->post("https://www.googleapis.com/drive/v3/files/{$fileId}/permissions", [
                'type' => 'anyone',
                'role' => 'reader',
            ]);
        } catch (Exception $e) {
            logger()->warning("Failed to set public permission for file {$fileId}: " . $e->getMessage());
        }
    }

    protected function deleteFromGoogleDrive(string $fileId): bool
    {
        try {
            $accessToken = $this->getGoogleAccessToken();
            
            $httpClient = Http::withToken($accessToken);
            if (app()->environment('local')) {
                $httpClient = $httpClient->withOptions(['verify' => false]);
            }
            
            $httpClient->delete("https://www.googleapis.com/drive/v3/files/{$fileId}");
            return true;
        } catch (Exception $e) {
            logger()->error("Failed to delete file {$fileId}: " . $e->getMessage());
            return false;
        }
    }
}
