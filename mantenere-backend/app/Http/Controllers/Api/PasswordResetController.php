<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\User;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'No encontramos ningún usuario con este correo electrónico.'
        ]);

        $token = Str::random(60);

        // Delete any existing tokens for this email
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Insert new token
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => Hash::make($token),
            'created_at' => Carbon::now()
        ]);

        // Create the reset link
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $resetLink = $frontendUrl . '/reset-password?token=' . $token . '&email=' . urlencode($request->email);

        // Send Email
        Mail::send('emails.reset_password', ['resetLink' => $resetLink], function($message) use ($request) {
            $message->to($request->email);
            $message->subject('Restablecer Contraseña - Mantenere');
        });

        return response()->json([
            'message' => 'Te hemos enviado un enlace por correo para restablecer tu contraseña.'
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.'
        ]);

        $resetRecord = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$resetRecord || !Hash::check($request->token, $resetRecord->token)) {
            return response()->json(['message' => 'El token de recuperación es inválido o ha expirado.'], 400);
        }

        // Check if token is expired (e.g., 60 minutes)
        if (Carbon::parse($resetRecord->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['message' => 'El token ha expirado. Por favor solicita uno nuevo.'], 400);
        }

        // Update User Password
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Delete the token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'message' => '¡Tu contraseña ha sido restablecida exitosamente!'
        ]);
    }
}
