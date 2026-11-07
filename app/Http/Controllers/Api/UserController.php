<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        $users = $request->user()->newQuery()
            ->where('id', '!=', $request->user()->id)
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }
}
