<?php

namespace App\Http\Controllers\Division;

use App\Http\Controllers\Controller;
use App\Http\Requests\Division\DivisionRequest;
use App\Models\Division;
use App\Services\MasterListExportService;
use App\Support\MasterListing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DivisionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = MasterListing::perPage($request);
        $divisions = $this->filteredDivisions($request)->orderByDesc('id')->paginate($perPage)->withQueryString();

        return view('division.index', compact('divisions', 'perPage'));
    }

    public function export(Request $request, MasterListExportService $exporter)
    {
        return $exporter->excel($this->divisionRows($request), ['S.No', 'Division', 'Status'], 'divisions');
    }

    public function exportPdf(Request $request, MasterListExportService $exporter)
    {
        return $exporter->pdf('Divisions', ['S.No', 'Division', 'Status'], $this->divisionRows($request), 'divisions');
    }

    private function filteredDivisions(Request $request): Builder
    {
        $query = Division::query();
        MasterListing::applySearch($query, $request, ['division_name']);
        MasterListing::applyStatus($query, $request);

        return $query;
    }

    private function divisionRows(Request $request)
    {
        return $this->filteredDivisions($request)->orderByDesc('id')->get()->values()->map(
            fn (Division $division, int $index) => [$index + 1, $division->division_name, ucfirst((string) $division->status)]
        );
    }

    public function create()
    {
        return view('division.create');
    }

    public function store(DivisionRequest $request)
    {
        Division::create($request->validated());

        return redirect()
            ->route('division.index')
            ->with('success', 'Division created successfully.');
    }

    public function edit(Division $division)
    {
        return view('division.edit', compact('division'));
    }

    public function update(DivisionRequest $request, Division $division)
    {
        $division->update($request->validated());

        return redirect()
            ->route('division.index')
            ->with('success', 'Division updated successfully.');
    }

    public function destroy(Division $division)
    {
        $division->delete();

        return redirect()
            ->route('division.index')
            ->with('success', 'Division deleted successfully.');
    }

    public function updateStatus(Request $request)
    {
        $division = Division::findOrFail($request->id);
        $division->status = $request->status;
        $division->save();

        return response()->json(['success' => true]);
    }
}
