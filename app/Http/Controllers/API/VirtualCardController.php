<?php

namespace App\Http\Controllers\API;

use App\Models\VirtualCard;
use Illuminate\Support\Str;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class VirtualCardController extends Controller
{
     // Get authenticated user's card (or create if doesn't exist)
     public function show(Request $request)
     {
         $user = $request->user();
            // Validate user has a wallet
            if (!$user->wallet) {
                return response()->json(['message' => 'L\'utilisateur n\'a pas de portefeuille'], 404);
            }
            // Validate user has a virtual card or create one
 
         $card = $user->wallet->virtualCard ?? $user->wallet->virtualCard()->create([
             'uuid' => (string) Str::uuid()
         ]);
 
         return response()->json([
             'card' => $card,
             'qr_code_data' => route('api.qrcode.resolve', ['uuid' => $card->uuid]),
         ]);
     }
 
     // Optional: allow QR code resolution via UUID
     public function resolve($uuid)
     {
         $card = VirtualCard::where('uuid', $uuid)->firstOrFail();
 
         return response()->json([
             'card' => $card,
             'owner' => [
                 'id' => $card->user->id,
                 'name' => $card->user->name,
                 'phone' => $card->user->phone_number,
             ]
         ]);
     }
 
     // Optional: deactivate a card
     public function deactivate(Request $request)
     {
         $card = $request->user()->virtualCard;
         if ($card) {
             $card->update(['status' => 'inactive']);
             return response()->json(['message' => 'Carte désactivée']);
         }
         return response()->json(['message' => 'Aucune carte trouvée'], 404);
     }
 
     public function regenerate(Request $request)
     {
         $card = $request->user()->virtualCard;
         if ($card) {
             $card->update([
                 'uuid' => (string) Str::uuid(),
                 'status' => 'active'
             ]);
             return response()->json(['message' => 'Carte régénérée', 'card' => $card]);
         }
         return response()->json(['message' => 'Aucune carte trouvée'], 404);
     }

   
} 