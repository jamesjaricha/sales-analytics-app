@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="mb-10">
            <div class="flex items-center space-x-4">
                <a href="{{ route('users.index') }}"
                    class="text-gray-500 hover:text-gray-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-4xl font-bold text-gray-900">Create New User</h1>
                    <p class="text-gray-500 mt-2">Add a new user to the system</p>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
            <form action="{{ route('users.store') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Full Name
                    </label>
                    <input type="text"
                        name="name"
                        id="name"
                        value="{{ old('name') }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        required>
                    @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email Address
                    </label>
                    <input type="email"
                        name="email"
                        id="email"
                        value="{{ old('email') }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        required>
                    @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Role -->
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-2">
                        Role
                    </label>
                    <select name="role"
                        id="role"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        required>
                        <option value="">Select a role</option>
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="sales_rep" {{ old('role') === 'sales_rep' ? 'selected' : '' }}>Sales Rep</option>
                    </select>
                    @error('role')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Password
                    </label>
                    <input type="password"
                        name="password"
                        id="password"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        required>
                    @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                        Confirm Password
                    </label>
                    <input type="password"
                        name="password_confirmation"
                        id="password_confirmation"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        required>
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end space-x-4 pt-6">
                    <a href="{{ route('users.index') }}"
                        class="px-6 py-3 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50 font-medium transition-colors">
                        Cancel
                    </a>
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-xl shadow-lg transition-all duration-200 transform hover:scale-105">
                        Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection