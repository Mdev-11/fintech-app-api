<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    public function show(Request $request)
    {
        // $wallet = $request->user()->wallet ?? $request->user()->wallet()->create([
        //    'user_id' => $request->user()->id,
        // ]);
        $user = $request->user();
        //get the user wallet allong with the associated virtual card
        $wallet = $user->wallet()->with('virtualCard')->first();
        if (!$wallet) {
            // Create a new wallet if it doesn't exist
            $wallet = Wallet::create([
                'user_id' => $user->id,
                'balance' => 0.00,
                'currency' => config('wallet.currency', 'XOF'),
                'is_active' => true,
                'settings' => [],
            ]);
        }
        //Rturn the wallet with the virtual card
        return response()->json([
            'wallet' => $wallet,
            // 'virtual_card' => $wallet->virtualCard,
            'qr_code_data' => route('api.qrcode.resolve', ['uuid' => $wallet->virtualCard->uuid]),
        ]);
    }

    public function recharge(Request $request)
    {
        Log::info('Recharge request received', [
            'user_id' => $request->user()->id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            // 'payment_details' => $request->payment_details,
        ]);
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string'],
            // 'payment_details' => ['required', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $transaction = $request->user()->wallet->recharge(
                $request->amount,
                $request->payment_method,
                $request->payment_details
            );

            return response()->json([
                'message' => 'Recharge réussie',
                'transaction' => $transaction,
                'wallet' => $request->user()->wallet,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'La recharge a échoué',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    
    public function transfer(Request $request)
    {
        Log::info('Transfer request received', [
            'user_id' => $request->user()->id,
            'recipient_phone_number' => $request->recipient_phone_number,
            'amount' => $request->amount,
            'description' => $request->description,
        ]);

        $validator = Validator::make($request->all(), [
            'recipient_phone_number' => ['required', 'exists:users,phone_number'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $sender = $request->user();
        $receiver = User::where('phone_number', $request->recipient_phone_number)->first();
        if (!$receiver) {
            return response()->json(['message' => 'Destinataire non trouvé'], 404);
        }

        if ($sender->id === $receiver->id) {
            return response()->json(['message' => 'Impossible de transférer à vous-même'], 400);
        }

        $platformUserNumber = config('wallet.phone_number') ?? '771230000'; // Default platform user phone number
        // Get platform user by phone number
        $platformUser = User::where('phone_number', $platformUserNumber)->first();
        if (!$platformUser) {
            return response()->json(['message' => 'Utilisateur de la plateforme non trouvé'], 404);
        }

        try {
            $rawAmount = floatval($request->amount);
            $feeRate = config('wallet.transfer_fee_percent') / 100.00;
            $fee = round($rawAmount * $feeRate, 2);
            $netAmount = round($rawAmount - $fee, 2);

            if ($netAmount <= 0) {
                return response()->json(['message' => 'Le montant est trop faible pour couvrir les frais de transfert'], 400);
            }

            if ($sender->wallet->balance < $rawAmount) {
                return response()->json(['message' => 'Solde insuffisant'], 400);
            }

            DB::beginTransaction();

            $netTransfer = $sender->wallet->transfer($receiver->wallet, $netAmount, [
                'type' => 'transfer',
                'description' => $request->description ?? 'Transfert vers ' . $receiver->phone_number,
            ]);
         
            $feeTransfer = $sender->wallet->transfer($platformUser->wallet, $fee, [
                'type' => 'transfer-fee',
                'description' => 'Frais de transfert vers la plateforme pour le transfert vers ' . $receiver->phone_number,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Transfert réussi',
                'net_transaction' => $netTransfer,
                'fee_transaction' => $feeTransfer,
                'wallet' => $sender->wallet->fresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Transfer failed', [
                'error' => $e->getMessage(),
                'user_id' => $sender->id,
                'recipient_phone_number' => $request->recipient_phone_number,
                'amount' => $request->amount,
            ]);
            return response()->json([
                'message' => 'Le transfert a échoué',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function withdraw(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'withdrawal_method' => ['nullable', 'string'],
            'withdrawal_details' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $transaction = $request->user()->wallet->withdraw(
                $request->amount,
                $request->withdrawal_method,
                $request->withdrawal_details
            );

            return response()->json([
                'message' => 'Retrait réussi',
                'transaction' => $transaction,
                'wallet' => $request->user()->wallet,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Le retrait a échoué',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
} 