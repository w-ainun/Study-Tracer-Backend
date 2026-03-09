<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

trait GeneratesThumbnail
{
    /**
     * Store an uploaded image and generate a thumbnail version.
     *
     * @param  UploadedFile  $file       The uploaded image file.
     * @param  string        $directory  Storage subdirectory (e.g. 'alumni/foto').
     * @param  int           $width      Thumbnail max width in pixels.
     * @param  int           $height     Thumbnail max height in pixels.
     * @return array{path: string, thumbnail: string}  Original and thumbnail paths.
     */
    protected function storeWithThumbnail(
        UploadedFile $file,
        string $directory,
        int $width = 150,
        int $height = 150
    ): array {
        // Store original
        $originalPath = $file->store($directory, 'public');

        // Generate thumbnail
        $thumbnailDir = $directory . '/thumbnails';
        $filename = basename($originalPath);
        $thumbnailPath = $thumbnailDir . '/' . $filename;

        $manager = new ImageManager(new Driver());
        $image = $manager->read(Storage::disk('public')->path($originalPath));
        $image->scaleDown(width: $width, height: $height);

        // Ensure thumbnail directory exists and save
        Storage::disk('public')->makeDirectory($thumbnailDir);

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $encoded = match ($extension) {
            'png' => $image->toPng(),
            default => $image->toJpeg(80),
        };
        file_put_contents(Storage::disk('public')->path($thumbnailPath), (string) $encoded);

        return [
            'path' => $originalPath,
            'thumbnail' => $thumbnailPath,
        ];
    }

    /**
     * Delete an image and its thumbnail from storage.
     */
    protected function deleteWithThumbnail(string $originalPath): void
    {
        Storage::disk('public')->delete($originalPath);

        $dir = dirname($originalPath);
        $filename = basename($originalPath);
        $thumbnailPath = $dir . '/thumbnails/' . $filename;

        Storage::disk('public')->delete($thumbnailPath);
    }

    /**
     * Get the thumbnail path for a given original image path.
     */
    public static function thumbnailPath(?string $originalPath): ?string
    {
        if (!$originalPath) {
            return null;
        }

        $dir = dirname($originalPath);
        $filename = basename($originalPath);

        return $dir . '/thumbnails/' . $filename;
    }
}
