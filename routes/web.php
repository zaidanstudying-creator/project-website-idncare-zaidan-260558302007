<?php

use App\Enums\UserRole;
use App\Http\Middleware\PrivateFileResponseHeaders;
use App\Livewire\Pages\Admin\ActivityLogs\Index as AdminActivityLogsIndex;
use App\Livewire\Pages\Admin\Archive\Index as AdminArchiveIndex;
use App\Livewire\Pages\Admin\Categories\Index as AdminCategoriesIndex;
use App\Livewire\Pages\Admin\Locations\Index as AdminLocationsIndex;
use App\Livewire\Pages\Admin\Reports\Index as AdminReportsIndex;
use App\Livewire\Pages\Admin\Reports\Show as AdminReportShow;
use App\Livewire\Pages\Admin\Settings\Index as AdminSettingsIndex;
use App\Livewire\Pages\Admin\Users\EditAccount as AdminUsersEditAccount;
use App\Livewire\Pages\Admin\Users\Import as AdminUsersImport;
use App\Livewire\Pages\Admin\Users\Index as AdminUsersIndex;
use App\Livewire\Pages\Auth\Login as LoginPage;
use App\Livewire\Pages\Auth\PasswordChange as PasswordChangePage;
use App\Livewire\Pages\Dashboard\Index as DashboardIndex;
use App\Livewire\Pages\Dashboard\Redirect as DashboardRedirect;
use App\Livewire\Pages\Landing;
use App\Livewire\Pages\Notifications\Index as NotificationsIndex;
use App\Livewire\Pages\Profile\Show as ProfileShow;
use App\Livewire\Pages\Reports\AttachmentViewer;
use App\Livewire\Pages\Reports\CreateForm as StudentReportCreateForm;
use App\Livewire\Pages\Reports\CreateSelector as StudentReportCreateSelector;
use App\Livewire\Pages\Reports\Index as StudentReportsIndex;
use App\Livewire\Pages\Reports\Monitor as PublicReportsMonitor;
use App\Livewire\Pages\Reports\PublicShow as PublicReportShow;
use App\Livewire\Pages\Reports\Show as StudentReportShow;
use App\Livewire\Pages\Settings\Appearance as AppearanceSettings;
use Illuminate\Support\Facades\Route;

Route::livewire('/', Landing::class)->name('home');

Route::middleware('guest')->group(function (): void {
    Route::livewire('/login', LoginPage::class)->name('login');
});

Route::middleware(['auth', 'account.session'])->group(function (): void {
    Route::livewire('/password/change', PasswordChangePage::class)->name('password.change');
    Route::livewire('/profile', ProfileShow::class)->middleware('force.password.change')->name('profile.show');
    Route::livewire('/notifications', NotificationsIndex::class)->middleware('force.password.change')->name('notifications.index');
    Route::livewire('/settings/appearance', AppearanceSettings::class)->middleware('force.password.change')->name('settings.appearance');

    Route::middleware('force.password.change')->group(function (): void {
        Route::livewire('/attachments/{attachment}', AttachmentViewer::class)->whereNumber('attachment')
            ->middleware(PrivateFileResponseHeaders::class)
            ->name('attachments.show');

        Route::middleware('role:mahasiswa')->group(function (): void {
            Route::livewire('/reports', StudentReportsIndex::class)->name('reports.index');
            Route::livewire('/reports/create', StudentReportCreateSelector::class)->name('reports.create');
            Route::livewire('/reports/create/{type}', StudentReportCreateForm::class)
                ->where('type', 'fasilitas|aspirasi|pelanggaran')
                ->name('reports.create.form');
            Route::livewire('/reports/{report}', StudentReportShow::class)->whereNumber('report')->name('reports.show');
            Route::livewire('/monitor', PublicReportsMonitor::class)->name('monitor.index');
            Route::livewire('/monitor/{report}', PublicReportShow::class)->whereNumber('report')->name('monitor.show');
        });

        Route::livewire('/dashboard', DashboardRedirect::class)->name('dashboard');

        foreach ([
            'mahasiswa' => UserRole::MAHASISWA,
            'admin-sarpras' => UserRole::ADMIN_SARPRAS,
            'admin-kemahasiswaan' => UserRole::ADMIN_KEMAHASISWAAN,
            'super-admin' => UserRole::SUPER_ADMIN,
        ] as $path => $role) {
            Route::livewire('/dashboard/'.$path, DashboardIndex::class)
                ->middleware('role:'.$role->value)
                ->name('dashboard.'.$path);
        }
    });
});

Route::middleware([
    'auth',
    'account.session',
    'force.password.change',
    'role:admin_sarpras,admin_kemahasiswaan,super_admin',
])->prefix('admin')->name('admin.')->group(function (): void {
    Route::livewire('/reports', AdminReportsIndex::class)->name('reports.index');
    Route::livewire('/reports/{report}', AdminReportShow::class)->whereNumber('report')->name('reports.show');

    Route::livewire('/categories', AdminCategoriesIndex::class)->name('categories.index');

    Route::livewire('/locations', AdminLocationsIndex::class)->name('locations.index');
    Route::livewire('/logs', AdminActivityLogsIndex::class)->name('logs.index');
});

Route::middleware(['auth', 'account.session', 'force.password.change', 'role:super_admin'])
    ->prefix('admin')->name('admin.')
    ->group(function (): void {
        Route::livewire('/settings', AdminSettingsIndex::class)->name('settings.index');
        Route::livewire('/users', AdminUsersIndex::class)->name('users.index');
        Route::livewire('/users/{user}/edit', AdminUsersEditAccount::class)->name('users.edit');
        Route::livewire('/users/import', AdminUsersImport::class)->name('users.import');
        Route::livewire('/archive', AdminArchiveIndex::class)->name('archive.index');
    });
