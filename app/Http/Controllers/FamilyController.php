<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\FamilyEditRequest;
use App\Support\CampFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FamilyController extends Controller
{
    /**
     * عرض بيانات المستخدم الحالي (لوحة تحكمه).
     */
    public function show()
    {
        $family = Auth::user()->family()->with(['children', 'specialCases', 'editRequests', 'benefitDistributions.organization'])->first();

        return view('family.show', compact('family'));
    }

    /**
     * فورم إدخال البيانات لأول مرة.
     */
    public function create()
    {
        if (Auth::user()->family()->exists()) {
            return redirect()->route('family.show');
        }

        return view('family.create');
    }

    protected function rules(?int $ignoreFamilyId = null): array
    {
        return [
            'full_name'                  => ['required', 'string', 'max:255'],
            'national_id'                => [
                'required', 'string', 'max:20',
                Rule::unique('families', 'national_id')->ignore($ignoreFamilyId),
            ],
            'national_id_photo'          => ['nullable', 'image', 'max:4096'], // 4MB
            'existing_national_id_photo' => ['nullable', 'string'],
            'wife_name'                  => ['nullable', 'string', 'max:255'],
            'wife_national_id'           => ['nullable', 'string', 'max:20'],
            'members_count'              => ['required', 'integer', 'min:1'],

            'children'                       => ['nullable', 'array'],
            'children.*.full_name'           => ['required_with:children', 'string', 'max:255'],
            'children.*.national_id'         => ['nullable', 'string', 'max:20'],
            'children.*.age'                 => ['required_with:children', 'integer', 'min:0', 'max:17'],

            'special_cases'                       => ['nullable', 'array'],
            'special_cases.*.type'                => ['required_with:special_cases', 'string', 'max:255'],
            'special_cases.*.description'         => ['nullable', 'string'],
            'special_cases.*.child_index'         => ['nullable', 'integer'], // فهرس الطفل داخل مصفوفة children إن وجد
            'special_cases.*.document'            => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // 5MB — سجل طبي / إثبات
            'special_cases.*.existing_document'   => ['nullable', 'string'],
        ];
    }

    /**
     * مسار صورة الهوية: الجديدة إن رُفعت، وإلا الموجودة بقاعدة البيانات.
     * (لا نثق بأي مسار قادم من المتصفح، وحذف القديمة يتم لاحقاً من الكنترولر.)
     */
    protected function resolveNationalIdPhoto(Request $request, ?Family $family): ?string
    {
        if ($request->hasFile('national_id_photo')) {
            return $request->file('national_id_photo')->store('id-photos', CampFiles::DISK);
        }

        return $family?->national_id_photo_path;
    }

    /**
     * يبني مصفوفة الحالات الخاصة مع تخزين الملف المرفق إن وُجد.
     * المرفق القديم يُقبل فقط إذا كان فعلاً تابعاً لحالة من حالات هذه الأسرة.
     */
    protected function resolveSpecialCases(Request $request, array $data, array $childModels, ?Family $family = null): array
    {
        $ownedDocuments = $family ? $family->specialCases->pluck('document_path')->filter()->all() : [];
        $result = [];

        foreach ($data['special_cases'] ?? [] as $index => $caseData) {
            $documentPath = $caseData['existing_document'] ?? null;
            if (! in_array($documentPath, $ownedDocuments, true)) {
                $documentPath = null;
            }

            if ($request->hasFile("special_cases.$index.document")) {
                $documentPath = $request->file("special_cases.$index.document")->store('special-case-documents', CampFiles::DISK);
            }

            $childIndex = $caseData['child_index'] ?? null;

            $result[] = [
                'child_id'      => $childIndex !== null && $childIndex !== '' ? ($childModels[$childIndex]->id ?? null) : null,
                'child_index'   => $childIndex,
                'type'          => $caseData['type'],
                'description'   => $caseData['description'] ?? null,
                'document_path' => $documentPath,
            ];
        }

        return $result;
    }

    /**
     * حفظ البيانات لأول مرة (تبقى pending حتى موافقة الأدمن).
     */
    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $nationalIdPhotoPath = $this->resolveNationalIdPhoto($request, null);

        $family = DB::transaction(function () use ($data, $request, $nationalIdPhotoPath) {
            $family = Family::create([
                'user_id'                => Auth::id(),
                'full_name'              => $data['full_name'],
                'national_id'            => $data['national_id'],
                'national_id_photo_path' => $nationalIdPhotoPath,
                'wife_name'              => $data['wife_name'] ?? null,
                'wife_national_id'       => $data['wife_national_id'] ?? null,
                'members_count'          => $data['members_count'],
                'status'                 => 'pending',
            ]);

            $childModels = [];
            foreach ($data['children'] ?? [] as $index => $childData) {
                $childModels[$index] = $family->children()->create($childData);
            }

            foreach ($this->resolveSpecialCases($request, $data, $childModels) as $caseData) {
                unset($caseData['child_index']);
                $family->specialCases()->create($caseData);
            }

            return $family;
        });

        return redirect()->route('family.show')
            ->with('success', 'تم إرسال بياناتك بنجاح، وهي الآن بانتظار موافقة الإدارة.');
    }

    /**
     * فورم تعديل البيانات.
     * - إذا كانت الأسرة لسه pending: تعديل مباشر.
     * - إذا كانت معتمدة (approved): ينشئ طلب تعديل (FamilyEditRequest) بانتظار الموافقة.
     */
    public function edit()
    {
        $family = Auth::user()->family()->with(['children', 'specialCases'])->firstOrFail();

        return view('family.edit', compact('family'));
    }

    public function update(Request $request)
    {
        $family = Auth::user()->family()->with(['children', 'specialCases'])->firstOrFail();
        $data = $request->validate($this->rules($family->id));

        $oldFiles = CampFiles::forFamily($family);
        $nationalIdPhotoPath = $this->resolveNationalIdPhoto($request, $family);

        // pending أو rejected: تعديل مباشر، والأسرة المرفوضة ترجع للمراجعة (pending)
        if (in_array($family->status, ['pending', 'rejected'], true)) {
            $newCases = [];

            try {
                DB::transaction(function () use ($family, $data, $request, $nationalIdPhotoPath, &$newCases) {
                    $family->update([
                        'full_name'               => $data['full_name'],
                        'national_id'             => $data['national_id'],
                        'national_id_photo_path'  => $nationalIdPhotoPath,
                        'wife_name'               => $data['wife_name'] ?? null,
                        'wife_national_id'        => $data['wife_national_id'] ?? null,
                        'members_count'           => $data['members_count'],
                        'status'                  => 'pending',
                        'rejection_reason'        => null,
                        'reviewed_by'             => null,
                        'reviewed_at'             => null,
                    ]);

                    $childModels = [];
                    $family->children()->delete();
                    foreach ($data['children'] ?? [] as $index => $childData) {
                        $childModels[$index] = $family->children()->create($childData);
                    }

                    $newCases = $this->resolveSpecialCases($request, $data, $childModels, $family);
                    $family->specialCases()->delete();
                    foreach ($newCases as $caseData) {
                        unset($caseData['child_index']);
                        $family->specialCases()->create($caseData);
                    }
                });
            } catch (\Throwable $e) {
                // فشل الحفظ: نحذف الملفات الجديدة اللي انرفعت للتو
                CampFiles::deleteExcept(
                    array_merge([$nationalIdPhotoPath], array_column($newCases, 'document_path')),
                    $oldFiles
                );
                throw $e;
            }

            // الملفات القديمة اللي انستبدلت أو انحذفت
            CampFiles::deleteExcept($oldFiles, array_merge([$nationalIdPhotoPath], array_column($newCases, 'document_path')));

            return redirect()->route('family.show')->with('success', 'تم تحديث البيانات.');
        }

        // الأسرة معتمدة: طلب تعديل بانتظار موافقة الأدمن.
        // الملفات القديمة المعتمدة ما بتنحذف هون، بتنحذف عند اعتماد الطلب.
        $tempChildModels = [];
        foreach (($data['children'] ?? []) as $index => $childData) {
            $tempChildModels[$index] = (object) ['id' => null];
        }

        $payload = $data;
        unset($payload['national_id_photo']);
        unset($payload['existing_national_id_photo']);
        $payload['national_id_photo_path'] = $nationalIdPhotoPath;
        $payload['special_cases'] = array_map(function ($case) {
            unset($case['child_id']);

            return $case;
        }, $this->resolveSpecialCases($request, $data, $tempChildModels, $family));

        $previousPending = FamilyEditRequest::where('family_id', $family->id)->where('status', 'pending')->get();

        FamilyEditRequest::create([
            'family_id' => $family->id,
            'user_id'   => Auth::id(),
            'payload'   => $payload,
            'status'    => 'pending',
        ]);

        // طلب جديد يحلّ مكان الطلب المعلّق السابق، ونحذف ملفاته الجديدة اللي ما عاد إلها لزوم
        foreach ($previousPending as $old) {
            CampFiles::deleteExcept(
                CampFiles::forPayload($old->payload),
                array_merge($oldFiles, CampFiles::forPayload($payload))
            );
            $old->delete();
        }

        return redirect()->route('family.show')
            ->with('success', 'تم إرسال طلب التعديل، بانتظار موافقة الإدارة.');
    }
}
