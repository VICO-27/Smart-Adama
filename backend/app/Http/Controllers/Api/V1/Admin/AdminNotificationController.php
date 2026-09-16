<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

class AdminNotificationController extends Controller
{
    /**
     * Get all admin notifications
     */
    public function index(Request $request): JsonResponse
    {
        // Admin notifications are often global, but in Laravel they are attached to a notifiable model.
        // We will assume the authenticated admin user is the notifiable.
        $user = $request->user();

        if (! $user) {
            return response()->json(['notifications' => []]);
        }

        $notifications = $user->notifications()->latest()->limit(50)->get();
        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['success' => false], 401);
        }

        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $user->unreadNotifications->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Seed mock notifications for testing the UI
     */
    public function seedMock(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['success' => false], 401);
        }

        // Delete existing mock notifications to avoid infinite clutter
        $user->notifications()->delete();

        // Seed new ones
        $mockNotifications = [
            [
                'id' => Str::uuid()->toString(),
                'type' => 'App\Notifications\EmbeddingComplete',
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'title' => 'Embedding Complete',
                    'message' => 'The semantic chunking and embedding for "Smart Adama Guide" completed in 10.3s.',
                    'category' => 'RAG Pipeline',
                    'icon' => 'check-circle',
                    'color' => 'green',
                ]),
                'read_at' => null,
                'created_at' => now()->subMinutes(5),
                'updated_at' => now()->subMinutes(5),
            ],
            [
                'id' => Str::uuid()->toString(),
                'type' => 'App\Notifications\NewUserRegistered',
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'title' => 'New User Registration',
                    'message' => 'A new user (vico@smartadama.com) just joined the platform.',
                    'category' => 'Users',
                    'icon' => 'user-plus',
                    'color' => 'blue',
                ]),
                'read_at' => null,
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ],
            [
                'id' => Str::uuid()->toString(),
                'type' => 'App\Notifications\SupabaseAlert',
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'title' => 'Supabase Connection Warning',
                    'message' => 'High latency detected in the Supabase PostgreSQL connection pool.',
                    'category' => 'System',
                    'icon' => 'alert-triangle',
                    'color' => 'red',
                ]),
                'read_at' => null,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
            [
                'id' => Str::uuid()->toString(),
                'type' => 'App\Notifications\SystemUpdate',
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'title' => 'System Update Available',
                    'message' => 'Smart Adama Core v2.1.4 is ready for deployment.',
                    'category' => 'System',
                    'icon' => 'info',
                    'color' => 'gray',
                ]),
                'read_at' => now()->subDays(2),
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ],
        ];

        DatabaseNotification::insert($mockNotifications);

        return response()->json(['success' => true]);
    }
}
