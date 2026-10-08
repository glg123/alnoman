<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('camp_profile', function (Blueprint $table) {
            $table->id();

            // بيانات المخيم
            $table->string('name');
            $table->string('logo_path')->nullable();
            $table->text('description')->nullable();
            $table->date('established_at')->nullable();

            // الأعداد
            $table->unsignedInteger('displaced_count')->nullable();
            $table->unsignedInteger('families_count')->nullable();

            // العنوان
            $table->string('governorate')->nullable();
            $table->string('area')->nullable();
            $table->string('address', 500)->nullable();
            $table->string('map_url', 500)->nullable();

            // التواصل مع المخيم
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();

            // مندوب المخيم
            $table->string('rep_name')->nullable();
            $table->string('rep_phone', 30)->nullable();
            $table->string('rep_phone_alt', 30)->nullable();
            $table->string('rep_email')->nullable();
            $table->string('rep_national_id', 20)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camp_profile');
    }
};
