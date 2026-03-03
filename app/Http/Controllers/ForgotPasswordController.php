<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PasswordResetOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Resend\Laravel\Facades\Resend;

class ForgotPasswordController extends Controller
{
    /**
     * Show the forgot password form
     */
    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Send OTP to the user's email
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'No account found with this email address.',
            ])->withInput();
        }

        // Generate OTP
        $otpData = PasswordResetOtp::createForUser($user);
        $plainOtp = $otpData['plain_code'];

        // Send OTP via Resend
        try {
            Resend::emails()->send([
                'from' => config('mail.from.address'),
                'to' => [$user->email],
                'subject' => 'Password Reset OTP - ' . config('app.name'),
                'html' => $this->getOtpEmailHtml($user, $plainOtp),
            ]);
        } catch (\Exception $e) {
            return back()->withErrors([
                'email' => 'Failed to send OTP. Please try again later.',
            ])->withInput();
        }

        // Store email in session for verification step
        session(['otp_reset_email' => $user->email]);

        return redirect()->route('password.verify-otp.form')
            ->with('success', 'A 6-digit OTP has been sent to your email address.');
    }

    /**
     * Show the OTP verification form
     */
    public function showVerifyOtpForm()
    {
        if (!session('otp_reset_email')) {
            return redirect()->route('password.forgot');
        }

        return view('auth.verify-otp');
    }

    /**
     * Verify the OTP code
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $email = session('otp_reset_email');

        if (!$email) {
            return redirect()->route('password.forgot')
                ->withErrors(['error' => 'Session expired. Please try again.']);
        }

        $otp = PasswordResetOtp::verifyOtp($email, $request->otp);

        if (!$otp) {
            return back()->withErrors([
                'otp' => 'Invalid or expired OTP code. Please try again.',
            ]);
        }

        // Mark OTP as used
        $otp->markAsUsed();

        // Store verification token in session
        session(['otp_verified' => true]);

        return redirect()->route('password.reset.form')
            ->with('success', 'OTP verified. Please set your new password.');
    }

    /**
     * Show the reset password form
     */
    public function showResetForm()
    {
        if (!session('otp_verified') || !session('otp_reset_email')) {
            return redirect()->route('password.forgot');
        }

        return view('auth.reset-password');
    }

    /**
     * Reset the user's password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $email = session('otp_reset_email');

        if (!session('otp_verified') || !$email) {
            return redirect()->route('password.forgot')
                ->withErrors(['error' => 'Session expired. Please try again.']);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->route('password.forgot')
                ->withErrors(['error' => 'User not found.']);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Clear session data
        session()->forget(['otp_reset_email', 'otp_verified']);

        return redirect()->route('login')
            ->with('success', 'Password reset successfully. You can now login with your new password.');
    }

    /**
     * Generate OTP email HTML content
     */
    private function getOtpEmailHtml(User $user, string $otp): string
    {
        $appName = config('app.name');
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #333; margin: 0;">{$appName}</h1>
            <p style="color: #666; margin-top: 5px;">Password Reset Request</p>
        </div>
        
        <p style="color: #333; font-size: 16px;">Hello {$user->name},</p>
        
        <p style="color: #666; font-size: 14px;">You have requested to reset your password. Please use the following OTP code to verify your identity:</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <div style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px 40px; border-radius: 10px;">
                <span style="font-size: 32px; font-weight: bold; color: #ffffff; letter-spacing: 8px;">{$otp}</span>
            </div>
        </div>
        
        <p style="color: #666; font-size: 14px;">This code will expire in <strong>10 minutes</strong>.</p>
        
        <p style="color: #666; font-size: 14px;">If you did not request a password reset, please ignore this email or contact support if you have concerns.</p>
        
        <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
        
        <p style="color: #999; font-size: 12px; text-align: center;">
            This is an automated email from {$appName}. Please do not reply to this email.
        </p>
    </div>
</body>
</html>
HTML;
    }
}
