<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Auction;
use App\Policies\AuctionPolicy;
use Illuminate\Support\Facades\Gate;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
   public function boot(): void
{
    Gate::policy(Auction::class, AuctionPolicy::class);
}
}
