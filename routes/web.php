<?php

use App\Http\Controllers\Admin\EditRequestController;
use App\Http\Controllers\Admin\BenefitDistributionController;
use App\Http\Controllers\Admin\FamilyApprovalController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\FamilyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ملاحظة: هذا مجرد مرجع تدمجه داخل routes/web.php الموجود عندك
| بعد إعداد auth (يفضّل استخدام middleware مخصص للأدمن، مثال: 'is-admin')
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => redirect()->route('login'));

Route::middleware(['guest'])->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth'])->group(function () {

    // ============ جانب المستخدم العادي ============
    Route::get('/family/create', [FamilyController::class, 'create'])->name('family.create');
    Route::post('/family', [FamilyController::class, 'store'])->name('family.store');
    Route::get('/family', [FamilyController::class, 'show'])->name('family.show');
    Route::get('/family/edit', [FamilyController::class, 'edit'])->name('family.edit');
    Route::put('/family', [FamilyController::class, 'update'])->name('family.update');

    // ============ جانب الأدمن ============
    Route::middleware(['is-admin'])->prefix('admin')->name('admin.')->group(function () {

        // إدارة المستخدمين
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');

        // اعتماد بيانات الأسر
        Route::get('/families', [FamilyApprovalController::class, 'index'])->name('families.index');
        Route::get('/families/{family}', [FamilyApprovalController::class, 'show'])->name('families.show');
        Route::post('/families/{family}/approve', [FamilyApprovalController::class, 'approve'])->name('families.approve');
        Route::post('/families/{family}/reject', [FamilyApprovalController::class, 'reject'])->name('families.reject');

        // طلبات التعديل
        Route::get('/edit-requests', [EditRequestController::class, 'index'])->name('edit-requests.index');
        Route::get('/edit-requests/{editRequest}', [EditRequestController::class, 'show'])->name('edit-requests.show');
        Route::post('/edit-requests/{editRequest}/approve', [EditRequestController::class, 'approve'])->name('edit-requests.approve');
        Route::post('/edit-requests/{editRequest}/reject', [EditRequestController::class, 'reject'])->name('edit-requests.reject');

        // المؤسسات والجمعيات
        Route::resource('organizations', OrganizationController::class)->except(['show']);

        // سجل الاستفادات
        Route::get('/benefits', [BenefitDistributionController::class, 'index'])->name('benefits.index');
        Route::get('/benefits/create', [BenefitDistributionController::class, 'create'])->name('benefits.create');
        Route::post('/benefits', [BenefitDistributionController::class, 'store'])->name('benefits.store');
        Route::get('/benefits/{benefit}', [BenefitDistributionController::class, 'show'])->name('benefits.show');
    });
});
