<?php

namespace App\Providers;

use App\Services\AppSettings;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        View::composer(['welcome', 'familia.layouts.app', 'public.layouts.app'], function ($view) {
            try {
                $view->with('branding', AppSettings::all());
            } catch (\Throwable) {
                $view->with('branding', AppSettings::defaults());
            }
        });
    }
}
