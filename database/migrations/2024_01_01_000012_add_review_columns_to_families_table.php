<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->foreignId('reviewed_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });

        // الأسر الحالية: من اعتمدها/رفضها مسجّل سابقاً بأعمدة approved_*
        DB::table('families')->whereNotNull('approved_by')->update([
            'reviewed_by' => DB::raw('approved_by'),
            'reviewed_at' => DB::raw('approved_at'),
        ]);

        // الأسر المرفوضة سابقاً كان رفضها مسجّلاً بأعمدة الاعتماد: نفصلها
        DB::table('families')->where('status', 'rejected')->update([
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    public function down(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn('reviewed_at');
        });
    }
};
