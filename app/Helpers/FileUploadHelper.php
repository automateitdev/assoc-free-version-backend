<?php

namespace App\Helpers;

use App\Exceptions\FileUploadException;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class FileUploadHelper
{
  protected $disk;

  public function __construct()
  {
    // $this->disk = app()->environment('production') ? 's3' : 'public';
    $this->disk = 'public';
    Log::channel('uploader_log')->info("FileUploadClass initialized with disk: {$this->disk}");
  }

  public function imageUploader($file, $path, $width = null, $height = null, $old_image = null)
  {
    try {
      if (!$file || !$file->isValid()) {
        throw new FileUploadException('Uploaded file is invalid.');
      }

      $manager = new ImageManager(new Driver());
      $img = $manager->read($file);

      // Resize with aspect ratio (v3 syntax)
      if ($width || $height) {
        $img->resize($width, $height, keepAspectRatio: true);
      }

      // Convert to webp
      $fileName = uniqid() . '.webp';
      $storagePath = $path . '/' . $fileName;

      $fileSizeMB = $file->getSize() / (1024 * 1024);
      $quality = match (true) {
        $fileSizeMB > 10 => 30,
        $fileSizeMB > 5 => 40,
        $fileSizeMB > 1 => 60,
        $fileSizeMB > 0.5 => 70,
        default => 85,
      };

      // Encode returns EncodedImage, so cast to string
      $encoded = (string) $img->encodeByMediaType('image/webp', quality: $quality);

      Storage::disk($this->disk)->makeDirectory($path);
      Storage::disk($this->disk)->put($storagePath, $encoded, 'public');

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

    Log::channel('uploader_log')->info('Trying to delete: ' . $path);

    if (Storage::disk($this->disk)->exists($path)) {
      Storage::disk($this->disk)->delete($path);
      Log::channel('uploader_log')->info('Deleted successfully: ' . $path);
      return true;
    }

    Log::channel('uploader_log')->warning('File not found: ' . $path);
    return false;
  }

  public function pdfUploader($file, $path, $old_file = null)
  {
    try {
      if (!$file) {
        throw new FileUploadException('No PDF file was provided.');
      }

      if ($old_file) {
        $this->fileUnlink($old_file);
      }

      if (strtolower($file->getClientOriginalExtension()) !== 'pdf') {
        throw new FileUploadException('Only PDF files are supported.');
      }

      $fileName = uniqid() . '.pdf';
      $uploadPath = $path . '/' . date('Y-m-d') . '/' . $fileName;

      Storage::disk($this->disk)->put($uploadPath, file_get_contents($file), 'public');

      return $uploadPath;
    } catch (\Throwable $e) {
      Log::channel('uploader_log')->error("PDF Upload Error", [
        'error' => $e->getMessage(),
        'file' => $file?->getClientOriginalName(),
        'trace' => $e->getTraceAsString(),
        'path' => $path
      ]);

      throw new FileUploadException("Failed to upload PDF: " . $e->getMessage());
    }
  }
}
