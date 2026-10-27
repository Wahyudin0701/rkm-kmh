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
            if (in_array($user->role, ['super_admin', 'superadmin', 'admin'])) {
                return true;
            }
            try {
                if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                    return true;
                }
                return $user->hasRole('super_admin') ? true : null;
            } catch (\Throwable $e) {
                return null;
            }
        });

        // Konfigurasi durasi "Ingat Saya" (Remember Me) menjadi 30 hari (43.200 menit)
        if (class_exists(\Illuminate\Support\Facades\Auth::class)) {
            \Illuminate\Support\Facades\Auth::guard('web')->setRememberDuration(43200);
        }
    }
}
