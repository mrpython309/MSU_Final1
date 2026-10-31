<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveEmployeeRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Division;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class EmployeesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employees = User::with(['division', 'companies', 'roles.permissions'])
            ->where('id', '!=', 1)
            ->where('is_user', 'yes')
            ->orderByDesc('id')
            ->paginate(50);

        return view('employee.index', compact('employees'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $divisions = Division::where('status', 'active')->orderBy('division_name')->get();
        $roles = Role::where('id', '!=', 1)->get();
        $companies = Company::where('status', 'active')->get();
        $departments = Department::where('status', 'active')->get();

        $oldCompanyIds = array_filter((array) old('company_id', []));
        $branches = $oldCompanyIds !== []
            ? Branch::query()
                ->whereIn('company_id', $oldCompanyIds)
                ->where('status', 'active')
                ->with('company:id,name')
                ->orderBy('name')
                ->get()
            : collect();

        return view('employee.create', compact(
            'divisions',
            'roles',
            'companies',
            'departments',
            'branches'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SaveEmployeeRequest $request)
    {
        $data = $request->validated();

        $roleId = $data['role_id'] ?? null;
        unset($data['role_id']);

        $companiesId = $data['company_id'] ?? [];
        unset($data['company_id']);

        $branchIds = $data['branch_id'] ?? [];
        unset($data['branch_id']);

        $departmentIds = $data['department_id'] ?? [];
        unset($data['department_id']);

        $data['is_department_head'] = $request->boolean('is_department_head') ? 1 : 0;

        // Keep a primary company/branch for legacy columns when present.
        $data['company_id'] = $companiesId[0] ?? null;
        $data['branch_id'] = $branchIds[0] ?? null;

        $user = User::create($data);

        $user->branches()->sync($branchIds);
        $user->departments()->sync($departmentIds);
        $user->companies()->sync($companiesId);

        if ($roleId) {
            $role = Role::findById($roleId);
            $user->syncRoles([$role->name]);
        }

        return redirect()->route('employees.index')->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $employee)
    {
        $employee->load(['companies', 'branches', 'departments', 'roles']);

        $divisions = Division::query()
            ->where(function ($query) use ($employee) {
                $query->where('status', 'active');
                if ($employee->division_id) {
                    $query->orWhere('id', $employee->division_id);
                }
            })
            ->orderBy('division_name')
            ->get();
        $roles = Role::where('id', '!=', 1)->get();
        $companies = Company::where('status', 'active')->get();
        $companyIds = $employee->companies->pluck('id')->toArray();
        $branches = Branch::query()
            ->whereIn('company_id', $companyIds ?: [0])
            ->where('status', 'active')
            ->with('company:id,name')
            ->orderBy('name')
            ->get();
        $departments = Department::where('status', 'active')->get();

        return view('employee.edit', compact(
            'divisions',
            'roles',
            'companies',
            'employee',
            'branches',
            'departments'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SaveEmployeeRequest $request, User $employee)
    {
        $data = $request->validated();

        $roleId = $data['role_id'] ?? null;
        unset($data['role_id']);

        $companiesId = $data['company_id'] ?? [];
        unset($data['company_id']);

        $branchIds = $data['branch_id'] ?? [];
        unset($data['branch_id']);

        $departmentIds = $data['department_id'] ?? [];
        unset($data['department_id']);

        if (empty($data['password'])) {
            unset($data['password']);
        }
        // Do not Hash::make here — User model casts password as 'hashed'.

        $data['is_department_head'] = $request->boolean('is_department_head') ? 1 : 0;
        $data['company_id'] = $companiesId[0] ?? null;
        $data['branch_id'] = $branchIds[0] ?? null;

        $employee->update($data);

        $employee->branches()->sync($branchIds);
        $employee->departments()->sync($departmentIds);
        $employee->companies()->sync($companiesId);

        if ($roleId) {
            $role = Role::findById($roleId);
            $employee->syncRoles([$role->name]);
        }

        return redirect()->route('employees.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $employee)
    {
        if ((int) $employee->id === 1) {
            return redirect()->route('employees.index')->with('error', 'The primary admin user cannot be deleted.');
        }

        if ((int) $employee->id === (int) Auth::id()) {
            return redirect()->route('employees.index')->with('error', 'You cannot delete your own account.');
        }

        try {
            $employee->branches()->detach();
            $employee->departments()->detach();
            $employee->companies()->detach();
            $employee->roles()->detach();
            $employee->delete();
        } catch (QueryException $e) {
            return redirect()->route('employees.index')->with(
                'error',
                'This user cannot be deleted because they are linked to other records (approvals, GRNs, etc.). Reassign or remove those links first.'
            );
        }

        return redirect()->route('employees.index')->with('success', 'User deleted successfully.');
    }
}
