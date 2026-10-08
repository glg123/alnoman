<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FamilyEditRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EditRequestController extends Controller
{
    public function index()
    {
        $editRequests = FamilyEditRequest::with(['family', 'user'])
            ->where('status', 'pending')
            ->latest()
            ->paginate(20);

        return view('admin.edit-requests.index', compact('editRequests'));
    }

    public function show(FamilyEditRequest $editRequest)
    {
        $editRequest->load(['family.children', 'family.specialCases', 'user']);

        return view('admin.edit-requests.show', compact('editRequest'));
    }

    /**
     * قبول التعديل: يدمج الـ payload داخل الجدول الأساسي (families/children/special_cases).
     */
    public function approve(FamilyEditRequest $editRequest)
    {
        DB::transaction(function () use ($editRequest) {
            $family = $editRequest->family;
            $data = $editRequest->payload;

            $family->update([
                'full_name'               => $data['full_name'],
                'national_id'             => $data['national_id'],
                'national_id_photo_path'  => $data['national_id_photo_path'] ?? $family->national_id_photo_path,
                'wife_name'               => $data['wife_name'] ?? null,
                'wife_national_id'        => $data['wife_national_id'] ?? null,
                'members_count'           => $data['members_count'],
            ]);

            $family->children()->delete();
            $family->specialCases()->delete();

            $childModels = [];
            foreach ($data['children'] ?? [] as $index => $childData) {
                $childModels[$index] = $family->children()->create($childData);
            }

            foreach ($data['special_cases'] ?? [] as $caseData) {
                $childIndex = $caseData['child_index'] ?? null;
                $family->specialCases()->create([
                    'child_id'      => $childIndex !== null && $childIndex !== '' ? ($childModels[$childIndex]->id ?? null) : null,
                    'type'          => $caseData['type'],
                    'description'   => $caseData['description'] ?? null,
                    'document_path' => $caseData['document_path'] ?? null,
                ]);
            }

            $editRequest->update([
                'status'      => 'approved',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);
        });

        return back()->with('success', 'تم اعتماد التعديل ودمجه في بيانات الأسرة.');
    }

    public function reject(Request $request, FamilyEditRequest $editRequest)
    {
        $data = $request->validate([
            'admin_note' => ['required', 'string', 'max:500'],
        ]);

        $editRequest->update([
            'status'      => 'rejected',
            'admin_note'  => $data['admin_note'],
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'تم رفض طلب التعديل.');
    }
}
