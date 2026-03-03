<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\PasswordChangeRequest;
use App\Rules\EmployeePassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class EmployeeController extends Controller
{
    public function index()
    {
        $query = Employee::with('user')->orderBy('last_name');

        if ($search = request('search')) {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('first_name','like',$like)
                  ->orWhere('last_name','like',$like)
                  ->orWhere('contact_number','like',$like)
                  ->orWhere('sss_number','like',$like)
                  ->orWhereHas('user', fn($uq) =>
                        $uq->where('name','like',$like)
                           ->orWhere('email','like',$like)
                  );
            });
        }

        $employees = $query->paginate(10);

        // KPI Stats
        $stats = $this->getEmployeeStats();

        // Get pending password change requests (admin only)
        $pendingPasswordRequests = [];
        if (auth()->user()->role === 'admin') {
            $pendingPasswordRequests = PasswordChangeRequest::with(['user', 'employee'])
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('employees.index', compact('employees', 'stats', 'pendingPasswordRequests'));
    }

    /**
     * Compute employee KPI statistics
     */
    protected function getEmployeeStats(): array
    {
        $totalEmployees = Employee::count();
        
        $activeEmployees = Employee::whereHas('user', function ($q) {
            $q->where('is_active', true);
        })->count();
        
        $inactiveEmployees = Employee::whereHas('user', function ($q) {
            $q->where('is_active', false);
        })->count();
        
        // Employees registered in the last 30 days
        $recentlyRegistered = Employee::where('created_at', '>=', Carbon::now()->subDays(30))->count();
        
        // Employees by role (admin vs employee accounts)
        $employeesByRole = [
            'employees' => User::where('role', 'employee')->count(),
            'admins' => User::where('role', 'admin')->count(),
        ];

        return [
            'total' => $totalEmployees,
            'active' => $activeEmployees,
            'inactive' => $inactiveEmployees,
            'recent' => $recentlyRegistered,
            'by_role' => $employeesByRole,
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => ['required','string','max:100'],
            'email'           => ['required','email','max:150','unique:users,email'],
            'password'        => ['required','confirmed', new EmployeePassword],
            'role'            => ['required','string','in:employee,security'],
            'first_name'      => ['required','string','max:80'],
            'last_name'       => ['required','string','max:80'],
            'address'         => ['required','string','max:255'],
            'contact_number'  => ['required','string','max:40'],
            'sss_number'      => ['required','string','max:40','unique:employees,sss_number'],
            'profile_picture' => ['nullable','image','mimes:jpg,jpeg,png','max:1024'],
        ]);

        DB::transaction(function () use ($request, $data) {

            // password cast hashes automatically
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => $data['password'],
                'role'     => $data['role'],
                'is_active' => true,
            ]);

            $profilePath = null;
            if ($request->hasFile('profile_picture')) {
                $profilePath = $this->storeProfilePicture($request->file('profile_picture'), $data['first_name'], $data['last_name']);
            }

            $employee = Employee::create([
                'user_id'        => $user->id,
                'first_name'     => $data['first_name'],
                'last_name'      => $data['last_name'],
                'address'        => $data['address'],
                'contact_number' => $data['contact_number'],
                'sss_number'     => $data['sss_number'],
                'profile_picture'=> $profilePath,
            ]);

            ActivityLog::record(
                'employee.created',
                $employee,
                'Employee created: '.$employee->first_name.' '.$employee->last_name,
                ['employee_id' => $employee->id, 'user_id' => $user->id]
            );
        });

        // Return JSON for AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Employee registered successfully.'
            ]);
        }

        return redirect()->route('employees.index')->with('success','Employee registered successfully.');
    }

    /**
     * Store profile picture with sanitized filename
     */
    protected function storeProfilePicture($file, string $firstName, string $lastName): string
    {
        // Sanitize and create unique filename
        $safeName = Str::slug($firstName . '-' . $lastName);
        $extension = $file->getClientOriginalExtension();
        $filename = $safeName . '-' . time() . '-' . Str::random(8) . '.' . $extension;
        
        return $file->storeAs('employee_profiles', $filename, 'public');
    }

    public function edit(Employee $employee)
    {
        $employee->load('user');
        return view('employees.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        $employee->load('user');

        $data = $request->validate([
            'name'            => ['required','string','max:100'],
            'email'           => ['required','email','max:150', Rule::unique('users','email')->ignore($employee->user_id)],
            'password'        => ['nullable','confirmed', new EmployeePassword],
            'first_name'      => ['required','string','max:80'],
            'last_name'       => ['required','string','max:80'],
            'address'         => ['required','string','max:255'],
            'contact_number'  => ['required','string','max:40'],
            'sss_number'      => ['required','string','max:40', Rule::unique('employees','sss_number')->ignore($employee->id)],
            'profile_picture' => ['nullable','image','mimes:jpg,jpeg,png','max:1024'],
        ]);

        DB::transaction(function () use ($request, $data, $employee) {

            $employee->user->name  = $data['name'];
            $employee->user->email = $data['email'];
            if (!empty($data['password'])) {
                $employee->user->password = $data['password']; // cast hashes
            }
            $employee->user->save();

            if ($request->hasFile('profile_picture')) {
                // Delete old profile picture if exists
                if ($employee->profile_picture && Storage::disk('public')->exists($employee->profile_picture)) {
                    Storage::disk('public')->delete($employee->profile_picture);
                }
                $employee->profile_picture = $this->storeProfilePicture(
                    $request->file('profile_picture'), 
                    $data['first_name'], 
                    $data['last_name']
                );
            }

            $employee->first_name     = $data['first_name'];
            $employee->last_name      = $data['last_name'];
            $employee->address        = $data['address'];
            $employee->contact_number = $data['contact_number'];
            $employee->sss_number     = $data['sss_number'];
            $employee->save();

            ActivityLog::record(
                'employee.updated',
                $employee,
                'Employee updated: '.$employee->first_name.' '.$employee->last_name,
                ['employee_id' => $employee->id]
            );
        });

        return redirect()->route('employees.edit',$employee->id)->with('success','Employee updated.');
    }

    public function destroy(Employee $employee)
    {
        $employee->load('user');

        if ($employee->profile_picture && Storage::disk('public')->exists($employee->profile_picture)) {
            Storage::disk('public')->delete($employee->profile_picture);
        }

        $user = $employee->user;

        DB::transaction(function () use ($employee, $user) {
            $employee->delete();
            if ($user && $user->role === 'employee') {
                $user->delete();
            }

            ActivityLog::record(
                'employee.archived',
                $employee,
                'Employee archived: '.$employee->first_name.' '.$employee->last_name,
                ['employee_id' => $employee->id, 'user_id' => $user?->id]
            );
        });

        return redirect()->route('employees.index')->with('success','Employee deleted.');
    }

    // restore a soft-deleted employee (and its user if applicable)
    public function restore($id)
    {
        $employee = Employee::withTrashed()->where('id', $id)->firstOrFail();
        $user = $employee->user()->withTrashed()->first();

        DB::transaction(function () use ($employee, $user) {
            if ($employee->trashed()) $employee->restore();
            if ($user && method_exists($user, 'restore') && $user->trashed()) $user->restore();

            ActivityLog::record(
                'employee.restored',
                $employee,
                'Employee restored: '.$employee->first_name.' '.$employee->last_name,
                ['employee_id' => $employee->id, 'user_id' => $user?->id]
            );
        });

        return redirect()->route('employees.index')->with('success','Employee restored.');
    }

    // permanently delete an employee and its user
    public function forceDelete($id)
    {
        $employee = Employee::withTrashed()->where('id', $id)->firstOrFail();
        $user = $employee->user()->withTrashed()->first();

        if ($employee->profile_picture && Storage::disk('public')->exists($employee->profile_picture)) {
            Storage::disk('public')->delete($employee->profile_picture);
        }

        DB::transaction(function () use ($employee, $user) {
            $name = $employee->first_name.' '.$employee->last_name;
            if ($user && $user->role === 'employee' && method_exists($user, 'forceDelete')) {
                $user->forceDelete();
            }
            $employee->forceDelete();

            ActivityLog::record(
                'employee.permanently_deleted',
                null,
                'Employee permanently deleted: '.$name,
                ['employee_id' => $employee->id]
            );
        });

        return redirect()->route('employees.index')->with('success','Employee permanently deleted.');
    }

    /**
     * Get employee data for profile modal (AJAX)
     */
    public function show(Employee $employee)
    {
        $employee->load('user');
        
        return response()->json([
            'success' => true,
            'employee' => [
                'id' => $employee->id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'contact_number' => $employee->masked_contact,
                'sss_number' => $employee->masked_sss,
                'address' => $employee->address,
                'profile_picture' => $employee->profile_picture,
            ],
            'user' => [
                'email' => $employee->user->email ?? null,
                'role' => $employee->user->role ?? 'employee',
                'is_active' => $employee->user->is_active ?? true,
                'created_at' => $employee->user->created_at ?? null,
            ],
        ]);
    }

    /**
     * Deactivate an employee account (Admin only)
     */
    public function deactivate(Employee $employee)
    {
        $employee->load('user');

        if (!$employee->user) {
            return redirect()->route('employees.index')->with('error', 'User account not found.');
        }

        if ($employee->user->role === 'admin') {
            return redirect()->route('employees.index')->with('error', 'Cannot deactivate admin accounts.');
        }

        $employee->user->update(['is_active' => false]);

        ActivityLog::record(
            'employee.deactivated',
            $employee,
            'Employee deactivated: ' . $employee->first_name . ' ' . $employee->last_name,
            ['employee_id' => $employee->id, 'user_id' => $employee->user->id]
        );

        return redirect()->route('employees.index')->with('success', 'Employee account deactivated.');
    }

    /**
     * Reactivate an employee account (Admin only)
     */
    public function activate(Employee $employee)
    {
        $employee->load('user');

        if (!$employee->user) {
            return redirect()->route('employees.index')->with('error', 'User account not found.');
        }

        $employee->user->update(['is_active' => true]);

        ActivityLog::record(
            'employee.activated',
            $employee,
            'Employee reactivated: ' . $employee->first_name . ' ' . $employee->last_name,
            ['employee_id' => $employee->id, 'user_id' => $employee->user->id]
        );

        return redirect()->route('employees.index')->with('success', 'Employee account reactivated.');
    }
}