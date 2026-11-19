<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        Gate::authorize('admin');

        return UserResource::collection(User::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        Gate::authorize('admin');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users'],
            'role' => ['required', Rule::in(['admin', 'operatore'])],
            'password' => ['required', Password::min(8)],
        ]);

        return (new UserResource(User::create($data)))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, User $user)
    {
        Gate::authorize('admin');
        if ($user->is($request->user())) {
            return response()->json(['message' => 'Non puoi eliminare il tuo account.'], 422);
        }
        $user->delete();

        return response()->noContent();
    }
}
