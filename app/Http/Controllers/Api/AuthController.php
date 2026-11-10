<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Autenticación de la app móvil con tokens de Laravel Sanctum (RNF-04).
 */
class AuthController extends Controller
{
    use ApiResponse;

    public function login(Request $request): JsonResponse
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::where('email', $credenciales['email'])->first();

        if (! $user || ! Hash::check($credenciales['password'], $user->password)) {
            return $this->errorResponse('Credenciales incorrectas.', 401);
        }

        if (! $user->hasAnyRole(['Asesor', 'Administrador'])) {
            return $this->errorResponse('El usuario no tiene un rol asignado en el CRM.', 403);
        }

        $token = $user->createToken($credenciales['device_name'] ?? 'app-movil');
        $minutos = config('sanctum.expiration');

        return $this->successResponse([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expira_en' => $minutos ? now()->addMinutes($minutos)->toIso8601String() : null,
            'usuario' => $this->usuario($user),
        ], 'Inicio de sesión correcto.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->successResponse($this->usuario($request->user()));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Sesión cerrada.');
    }

    private function usuario(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
        ];
    }
}
