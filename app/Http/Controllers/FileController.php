<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\FamilyEditRequest;
use App\Models\SpecialCase;
use App\Support\CampFiles;
use Illuminate\Support\Facades\Auth;

/**
 * يعرض صور الهوية والمرفقات من القرص الخاص:
 * الأدمن يشوف الكل، والمستخدم يشوف ملفات أسرته فقط.
 */
class FileController extends Controller
{
    public function idPhoto(Family $family)
    {
        $this->authorizeFamily($family);

        return $this->send($family->national_id_photo_path);
    }

    public function caseDocument(SpecialCase $specialCase)
    {
        $this->authorizeFamily($specialCase->family);

        return $this->send($specialCase->document_path);
    }

    public function editRequestPhoto(FamilyEditRequest $editRequest)
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        return $this->send($editRequest->payload['national_id_photo_path'] ?? null);
    }

    public function editRequestCase(FamilyEditRequest $editRequest, int $index)
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        return $this->send($editRequest->payload['special_cases'][$index]['document_path'] ?? null);
    }

    private function authorizeFamily(Family $family): void
    {
        $user = Auth::user();

        abort_unless($user->isAdmin() || $family->user_id === $user->id, 403);
    }

    private function send(?string $path)
    {
        abort_unless($path && CampFiles::disk()->exists($path), 404);

        return CampFiles::disk()->response($path, null, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }
}
