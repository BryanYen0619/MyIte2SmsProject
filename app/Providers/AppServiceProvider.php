<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
//        // forcing https routes when using ssl
//        if (!\App::environment('local')) {
//            \URL::forceSchema('https');
//        }
//        // allow origin
//        header('Access-Control-Allow-Origin: *');
//        // add any additional headers you need to support here
//        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Requested-With');
//
//        // Carbon Time Locale
//        \Carbon\Carbon::setLocale('zh-TW');
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
