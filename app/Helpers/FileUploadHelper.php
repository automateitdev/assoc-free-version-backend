<?php

namespace App\Helpers;

use App\Exceptions\FileUploadException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager; // ✅ use ImageManager, not Facade
use Intervention\Image\Drivers\Gd\Driver;
class FileUploadHelper
{
  protected $disk;

  public function __construct()
  {
    $this->disk = 'public';
    Log::channel('uploader_log')->info("FileUploadClass initialized with disk: {$this->disk}");
  }

  public function imageUploader($file, $path, $width = null, $height = null, $old_image = null)
  {
    try {
      if (!$file) {
        throw new FileUploadException('No file was provided for upload.');
      }

      if ($old_image) {
        $this->fileUnlink($old_image);
      }

      if (!$file->isValid()) {
        throw new FileUploadException('Uploaded file is invalid or corrupted.');
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

      // Handle SVG
      if ($ext === 'svg') {
        $fileName = uniqid() . '.svg';
        $storagePath = $path . '/' . $fileName;
        Storage::disk($this->disk)->makeDirectory($path);
        Storage::disk($this->disk)->put($storagePath, file_get_contents($file), 'public');
        return $storagePath;
      }

      // Raster images
      $manager = new ImageManager(new Driver()); // or 'imagick'
      $img = $manager->read($file)->autoOrient();


      if ($width || $height) {
        $img->resize($width, $height, function ($constraint) {
          $constraint->aspectRatio();
        });
      }

      // WebP conversion
      $fileName = uniqid() . '.webp';
      $storagePath = $path . '/' . $fileName;
      $tmpPath = storage_path('app/temp/' . $fileName);
      Storage::disk('local')->makeDirectory('temp');

      $fileSizeMB = $file->getSize() / (1024 * 1024);
      $quality = match (true) {
        $fileSizeMB > 10 => 30,
        $fileSizeMB > 5 => 40,
        $fileSizeMB > 1 => 60,
        $fileSizeMB > 0.5 => 70,
        default => 85,
      };

      $img->encode('webp', $quality);
      $img->save($tmpPath);

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

  public function getImagePath($imagePath)
  {
    if ($imagePath && Storage::disk($this->disk)->exists($imagePath)) {
      return Storage::disk($this->disk)->url($imagePath);
    }
    return asset('storage/default/demo_user.png');
  }

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
