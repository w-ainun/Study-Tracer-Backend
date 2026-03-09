<?php

namespace App\Console\Commands;

use App\Models\Alumni;
use App\Models\Lowongan;
use App\Traits\GeneratesThumbnail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateMissingThumbnails extends Command
{
    use GeneratesThumbnail;

    protected $signature = 'thumbnails:generate {--force}';
    protected $description = 'Generate thumbnails for all images that don\'t have one yet';

    public function handle()
    {
        $this->info('Generating missing thumbnails...');
        $force = $this->option('force');

        // Generate thumbnails for Alumni photos
        $this->info("\n📸 Processing Alumni photos...");
        $alumni = Alumni::whereNotNull('foto')->get();
        $alumniCount = 0;

        foreach ($alumni as $alum) {
            if ($this->generateThumbnailIfMissing($alum->foto, $force)) {
                $alumniCount++;
            }
        }
        $this->info("✅ Generated {$alumniCount} alumni thumbnails");

        // Generate thumbnails for Lowongan photos
        $this->info("\n📸 Processing Lowongan photos...");
        $lowongan = Lowongan::whereNotNull('foto_lowongan')->get();
        $lowonganCount = 0;

        foreach ($lowongan as $low) {
            if ($this->generateThumbnailIfMissing($low->foto_lowongan, $force)) {
                $lowonganCount++;
            }
        }
        $this->info("✅ Generated {$lowonganCount} lowongan thumbnails");

        $this->info("\n🎉 Done! Total: " . ($alumniCount + $lowonganCount) . " thumbnails generated");
        return 0;
    }

    private function generateThumbnailIfMissing(string $originalPath, bool $force = false): bool
    {
        // Check if original exists
        if (!Storage::disk('public')->exists($originalPath)) {
            $this->warn("⚠️  Original not found: {$originalPath}");
            return false;
        }

        // Get thumbnail path
        $thumbnailPath = static::thumbnailPath($originalPath);
        
        // Skip if thumbnail exists (unless --force)
        if (!$force && $thumbnailPath && Storage::disk('public')->exists($thumbnailPath)) {
            return false;
        }

        try {
            // Generate thumbnail manually
            $dir = dirname($originalPath);
            $thumbnailDir = $dir . '/thumbnails';
            $filename = basename($originalPath);
            $thumbPath = $thumbnailDir . '/' . $filename;

            $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
            $image = $manager->read(Storage::disk('public')->path($originalPath));
            $image->scaleDown(width: 150, height: 150);

            Storage::disk('public')->makeDirectory($thumbnailDir);

            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $encoded = match ($extension) {
                'png' => $image->toPng(),
                default => $image->toJpeg(80),
            };
            file_put_contents(Storage::disk('public')->path($thumbPath), (string) $encoded);

            $this->line("✓ Generated: {$thumbPath}");
            return true;
        } catch (\Exception $e) {
            $this->error("✗ Failed: {$originalPath} - " . $e->getMessage());
            return false;
        }
    }
}
