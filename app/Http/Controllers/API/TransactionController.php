<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->input('limit', 5);

        $transactions = Transaction::where(function ($query) use ($request) {
                $query->where('sender_id', $request->user()->id)
                    ->orWhere('receiver_id', $request->user()->id);
            })
            ->orderBy('id', 'desc') // Use a unique column for cursor pagination
            ->cursorPaginate($limit);

        return response()->json([
            'data' => $transactions->items(),
            'has_more' => $transactions->hasMorePages(),
            'next_cursor' => optional($transactions->nextCursor())->encode(),
        ]);
    }

    public function show(Transaction $transaction)
    {
        $user = request()->user();
        
        if ($transaction->sender_id !== $user->id && $transaction->receiver_id !== $user->id) {
            return response()->json([
                'message' => 'Non autorisé',
            ], 403);
        }

        return response()->json($transaction);
    }
} 