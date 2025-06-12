<?php

namespace App\Http\Controllers\API;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->get('limit', 15);
        $cursor = $request->get('cursor');
        
        $query = $request->user()->notifications();

        if ($cursor) {
            $query->where('created_at', '<', $cursor);
        }

        $query->orderBy('created_at', 'desc')
              ->orderBy('id', 'desc');

        $notifications = $query->take($limit + 1)->get();

        $hasMore = $notifications->count() > $limit;
        $notifications = $notifications->take($limit);
        $unreadCount = $request->user()->notifications()->unread()->count();
          Log::info('unread_count at index', [
            'unread_count' => $unreadCount,
        ]);
        return response()->json([
            'data' => $notifications,
            'has_more' => $hasMore,
            'next_cursor' => $hasMore ? $notifications->last()->created_at->toIso8601String() : null,
            'unread_count' => $request->user()->notifications()->unread()->count(),
        ]);
    }

    public function markAsRead(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Non autorisé',
            ], 403);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marquée comme lue',
            'notification' => $notification,
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Toutes les notifications ont été marquées comme lues',
        ]);
    }

    public function destroy(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Non autorisé',
            ], 403);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notification supprimée avec succès',
        ]);
    }

    public function updateDeviceToken(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string',
        ]);

        $request->user()->update([
            'device_token' => $request->device_token,
        ]);

        return response()->json([
            'message' => 'Jeton de l\'appareil mis à jour avec succès',
        ]);
    }

    public function unreadCount(Request $request)
    {
        
        $count = $request->user()->notifications()->unread()->count();
        Log::info('unread_count', [
            'unread_count' => $count,
            'sql' => $request->user()->notifications()->unread()->toSql(),
            'get' => $request->user()->notifications()->unread()->get(),
        ]);
        return response()->json([
            'unread_count' => $count,
        ]);
    }
} 