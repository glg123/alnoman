<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('camp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('type')->default('text'); // text | textarea | number | url | date | image
            $table->text('value')->nullable();
            $table->boolean('is_system')->default(false); // الحقول الأساسية (اسم/شعار/عنوان/عدد الأسر) لا تُحذف
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camp_settings');
    }
};
