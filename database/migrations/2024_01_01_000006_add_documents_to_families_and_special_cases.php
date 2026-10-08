<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->string('national_id_photo_path')->nullable()->after('national_id');
        });

        Schema::table('special_cases', function (Blueprint $table) {
            $table->string('document_path')->nullable()->after('description'); // سجل طبي / ما يثبت الحالة
        });
    }

    public function down(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->dropColumn('national_id_photo_path');
        });

        Schema::table('special_cases', function (Blueprint $table) {
            $table->dropColumn('document_path');
        });
    }
};
