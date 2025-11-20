<?php

namespace App\Helpers;

use App\Exceptions\FileUploadException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Image;

class FileUploadHelper
{
  protected $disk;

  public function __construct()
  {
    // $this->disk = app()->environment('production') ? 's3' : 'public';
    $this->disk = 'public';
    Log::channel('uploader_log')->info("FileUploadHelper initialized with disk: {$this->disk}");
  }

  /**
   * Upload image with optional resize and webp conversion
   */
  public function imageUploader($file, $path, $width = null, $height = null, $old_image = null)
  {
    try {
      if (!$file) {
        Log::channel('uploader_log')->error("Image Upload Error: No file provided.");
        throw new FileUploadException('No file was provided for upload.');
      }

      // Delete old image if provided
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
        throw new FileUploadException("Unsupported file type: {$mime}");
      }

      // Handle SVG separately
      if ($ext === 'svg') {
        $fileName = uniqid() . '.svg';
        $storagePath = $path . '/' . $fileName;
        Storage::disk($this->disk)->makeDirectory($path);
        Storage::disk($this->disk)->put($storagePath, file_get_contents($file), 'public');
        return $storagePath;
      }

      // Raster images: read and orient
      $img = Image::read($file)->orient();

      if ($width || $height) {
        $img->resize($width, $height, function ($constraint) {
          $constraint->aspectRatio();
        });
      }

      // Convert to webp
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

  /**
   * Get public URL for image
   */
  public function getImagePath($imagePath)
  {
    if ($imagePath && Storage::disk($this->disk)->exists($imagePath)) {
      return Storage::disk($this->disk)->url($imagePath);
    }
    return asset('storage/default/demo_user.png');
  }

  /**
   * Delete a file
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

  /**
   * Upload PDF file
   */
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
