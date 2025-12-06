<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Your Password - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-emerald-50 to-emerald-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo/Brand -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-emerald-600 rounded-full mb-4">
                <span class="text-3xl">🐔</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">{{ config('app.name') }}</h1>
            <p class="text-gray-600">Set your password to get started</p>
        </div>

        <!-- Card -->
        <div class="bg-white rounded-xl shadow-lg p-8">
            @if(session('status'))
                <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 rounded-lg">
                    <p class="text-emerald-700 text-sm">{{ session('status') }}</p>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    @foreach($errors->all() as $error)
                        <p class="text-red-700 text-sm">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-6">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        Email Address
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="{{ old('email', $email) }}"
                        readonly
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed"
                    >
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        New Password
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        autofocus
                        minlength="8"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                        placeholder="Minimum 8 characters"
                    >
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                        Confirm Password
                    </label>
                    <input 
                        type="password" 
                        id="password_confirmation" 
                        name="password_confirmation" 
                        required
                        minlength="8"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                        placeholder="Re-enter your password"
                    >
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit"
                    class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg transition-colors focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                >
                    Set Password & Continue
                </button>
            </form>
        </div>

        <!-- Footer -->
        <p class="text-center text-gray-500 text-sm mt-6">
            Already have your password? 
            <a href="/admin/login" class="text-emerald-600 hover:text-emerald-700 font-medium">Log in</a>
        </p>
    </div>
</body>
</html>

