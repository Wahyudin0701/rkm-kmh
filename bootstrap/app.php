<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, $request) {
            $postMax = ini_get('post_max_size') ?: '256M';
            $uploadMax = ini_get('upload_max_filesize') ?: '256M';
            return back()->with('error', "Ukuran file yang Anda kirim terlalu besar dan melebihi batas konfigurasi server PHP (post_max_size: {$postMax}, upload_max_filesize: {$uploadMax}). Harap naikkan batas konfigurasi php.ini atau gunakan file yang lebih kecil.");
        });
    })->create();
