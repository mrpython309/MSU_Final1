<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Branch; 
use App\Http\Requests\Branches\BranchRequest;
use App\Services\MasterListExportService;
use App\Support\MasterListing;
use Illuminate\Database\Eloquent\Builder;

class BranchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = MasterListing::perPage($request);
        $branches = $this->filteredBranches($request)->paginate($perPage)->withQueryString();

        $companies = Company::active()->get();

        $cities = Branch::select('city')
        ->whereNotNull('city')
    ->where('city', '!=', '')
            ->distinct()
               ->orderBy('city')
            ->pluck('city');
        
        return view('branch.index', compact('branches', 'companies','cities', 'perPage'));
    }

    public function export(Request $request, MasterListExportService $exporter)
    {
        return $exporter->excel(
            $this->branchRows($request),
            $this->branchExportHeadings(),
            'branches'
        );
    }

    public function exportPdf(Request $request, MasterListExportService $exporter)
    {
        return $exporter->pdf(
            'Branches',
            $this->branchExportHeadings(),
            $this->branchRows($request),
            'branches'
        );
    }

    private function branchExportHeadings(): array
    {
        return [
            'S.No',
            'Branch Name',
            'Branch Code',
            'Company',
            'Address',
            'City',
            'State',
            'Country',
            'Contact Person',
            'Phone',
            'Email',
            'GST',
            'Status',
        ];
    }

    private function filteredBranches(Request $request): Builder
    {
        $query = Branch::with('company')
            ->when($request->name, fn ($q) => $q->where('name', 'like', '%'.$request->name.'%'))
            ->when($request->code, fn ($q) => $q->where('code', $request->code))
            ->when($request->city, fn ($q) => $q->where('city', 'like', '%'.$request->city.'%'))
            ->when($request->company_id, fn ($q) => $q->where('company_id', $request->company_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status));

        MasterListing::applySearch($query, $request, ['name', 'code', 'city', 'contact_person', 'contact_number', 'email', 'company.name']);

        return $query;
    }

    private function branchRows(Request $request)
    {
        return $this->filteredBranches($request)->get()->values()->map(
            fn (Branch $branch, int $index) => [
                $index + 1,
                $branch->name,
                $branch->code,
                $branch->company?->name,
                $branch->address,
                $branch->city,
                $branch->state,
                $branch->country,
                $branch->contact_person,
                $branch->contact_number,
                $branch->email,
                $branch->gst_number,
                ucfirst((string) $branch->status),
            ]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
         $companies = Company::active()->get();
      return view('branch.create', compact('companies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BranchRequest $request)
    {
        $validated = $request->validated();

        $branch=Branch::create($validated);

       // $user=auth()->user();

        // $user->branches()->syncWithoutDetaching([$branch->id]);

        return redirect()->route('branch.index')
            ->with('success', 'Branch created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Branch $branch)
    {
        $branch->loadMissing('company');

        return view('branch.show', compact('branch'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Branch $branch)
    {
        $companies = Company::active()->get();
        return view('branch.edit', compact('branch', 'companies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BranchRequest $request, Branch $branch)
    {
        $validated = $request->validated();

        $branch->update($validated);

        return redirect()->route('branch.index')
            ->with('success', 'Branch updated successfully.');
    }

    /**
     * Minimal JSON create for asset form "+" (requires company_id from the asset form).
     */
    public function quickStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_id' => 'required|exists:companies,id',
        ]);

        $name = trim($validated['name']);
        $companyId = (int) $validated['company_id'];

        $existing = Branch::query()
            ->where('company_id', $companyId)
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'data' => $existing->only(['id', 'name']),
                'message' => 'Branch already exists for this company.',
            ]);
        }

        $base = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', substr($name, 0, 4)));
        if ($base === '') {
            $base = 'BR';
        }

        do {
            $code = $base . '-' . strtoupper(substr(uniqid('', true), -6));
        } while (Branch::query()->where('code', $code)->exists());

        $branch = Branch::query()->create([
            'name' => $name,
            'code' => $code,
            'company_id' => $companyId,
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'data' => $branch->only(['id', 'name']),
            'message' => 'Branch created successfully.',
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Branch $branch)
    {
        if ($branch->hasDependencies()) {
            return back()->with('error', 'cannot delete branch because it has mapped with other records');
        }

        $branch->delete();

        return redirect()->route('branch.index')
            ->with('success', 'Branch deleted successfully.');
    }
}
