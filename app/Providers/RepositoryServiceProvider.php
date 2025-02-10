<?php

namespace App\Providers;

use App\Repositories\Eloquent\RequestRepository;
use App\Repositories\Interfaces\TimecardRepositoryInterface;
use App\Repositories\Eloquent\TimecardRepository;
use App\Repositories\Interfaces\RequestRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(TimecardRepositoryInterface::class, TimecardRepository::class);
        $this->app->bind(RequestRepositoryInterface::class, RequestRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
