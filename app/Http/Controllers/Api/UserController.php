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
        $contactsParam = $request->query('contacts');

        $query = $request->user()->newQuery()
            ->where('id', '!=', $request->user()->id);

        if ($contactsParam) {
            $phones = is_array($contactsParam) ? $contactsParam : explode(',', (string) $contactsParam);
            $cleanPhones = array_filter(array_map('trim', $phones));
            if (! empty($cleanPhones)) {
                $query->whereIn('phone', $cleanPhones);
            }
        } elseif ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        } else {
            // Do not show all users by default for user privacy.
            // Require searching by phone number, username, or email.
            return response()->json([]);
        }

        $users = $query->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'username', 'email', 'phone', 'avatar', 'last_seen_at']);

        return response()->json($users);
    }

    public function syncContacts(Request $request): JsonResponse
    {
        $data = $request->validate([
            'contacts' => ['required', 'array'],
            'contacts.*' => ['string'],
        ]);

        $phones = array_filter(array_map(fn ($p) => preg_replace('/[^\d+]/', '', (string) $p), $data['contacts']));

        $users = $request->user()->newQuery()
            ->where('id', '!=', $request->user()->id)
            ->whereIn('phone', $phones)
            ->get(['id', 'name', 'username', 'email', 'phone', 'avatar', 'last_seen_at']);

        return response()->json($users);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'username' => ['nullable', 'string', 'max:60', 'alpha_dash', 'unique:users,username,' . $user->id],
            'phone' => ['nullable', 'string', 'max:32', 'unique:users,phone,' . $user->id],
            'avatar' => ['nullable', 'string', 'max:500000'],
        ]);

        $user->fill($data)->save();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user->fresh(),
        ]);
    }
}
