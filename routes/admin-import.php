<?php

use App\Http\Controllers\Admin\PeopleImportController;
use Illuminate\Support\Facades\Route;

/*
| مسارات استيراد الأسر من Excel.
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
