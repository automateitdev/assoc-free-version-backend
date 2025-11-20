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
      if (!$file) {
        Log::channel('uploader_log')->error("Image Upload Error: No file provided.");
        throw new FileUploadException('No file was provided for upload.');
      }

      Log::channel('uploader_log')->info("Starting image upload process", [
        'file_name' => $file->getClientOriginalName(),
        'file_size' => $file->getSize(),
        'file_type' => $file->getMimeType(),
        'path' => $path,
        'width' => $width,
        'height' => $height,
        'disk' => $this->disk
      ]);

      if ($old_image) {
        Log::channel('uploader_log')->info("Attempting to delete old image: {$old_image}");
        $this->fileUnlink($old_image);
      }

      if (!$file->isValid()) {
        throw new FileUploadException('Uploaded file is invalid or corrupted.');
      }

      // ✅ Detect MIME type and map to file extension
      $mime = $file->getMimeType();

      $mimeToExt = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/svg+xml' => 'svg',
      ];

      $ext = $mimeToExt[$mime] ?? null;

      if (!$ext || !in_array($ext, array_values($mimeToExt))) {
        Log::channel('uploader_log')->error("Unsupported or undetectable MIME type: {$mime}", [
          'file_name' => $file->getClientOriginalName()
        ]);
        throw new FileUploadException("File type '{$mime}' is not supported. Allowed types: jpg, jpeg, png, gif, svg, webp.");
      }

      // ✅ Handle SVG separately (no resizing or re-encoding)
      if ($ext === 'svg') {
        $fileName = uniqid() . '.svg';
        $storagePath = $path . '/' . $fileName;
        Storage::disk($this->disk)->makeDirectory($path);
        Storage::disk($this->disk)->put($storagePath, file_get_contents($file), 'public');
        return $storagePath;
      }

      // ✅ Handle raster images (JPG, PNG, etc.)
      // $img = Image::make($file)->orientate();

      $manager = new ImageManager(new Driver());

      // read image from file system
      $img = $manager->read($file);

      if ($width || $height) {
        $img->resize($width, $height, function ($constraint) {
          $constraint->aspectRatio();
        });
      }

      // Convert to webp format
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

      $img->encode('webp', $quality)->save($tmpPath);

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
