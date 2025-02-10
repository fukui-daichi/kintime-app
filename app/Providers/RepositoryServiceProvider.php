<?php

namespace App\Providers;

use App\Repositories\Interfaces\TimecardRepositoryInterface;
use App\Repositories\Eloquent\TimecardRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(TimecardRepositoryInterface::class, TimecardRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
