<?php

namespace App\Http\Controllers\Api\V1\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->withCount('quizAttempts');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status !== 'All Statuses') {
            $query->where('status', strtolower($request->status));
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        $mappedUsers = $users->map(function ($user) {
            // Mock a progress stat for now since we don't have a direct field for "overall progress"
            // We can base it roughly on their level or quiz attempts
            $progress = min(100, max(0, ($user->level * 5) + ($user->quiz_attempts_count * 2)));

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar_url ?: "https://ui-avatars.com/api/?name=" . urlencode($user->name) . "&background=F0F3FA&color=395886",
                'registered' => $user->created_at->format('M j, Y'),
                'progress' => $progress . '%',
                'status' => ucfirst($user->status ?? 'Active'),
                'level' => $user->level,
                'xp' => $user->xp
            ];
        });

        return response()->json([
            'data' => $mappedUsers,
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
            ]
        ]);
    }

    public function show(User $user): JsonResponse
    {
        $user->load(['quizAttempts.quiz', 'progress.chapter']);

        $progress = min(100, max(0, ($user->level * 5) + ($user->quizAttempts()->count() * 2)));

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar_url ?: "https://ui-avatars.com/api/?name=" . urlencode($user->name) . "&background=F0F3FA&color=395886",
                'registered' => $user->created_at->format('M j, Y'),
                'status' => ucfirst($user->status ?? 'Active'),
                'level' => $user->level,
                'xp' => $user->xp,
                'overall_progress' => $progress,
                'quiz_attempts' => $user->quizAttempts,
                'chapter_progress' => $user->progress,
            ]
        ]);
    }

    public function invite(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'name' => 'required|string|max:255',
            'role' => 'nullable|string'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt(str()->random(16)), // random password
            'status' => 'active'
        ]);

        return response()->json([
            'message' => 'User invited successfully.',
            'user' => $user
        ], 201);
    }
}
