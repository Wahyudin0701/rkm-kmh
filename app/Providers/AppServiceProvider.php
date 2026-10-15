<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        \Carbon\Carbon::setLocale('id');
        date_default_timezone_set(config('app.timezone', 'Asia/Jakarta'));

        // Super Admin bypass: secara otomatis memiliki izin untuk semua ability
        Gate::before(function ($user, $ability) {
            return ($user->role === 'super_admin' || $user->hasRole('super_admin')) ? true : null;
        });
    }
}
