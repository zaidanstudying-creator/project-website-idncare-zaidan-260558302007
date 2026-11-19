<?php

use App\Console\Commands\CreateSuperAdmin;
use App\Console\Commands\DiagnoseGroqTitleSuggestions;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\MaintenancePage;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\ValidateAccountSession;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        CreateSuperAdmin::class,
        DiagnoseGroqTitleSuggestions::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->web(append: [MaintenancePage::class]);
        $middleware->alias([
            'force.password.change' => ForcePasswordChange::class,
            'role' => RoleMiddleware::class,
            'account.session' => ValidateAccountSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
