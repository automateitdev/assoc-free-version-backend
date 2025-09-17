<?php


use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
/**
 * generateSlug
 *
 * @param  mixed $value
 * @return void
 */


function generateSlug($value)
{
    try {
        return preg_replace('/\s+/u', '-', trim($value));
    } catch (\Exception $e) {
        return '';
    }
}

function getStorageImage($path, $name, $is_user = false, $resizable = false)
{
    if ($name && Storage::disk('public')->exists($path . '/' . $name)) {
        if ($resizable) {
            $full_path = 'storage/' . $path . '/' . $name;
            if ($name) {
                return $full_path;
            }
        }
        return app('url')->asset('storage/' . $path . '/' . $name);
    }
    return $is_user ? getUserDefaultImage() : getDefaultImage();
}
function getUserDefaultImage()
{
    return static_asset('images/user_default.png');
}

/**
 * getDefaultImage
 *
 * @return void
 */
function getDefaultImage()
{
    return static_asset('images/default.png');
}

if (!function_exists('randomNumberGenerate')) {

    function randomNumberGenerate()
    {
        return (string) substr(Str::uuid(), 0, 8);
    }

}
if (!function_exists('eiin_generage')) {

    function eiin_generage($digits = 11)
    {
        return ( str_pad(rand(0, pow(10, $digits)-1), $digits, '0', STR_PAD_LEFT));
    }

}

if (!function_exists('static_asset')) {

    function static_asset($path = null, $secure = null)
    {
        if (strpos(php_sapi_name(), 'cli') !== false || defined('LARAVEL_START_FROM_PUBLIC')) :
            return app('url')->asset($path, $secure);
        else :
            $all_null = ($path == null && $secure == null) ? '/' : '';
            return app('url')->asset('public/' . $path, $secure) . $all_null;
        endif;
    }

    if (!function_exists('create_log')) {

        function create_log($log_type, $message, $channel = null)
        {
            switch ($log_type) {
                case 'emergency':
                    if(!is_null($channel)){
                        Log::channel($channel)->emergency($message);
                    }else{
                        Log::emergency($message);
                    }
                break;
                case 'alert':
                    if(!is_null($channel)){
                        Log::channel($channel)->alert($message);
                    }else{
                        Log::alert($message);
                    }
                break;
                case 'critical':
                    if(!is_null($channel)){
                        Log::channel($channel)->critical($message);
                    }else{
                        Log::critical($message);
                    }
                break;
                case 'error':
                    if(!is_null($channel)){
                        Log::channel($channel)->error($message);
                    }else{
                        Log::error($message);
                    }
                break;
                case 'warning':
                    if(!is_null($channel)){
                        Log::channel($channel)->warning($message);
                    }else{
                        Log::warning($message);
                    }
                break;
                case 'notice':
                    if(!is_null($channel)){
                        Log::channel($channel)->notice($message);
                    }else{
                        Log::notice($message);
                    }
                break;
                case 'debug':
                    if(!is_null($channel)){
                        Log::channel($channel)->debug($message);
                    }else{
                        Log::debug($message);
                    }
                break;
                case 'info':
                    if(!is_null($channel)){
                        Log::channel($channel)->info($message);
                    }else{
                        Log::info($message);
                    }
                break;
                
                default:
                    if(!is_null($channel)){
                        Log::channel($channel)->info($message);
                    }else{
                        Log::info($message);
                    }
                    break;
               
            }
        }
    }

    if (!function_exists('get_public_ip')) {

        function get_public_ip()
        {
            try {
                $client = new Client();
                $response = $client->get('ipinfo.io/103.142.171.4?token=05b3f3ae3fd168');
                $data = json_decode($response->getBody());
               return  $data->ip;
            } catch (\Exception $th) {
                return request()->ip();
            }
           
        }
    
    }
}
