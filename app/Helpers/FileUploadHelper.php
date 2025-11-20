<?php

namespace App\Helpers;

use App\Exceptions\FileUploadException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class FileUploadHelper
{
  protected $disk;

  public function __construct()
  {
    $this->disk = 'public';
    Log::channel('uploader_log')->info("FileUploadHelper initialized with disk: {$this->disk}");
  }

  /**
   * Fix image orientation manually using EXIF data
   */
  private function fixOrientation($image, $file)
  {
    try {
      $exif = @exif_read_data($file->getPathname());
      if (!empty($exif['Orientation'])) {
        switch ($exif['Orientation']) {
          case 3:
            $image->rotate(180);
            break;
          case 6:
            $image->rotate(90);
            break;
          case 8:
            $image->rotate(-90);
            break;
        }
      }
    } catch (\Throwable $e) {
      // Ignore if EXIF is missing
    }

    return $image;
  }

  /**
   * Upload image and convert to WebP
   */
  public function imageUploader($file, $path, $width = null, $height = null, $old_image = null)
  {
    try {
      if (!$file) {
        throw new FileUploadException('No file provided.');
      }

      if ($old_image) {
        $this->fileUnlink($old_image);
      }

      if (!$file->isValid()) {
        throw new FileUploadException('Uploaded file is invalid.');
      }

      $mime = $file->getMimeType();
      $mimeToExt = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/svg+xml' => 'svg',
      ];

      $ext = $mimeToExt[$mime] ?? null;
      if (!$ext) {
        throw new FileUploadException("Unsupported MIME type: {$mime}");
      }

      // Handle SVG directly
      if ($ext === 'svg') {
        $fileName = uniqid() . '.svg';
        $storagePath = $path . '/' . $fileName;
        Storage::disk($this->disk)->makeDirectory($path);
        Storage::disk($this->disk)->put($storagePath, file_get_contents($file), 'public');
        return $storagePath;
      }

      // Raster images
      $manager = new ImageManager(['driver' => 'gd']);
      $img = $manager->read($file);

      // Manual EXIF orientation
      $img = $this->fixOrientation($img, $file);

      // Resize if needed
      if ($width || $height) {
        $img->resize($width, $height, function ($constraint) {
          $constraint->aspectRatio();
        });
      }

      // WebP conversion
      $fileName = uniqid() . '.webp';
      $tmpPath = storage_path('app/temp/' . $fileName);
      Storage::disk('local')->makeDirectory('temp');

      // Adjust quality based on size
      $fileSizeMB = $file->getSize() / (1024 * 1024);
      $quality = match (true) {
        $fileSizeMB > 10 => 30,
        $fileSizeMB > 5 => 40,
        $fileSizeMB > 1 => 60,
        $fileSizeMB > 0.5 => 70,
        default => 85,
      };

      $img->encode('webp', $quality)->save($tmpPath);

      $storagePath = $path . '/' . $fileName;
      Storage::disk($this->disk)->makeDirectory($path);
      Storage::disk($this->disk)->put($storagePath, file_get_contents($tmpPath), 'public');

      @unlink($tmpPath);

      return $storagePath;
    } catch (\Throwable $e) {
      Log::channel('uploader_log')->error("Image Upload Exception", [
        'error' => $e->getMessage(),
        'file' => $file?->getClientOriginalName(),
        'trace' => $e->getTraceAsString(),
        'path' => $path,
        'disk' => $this->disk
      ]);

      throw new FileUploadException("Failed to upload image: " . $e->getMessage());
    }
  }

  /**
   * Get full URL for stored image
   */
  public function getImagePath($imagePath)
  {
    if ($imagePath && Storage::disk($this->disk)->exists($imagePath)) {
      return Storage::disk($this->disk)->url($imagePath);
    }
    return asset('storage/default/demo_user.png');
  }

  /**
   * Delete a file from storage
   */
  public function fileUnlink($path)
  {
    if (!$path) return false;
    if (Storage::disk($this->disk)->exists($path)) {
      Storage::disk($this->disk)->delete($path);
      return true;
    }
    return false;
  }
}
