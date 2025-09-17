<?php


namespace App\Traits;


use App\Utils\GlobalConstant;
use Illuminate\Support\Facades\Storage;
use Image;


trait ImageResizeTrait
{
    public function imageResize($image_resize, $file, $name, $path = 'others', $width = null, $height = null)
    {
        switch ($image_resize) {
            case GlobalConstant::IMAGE_RESIZE:
                $this->resize($file, $name, $path, $width, $height);
                break;

            case GlobalConstant::IMAGE_RESIZE_WITH_ASPECT_RATIO:
                $this->resizeWithAspectRatio($file, $name, $path, $width, $height);
                break;

            case GlobalConstant::IMAGE_FIT:
                $this->fit($file, $name, $path, $width, $height);
                break;

            case GlobalConstant::IMAGE_RESIZE_WITH_CANVAS:
                $this->resizeWithCanvas($file, $name, $path, $width, $height);
            break;

            default:
                break;
        };
    }

    protected function resize($file, $name, $path, $width = null, $height = null){

        if(!is_null($width) && !is_null($height)){
            $img = Image::make($file);
            $resize_imag = $img->resize($width, $height);

            Storage::disk('public')->put($path.'/'.$name, (string) $resize_imag->encode());
        }else{
            $file->storeAs($path, $name);
        }
    }

    protected function resizeWithAspectRatio($file, $name, $path, $width = null, $height = null){
        if(!is_null($width) || !is_null($height)){
            $img = Image::make($file);
            $resize_imag = $img->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

            Storage::disk('public')->put($path.'/'.$name, (string) $resize_imag->encode());
        }else{
            $file->storeAs($path, $name);
        }
    }

    protected function fit($file, $name, $path, $width, $height){
        if(!is_null($width) && !is_null($height)){
            $img = Image::make($file);
            $resize_imag = $img->fit($width, $height);

            Storage::disk('public')->put($path.'/'.$name, (string) $resize_imag->encode());
        }else{
            $file->storeAs($path, $name);
        }
    }

    protected function resizeWithCanvas($file, $name, $path, $width = null, $height = null){

        if(!is_null($width) && !is_null($height)){
            // create new image with transparent background color
            $background = Image::canvas($width, $height);

            $img = Image::make($file);

            // but keep aspect-ratio and do not size up,
            // so smaller sizes don't stretch
            $image = $img->resize($width, $height, function ($c) {
                $c->aspectRatio();
                $c->upsize();
            });

            // insert resized image centered into background
            $background->insert($image, 'center');

            // save or do whatever you like
            Storage::disk('public')->put($path.'/'.$name, (string) $background->encode());
        }else{
            $file->storeAs($path, $name);
        }
    }

}
