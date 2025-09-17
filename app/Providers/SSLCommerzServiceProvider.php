<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class SSLCommerzServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
         // Register the singleton
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->setSSLCommerzConfig();
    }

    protected function setSSLCommerzConfig()
    {
        if ($user = Auth::user()) {
            $instituteDetailsId = $user->institute_details_id;
            $sslInfo = DB::table('ssl_infos')
                ->where('institute_details_id', $instituteDetailsId)
                ->first();

            if ($sslInfo) {
                Config::set('sslcommerz.apiCredentials.store_id', $sslInfo->store_id);
                Config::set('sslcommerz.apiCredentials.store_password', $sslInfo->store_password);
            }
        }
    }
}
