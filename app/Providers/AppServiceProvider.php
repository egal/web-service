<?php

namespace App\Providers;

use Egal\Core\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->mergeConfigFrom(base_path('vendor/egal/framework/src/Core/config/app.php'), 'app');
        $this->mergeConfigFrom(base_path('vendor/egal/framework/src/Core/config/bus.php'), 'bus');
    }

}
