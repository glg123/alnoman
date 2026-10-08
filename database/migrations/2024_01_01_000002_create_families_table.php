<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // بيانات رب الأسرة
            $table->string('full_name');          // الاسم الرباعي
            $table->string('national_id')->unique();

            // بيانات الزوجة
            $table->string('wife_name')->nullable();
            $table->string('wife_national_id')->nullable();

            $table->unsignedInteger('members_count')->default(1);

            // حالة الاعتماد
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('families');
    }
};
