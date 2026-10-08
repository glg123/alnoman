<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BenefitDistribution;
use App\Models\Family;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BenefitDistributionController extends Controller
{
    public function index()
    {
        $distributions = BenefitDistribution::with('organization')
            ->withCount('families')
            ->latest('distributed_at')
            ->paginate(20);

        return view('admin.benefits.index', compact('distributions'));
    }

    public function create()
    {
        $organizations = Organization::orderBy('name')->get();
        $families = Family::approved()->orderBy('full_name')->get(['id', 'full_name', 'national_id', 'members_count']);

        return view('admin.benefits.create', compact('organizations', 'families'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'type'            => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string', 'max:1000'],
            'distributed_at'  => ['required', 'date'],
            'family_ids'      => ['required', 'array', 'min:1'],
            'family_ids.*'    => ['exists:families,id'],
        ], [
            'family_ids.required' => 'اختر مستفيداً واحداً على الأقل.',
            'family_ids.min'      => 'اختر مستفيداً واحداً على الأقل.',
        ]);

        DB::transaction(function () use ($data) {
            $distribution = BenefitDistribution::create([
                'organization_id' => $data['organization_id'],
                'type'            => $data['type'],
                'description'     => $data['description'] ?? null,
                'distributed_at'  => $data['distributed_at'],
                'created_by'      => Auth::id(),
            ]);

            $distribution->families()->attach($data['family_ids']);
        });

        return redirect()->route('admin.benefits.index')
            ->with('success', 'تم تسجيل الاستفادة لعدد ' . count($data['family_ids']) . ' مستفيد.');
    }

    public function show(BenefitDistribution $benefit)
    {
        $benefit->load(['organization', 'families', 'creator']);

        return view('admin.benefits.show', compact('benefit'));
    }
}
