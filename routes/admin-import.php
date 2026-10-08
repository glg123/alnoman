<?php

use App\Http\Controllers\AccountPasswordController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\CampProfileController;
use App\Http\Controllers\Admin\PeopleImportController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;

/*
| مسارات استيراد الأسر من Excel + مسارات الإعدادات العامة (بيانات المخيم).
| يُحمَّل هذا الملف من آخر routes/web.php بالسطر:
|     require __DIR__ . '/admin-import.php';
*/
Route::middleware(['auth', 'is-admin'])
    ->prefix('admin/import')
    ->name('admin.import.')
    ->group(function () {
        Route::get('/', [PeopleImportController::class, 'create'])->name('create');
        Route::get('/template', [PeopleImportController::class, 'template'])->name('template');
        Route::post('/', [PeopleImportController::class, 'upload'])->name('upload');

        Route::get('/{token}/mapping', [PeopleImportController::class, 'mapping'])->name('mapping');
        Route::post('/{token}/mapping', [PeopleImportController::class, 'saveMapping'])->name('mapping.save');
        Route::get('/{token}/preview', [PeopleImportController::class, 'preview'])->name('preview');
        Route::post('/{token}/run', [PeopleImportController::class, 'run'])->name('run');
        Route::get('/{token}/result', [PeopleImportController::class, 'result'])->name('result');
        Route::get('/{token}/download', [PeopleImportController::class, 'download'])->name('download');
        Route::delete('/{token}', [PeopleImportController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['auth', 'is-admin'])
    ->prefix('admin/camp-profile')
    ->name('admin.camp-profile.')
    ->group(function () {
        Route::get('/', [CampProfileController::class, 'edit'])->name('edit');
        Route::put('/', [CampProfileController::class, 'update'])->name('update');
    });

// عرض صور الهوية والمرفقات (محمية: الأدمن أو صاحب الأسرة فقط)
Route::middleware('auth')
    ->prefix('files')
    ->name('files.')
    ->group(function () {
        Route::get('/families/{family}/id-photo', [FileController::class, 'idPhoto'])->name('id-photo');
        Route::get('/special-cases/{specialCase}/document', [FileController::class, 'caseDocument'])->name('case-document');
        Route::get('/edit-requests/{editRequest}/photo', [FileController::class, 'editRequestPhoto'])->name('edit-request-photo');
        Route::get('/edit-requests/{editRequest}/cases/{index}', [FileController::class, 'editRequestCase'])
            ->whereNumber('index')->name('edit-request-case');
    });

// إدارة حسابات المستخدمين + سجل النشاط (للأدمن)
Route::middleware(['auth', 'is-admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::post('/users/{user}/toggle-active', [UserManagementController::class, 'toggleActive'])->name('users.toggle-active');
        Route::post('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.reset-password');
        Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
    });

// تغيير كلمة المرور (لكل مستخدم مسجّل)
Route::middleware('auth')
    ->prefix('account/password')
    ->name('account.password.')
    ->group(function () {
        Route::get('/', [AccountPasswordController::class, 'edit'])->name('edit');
        Route::put('/', [AccountPasswordController::class, 'update'])->name('update');
    });
