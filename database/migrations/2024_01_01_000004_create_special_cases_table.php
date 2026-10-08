<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('special_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            // اختياري: الحالة قد تخص طفل معين بدل الأسرة كاملة
            $table->foreignId('child_id')->nullable()->constrained('children')->cascadeOnDelete();

            $table->string('type');            // نوع الحالة: إعاقة حركية، سمعية، بصرية، مرض مزمن...الخ
            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_cases');
    }
};
