<?php

namespace App\Jobs;

use App\Models\Venue;
use App\Services\FileUploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class UploadVenueImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $venueId,
        public string $tempImagePath
    ) {}

    /**
     * Execute the job.
     */
    public function handle(FileUploadService $fileUploadService): void
    {
        $provider = config('services.file_upload.provider', 'local');
        
        Log::info("Starting image upload for venue {$this->venueId}", [
            'temp_path' => $this->tempImagePath,
            'provider' => $provider,
        ]);

        try {
            // Check if file exists
            if (!Storage::disk('public')->exists($this->tempImagePath)) {
                Log::error("Temp file not found: {$this->tempImagePath}");
                return;
            }

            // Skip upload if provider is local (already stored)
            if ($provider === 'local') {
                Log::info("Provider is local, skipping upload", ['venue_id' => $this->venueId]);
                return;
            }

            // Upload to configured provider (s3, r2, google-drive)
            $result = $fileUploadService->uploadFromStorage($this->tempImagePath, 'public');

            Log::info("Upload successful", [
                'venue_id' => $this->venueId,
                'provider' => $provider,
                'url' => $result['url'],
            ]);

            // Update venue with new URL
            $venue = Venue::find($this->venueId);
            if ($venue) {
                $venue->update(['image' => $result['url']]);
                Log::info("Venue image updated", ['venue_id' => $this->venueId]);
            }

            // Delete temp file
            Storage::disk('public')->delete($this->tempImagePath);
            Log::info("Temp file deleted: {$this->tempImagePath}");

        } catch (\Exception $e) {
            Log::error("Failed to upload venue image", [
                'venue_id' => $this->venueId,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("UploadVenueImageJob failed permanently", [
            'venue_id' => $this->venueId,
            'temp_path' => $this->tempImagePath,
            'error' => $exception->getMessage(),
        ]);

        // Keep the temp file for manual recovery
    }
}
