<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();

            $table->string('full_name');                 // اسم الطفل
            $table->string('national_id')->nullable()->unique(); // رقم هوية الطفل (قد لا تتوفر لحديثي الولادة)
            $table->unsignedTinyInteger('age');           // عمر الطفل

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('children');
    }
};
