<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class UserManagementController extends Controller
{
    // Middleware is now handled in routes/web.php for Laravel 11
    // No constructor needed for middleware registration

    /**
     * Display a listing of users
     */
    public function index()
    {
        try {
            $users = User::orderBy('name')->paginate(15);

            return view('users.index', compact('users'));
        } catch (\Exception $e) {
            Log::error('Users Index Error', [
                'message' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Unable to load users. Please try again.');
        }
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        return view('users.create');
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'confirmed', Password::defaults()],
                'role' => ['required', 'string', 'in:admin,sales_rep'],
                'pin' => ['nullable', 'digits_between:4,6'],
            ]);

            DB::beginTransaction();

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'pin' => ! empty($validated['pin']) ? Hash::make($validated['pin']) : null,
            ]);

            DB::commit();

            return redirect()->route('users.index')
                ->with('success', 'User created successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User Store Error', [
                'message' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create user. Please try again.');
        }
    }

    /**
     * Show the form for editing a user
     */
    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
                'role' => ['required', 'string', 'in:admin,sales_rep'],
                'password' => ['nullable', 'confirmed', Password::defaults()],
                'pin' => ['nullable', 'digits_between:4,6'],
                'clear_pin' => ['nullable', 'boolean'],
            ]);

            DB::beginTransaction();

            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
            ];

            // Only update password if provided
            if (! empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            // PIN: set when provided, remove when explicitly cleared
            if (! empty($validated['pin'])) {
                $updateData['pin'] = Hash::make($validated['pin']);
            } elseif ($request->boolean('clear_pin')) {
                $updateData['pin'] = null;
            }

            $user->update($updateData);

            DB::commit();

            return redirect()->route('users.index')
                ->with('success', 'User updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User Update Error', [
                'message' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update user. Please try again.');
        }
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user)
    {
        // Prevent deletion of the last admin user
        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return redirect()->route('users.index')
                ->with('error', 'Cannot delete the last admin user.');
        }

        // Prevent users from deleting themselves
        if ($user->id === Auth::id()) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully!');
    }
}
