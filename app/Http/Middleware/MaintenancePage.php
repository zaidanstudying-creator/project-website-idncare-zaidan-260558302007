<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MaintenancePage
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! SiteSetting::maintenanceEnabled()) {
            return $next($request);
        }

        $user = $request->user();
        $isActiveAdmin = $user !== null
            && $user->is_active
            && in_array($user->role, [
                UserRole::ADMIN_SARPRAS,
                UserRole::ADMIN_KEMAHASISWAAN,
                UserRole::SUPER_ADMIN,
            ], true);
        $isProtectedAdminRoute = $request->routeIs(
            'dashboard',
            'dashboard.admin-sarpras',
            'dashboard.admin-kemahasiswaan',
            'dashboard.super-admin',
            'password.change',
            'admin.*',
        );
        $isLoginRequest = $request->routeIs('login')
            || ($request->is('livewire/update') && $user === null);

        if ($isActiveAdmin || $isProtectedAdminRoute) {
            if ($request->routeIs('home')) {
                return redirect()->route(match ($user->role) {
                    UserRole::ADMIN_SARPRAS => 'dashboard.admin-sarpras',
                    UserRole::ADMIN_KEMAHASISWAAN => 'dashboard.admin-kemahasiswaan',
                    UserRole::SUPER_ADMIN => 'dashboard.super-admin',
                });
            }

            return $next($request);
        }

        if ($isLoginRequest || $request->is('up')) {
            return $next($request);
        }

        $maintenancePage = public_path('maintenance/index.html');

        if (! is_file($maintenancePage)) {
            abort(503, 'Website sedang maintenance. Silakan coba lagi nanti.');
        }

        $maintenanceContent = file_get_contents($maintenancePage);

        if (! is_string($maintenanceContent)) {
            abort(503, 'Website sedang maintenance. Silakan coba lagi nanti.');
        }

        $setting = SiteSetting::current();
        $title = e($setting?->maintenance_title ?: 'Waduh, maaf ya!');
        $message = nl2br(e($setting?->maintenance_message ?: 'Website sedang dirawat sebentar. Kami segera kembali.'));
        $maintenanceContent = str_replace(
            ['Waduh, maaf ya!', 'Website IDN CARE sedang maintenance sebentar. Kami lagi merapikan beberapa hal supaya layanan makin nyaman. Tungguin ya!'],
            [$title, $message],
            $maintenanceContent,
        );

        return response($maintenanceContent, Response::HTTP_SERVICE_UNAVAILABLE, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
            'Retry-After' => '3600',
        ]);
    }
}
