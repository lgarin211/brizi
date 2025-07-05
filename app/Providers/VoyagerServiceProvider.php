<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Voyager;
use App\Voyager\Widgets\UpdateCache;

class VoyagerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // if (class_exists('Voyager')) {
        //     \Voyager::addWidget(UpdateCache::class);
        // }
    }

    public function register()
    {
        //
    }
}
