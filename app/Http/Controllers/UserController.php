<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // LISTAR USUÁRIOS
    public function index()
    {
        return response()->json(User::paginate(20));
    }

    // CRIAR USUÁRIO
    public function store(UserRequest $request)
    {
        $this->blockManagerEditingAdmins($request->role);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json($user, 201);
    }

    // DETALHAR
    public function show(User $user)
    {
        $this->blockManagerViewingAdmins($user);
        return response()->json($user);
    }

    // EDITAR
    public function update(UserRequest $request, User $user)
    {
        $this->blockManagerEditingAdmins($user->role);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password
                ? Hash::make($request->password)
                : $user->password,
            'role' => $request->role,
        ]);

        return response()->json($user);
    }

    // APAGAR
    public function destroy(User $user)
    {
        $this->blockManagerEditingAdmins($user->role);

        $user->delete();

        return response()->json(['message' => 'Usuário removido']);
    }

    /*
    |--------------------------------------------------------------------------
    | Métodos privados de regra de role
    |--------------------------------------------------------------------------
    */

    private function blockManagerEditingAdmins(string $role)
    {
        if (auth()->user()->role === 'MANAGER' && $role === 'ADMIN') {
            abort(403, 'MANAGER não pode alterar ADMIN.');
        }
    }

    private function blockManagerViewingAdmins(User $user)
    {
        if (auth()->user()->role === 'MANAGER' && $user->role === 'ADMIN') {
            abort(403, 'MANAGER não pode visualizar ADMIN.');
        }
    }
}
