<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Mail\CambioContraseñaEmail;
use Illuminate\Support\Facades\Mail;

class PasswordController extends Controller
{
    /**
     * Actualizar la contraseña del usuario
     */
    public function update(Request $request): RedirectResponse {

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $request->user();

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // ENVÍO DE CORREO
        Mail::to($user->email)->send(
            new CambioContraseñaEmail(
                $validated['password'],
                $user
            )
        );

        return back();
    }

}
