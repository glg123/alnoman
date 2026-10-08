<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * ينقل صور الهوية والمرفقات الموجودة من القرص العام (public) إلى القرص الخاص (local)
 * فلا يعود أي ملف قابلاً للفتح بالرابط المباشر.
 */
return new class extends Migration
{
    public function up(): void
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');

        $move = function ($path) use ($public, $private) {
            if (! is_string($path) || $path === '' || ! $public->exists($path)) {
                return;
            }

            $stream = $public->readStream($path);
            $private->put($path, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
            $public->delete($path);
        };

        DB::table('families')->whereNotNull('national_id_photo_path')->pluck('national_id_photo_path')->each($move);
        DB::table('special_cases')->whereNotNull('document_path')->pluck('document_path')->each($move);

        foreach (DB::table('family_edit_requests')->pluck('payload') as $json) {
            $payload = json_decode($json, true) ?: [];
            $move($payload['national_id_photo_path'] ?? null);
            foreach ($payload['special_cases'] ?? [] as $case) {
                $move($case['document_path'] ?? null);
            }
        }
    }

    public function down(): void
    {
        // لا رجوع: الملفات تبقى على القرص الخاص.
    }
};
