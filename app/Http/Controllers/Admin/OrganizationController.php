<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrganizationController extends Controller
{
    public function index()
    {
        $organizations = Organization::withCount('distributions')->latest()->paginate(20);

        return view('admin.organizations.index', compact('organizations'));
    }

    public function create()
    {
        return view('admin.organizations.create');
    }

    protected function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'logo'        => ['nullable', 'image', 'max:2048'],
            'phone'       => ['nullable', 'string', 'max:50'],
            'address'     => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        $logoPath = $request->hasFile('logo')
            ? $request->file('logo')->store('organization-logos', 'public')
            : null;

        Organization::create([
            'name'        => $data['name'],
            'logo_path'   => $logoPath,
            'phone'       => $data['phone'] ?? null,
            'address'     => $data['address'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        return redirect()->route('admin.organizations.index')->with('success', 'تمت إضافة المؤسسة بنجاح.');
    }

    public function edit(Organization $organization)
    {
        return view('admin.organizations.edit', compact('organization'));
    }

    public function update(Request $request, Organization $organization)
    {
        $data = $request->validate($this->rules());

        $logoPath = $organization->logo_path;
        if ($request->hasFile('logo')) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }
            $logoPath = $request->file('logo')->store('organization-logos', 'public');
        }

        $organization->update([
            'name'        => $data['name'],
            'logo_path'   => $logoPath,
            'phone'       => $data['phone'] ?? null,
            'address'     => $data['address'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        return redirect()->route('admin.organizations.index')->with('success', 'تم تحديث بيانات المؤسسة.');
    }

    public function destroy(Organization $organization)
    {
        if ($organization->distributions()->exists()) {
            return back()->with('error', 'لا يمكن حذف مؤسسة لها سجل استفادات — يمكنك تعديل بياناتها بدلاً من ذلك.');
        }

        if ($organization->logo_path) {
            Storage::disk('public')->delete($organization->logo_path);
        }

        $organization->delete();

        return redirect()->route('admin.organizations.index')->with('success', 'تم حذف المؤسسة.');
    }
}
