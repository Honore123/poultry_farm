<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SetPasswordController extends Controller
{
    /**
     * Show the password setup form.
     */
    public function showSetPasswordForm(Request $request, string $token)
    {
        $email = $request->query('email');
        
        return view('auth.set-password', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    /**
     * Handle the password setup request.
     */
    public function setPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Check if token is valid
        $tokenRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$tokenRecord) {
            return back()->withErrors([
                'email' => 'This password reset link is invalid.',
            ]);
        }

        // Verify token
        if (!Hash::check($request->token, $tokenRecord->token)) {
            return back()->withErrors([
                'email' => 'This password reset link is invalid.',
            ]);
        }

        // Check if token expired (60 minutes)
        $tokenCreatedAt = \Carbon\Carbon::parse($tokenRecord->created_at);
        if ($tokenCreatedAt->addMinutes(60)->isPast()) {
            return back()->withErrors([
                'email' => 'This password reset link has expired. Please request a new one.',
            ]);
        }

        // Find the user and update password
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'We could not find a user with that email address.',
            ]);
        }

        // Update password
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete the token
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        // Redirect to login with success message
        return redirect('/admin/login')->with('status', 'Your password has been set! You can now log in.');
    }
}

