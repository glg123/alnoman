<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Family;
use App\Models\FamilyEditRequest;
use App\Support\CampFiles;
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
        $error = null;
        $oldFiles = [];
        $newFiles = [];

        DB::transaction(function () use ($editRequest, &$error, &$oldFiles, &$newFiles) {
            // قفل الصف: ضغطتين على الزر ما بنفّذوا مرتين
            $request = FamilyEditRequest::whereKey($editRequest->id)->lockForUpdate()->first();

            if (! $request || $request->status !== 'pending') {
                $error = 'هذا الطلب تمت معالجته مسبقاً.';

                return;
            }

            $family = $request->family;
            $data = $request->payload;

            $taken = Family::where('national_id', $data['national_id'])->where('id', '!=', $family->id)->exists();
            if ($taken) {
                $error = 'رقم الهوية المقترح مسجّل لأسرة ثانية. ارفض الطلب مع ذكر السبب.';

                return;
            }

            $oldFiles = CampFiles::forFamily($family);
            $newFiles = CampFiles::forPayload($data);

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

            $request->update([
                'status'      => 'approved',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);
        });

        if ($error) {
            return back()->with('error', $error);
        }

        // الملفات القديمة اللي استُبدلت بعد الاعتماد
        CampFiles::deleteExcept($oldFiles, $newFiles);

        ActivityLog::record('edit.approved', "اعتمد طلب تعديل أسرة {$editRequest->family->full_name}");

        return back()->with('success', 'تم اعتماد التعديل ودمجه في بيانات الأسرة.');
    }

    public function reject(Request $request, FamilyEditRequest $editRequest)
    {
        $data = $request->validate([
            'admin_note' => ['required', 'string', 'max:500'],
        ]);

        if ($editRequest->status !== 'pending') {
            return back()->with('error', 'هذا الطلب تمت معالجته مسبقاً.');
        }

        $editRequest->update([
            'status'      => 'rejected',
            'admin_note'  => $data['admin_note'],
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        // الملفات الجديدة اللي رفعها المستخدم مع الطلب ما عاد إلها لزوم (باستثناء المستخدمة فعلياً بالأسرة)
        CampFiles::deleteExcept(
            CampFiles::forPayload($editRequest->payload),
            CampFiles::forFamily($editRequest->family)
        );

        ActivityLog::record('edit.rejected', "رفض طلب تعديل أسرة {$editRequest->family->full_name}");

        return back()->with('success', 'تم رفض طلب التعديل.');
    }
}
