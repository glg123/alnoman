<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Family;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FamilyApprovalController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');

        $families = Family::with(['user', 'children', 'specialCases'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20);

        $stats = [
            'pending'  => Family::where('status', 'pending')->count(),
            'approved' => Family::where('status', 'approved')->count(),
            'rejected' => Family::where('status', 'rejected')->count(),
            'members'  => Family::where('status', 'approved')->sum('members_count'),
        ];

        return view('admin.families.index', compact('families', 'status', 'stats'));
    }

    public function show(Family $family)
    {
        $family->load(['user', 'children', 'specialCases', 'editRequests', 'benefitDistributions.organization']);

        return view('admin.families.show', compact('family'));
    }

    public function approve(Family $family)
    {
        $family->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        ActivityLog::record('family.approved', "اعتمد بيانات أسرة {$family->full_name}");

        return back()->with('success', 'تم اعتماد بيانات الأسرة.');
    }

    public function reject(Request $request, Family $family)
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $family->update([
            'status'            => 'rejected',
            'rejection_reason'  => $data['rejection_reason'],
            'approved_by'       => null,
            'approved_at'       => null,
            'reviewed_by'       => Auth::id(),
            'reviewed_at'       => now(),
        ]);

        ActivityLog::record('family.rejected', "رفض بيانات أسرة {$family->full_name}");

        return back()->with('success', 'تم رفض البيانات مع تسجيل السبب.');
    }
}
