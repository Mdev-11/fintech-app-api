<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Support\Str;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Services\FaceRecognitionService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{

    public function register(Request $request, FaceRecognitionService $faceRecognitionService)
    {
        Log::info('Registration request:', $request->all());

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'unique:users,phone_number'],
            // 'password' => ['required', Password::min(8)->mixedCase()->numbers()->symbols()],
            'password' => ['required', Password::min(8)->mixedCase()->numbers()],
            'face_image' => ['required', 'file', 'image', 'mimes:jpeg,png', 'max:5120'],
            'id_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx,txt', 'max:5120'],
            'id_document_type' => ['nullable', 'string'], // e.g., national_id, passport
        ]);

        if ($validator->fails()) {
            Log::info('Validator failed');
            return response()->json(['errors' => $validator->errors()], 422);
        }
        Log::info('Validator passed');
        try {
            $response = DB::transaction(function () use ($request, $faceRecognitionService) {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email ?? null,
                    'password' => Hash::make($request->password),
                    'phone_number' => $request->phone_number,
                    'id_document_type' => $request->id_document_type ?? 'national_id',
                ]);

                $userFace = $request->file('face_image');
                $idDocument = $request->file('id_document');

                $paths = $this->storeUserFace($userFace, $idDocument, $user->id);
                Log::info('Stored paths:', $paths);

                $facePath = $paths['face_path'] ?? null;
                $idPath = $paths['id_path'] ?? null;
                $fullFacePath = $paths['full_face_path'] ?? null;

                if (!$facePath || !$idPath || !$fullFacePath) {
                    throw new \RuntimeException('Échec du stockage du visage et du document d\'identité de l\'utilisateur.');
                }

                // $indexed = $faceRecognitionService->indexFace($fullFacePath, $user->id);
                // if (!$indexed) {
                //     throw new \RuntimeException('Failed to index user face.');
                // }

                $user->update([
                    'face_image_path' => $facePath,
                    'id_document_path' => $idPath,
                ]);

                Notification::createAuthenticationNotification(
                    $user,
                    'Compte créé avec succès. Veuillez attendre la vérification.'
                );

                $token = $user->createToken('auth_token')->plainTextToken;

                return [
                    'user' => $user,
                    'token' => $token,
                ];
            });

            return response()->json([
                'message' => 'Inscription réussie',
                'user' => $response['user'],
                'token' => $response['token'],
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Registration failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'L\'inscription a échoué. ' . $e->getMessage(),
            ], 500);
        }
    }
    public function login(Request $request, FaceRecognitionService $faceRecognitionService)
    {
        Log::info('Login request:', $request->all());

        $validator = Validator::make($request->all(), [
            'phone_number' => ['required', 'string', 'exists:users,phone_number'],
            'password' => ['required', 'string'],
            'face_image' => ['required', 'file', 'image', 'mimes:jpeg,png', 'max:5120'],
        ]);
        

        if ($validator->fails()) {
            Log::info('Validator failed');
            return response()->json(['errors' => $validator->errors()], 422);
        }
        Log::info('Validator passed');
        // Step 1: Check user credentials
        $user = \App\Models\User::where('phone_number', $request->phone_number)->first();
        Log::info('User found:', ['user' => $user]);
        if (! $user || ! Hash::check($request->password, $user->password)) {
            Log::info('Invalid credentials');
            return response()->json(['message' => 'Identifiants invalides.'], 401);
        }
        Log::info('User found and credentials verified');
        //Normalize the face path
        $storedFacePath = $user->face_image_path;
        $storedFacePath = storage_path("app/private/{$storedFacePath}");
        $storedFacePath = str_replace('\\', '/', $storedFacePath);
        $targetFacePath = $request->file('face_image')->getRealPath();

        if(!file_exists($storedFacePath)){
            return response()->json([
                'message' => 'La vérification du visage a échoué. Aucune image de visage trouvée pour l\'utilisateur.',
            ], 401);
        }
        if(!file_exists($targetFacePath)){
            return response()->json([
                'message' => 'La vérification du visage a échoué. Aucune image de visage fournie.',
            ], 401);
        }
        $faceVerified = $faceRecognitionService->verifyFace($storedFacePath, $targetFacePath);

        
        if (!$faceVerified) {
            return response()->json([
                'message' => 'La vérification du visage a échoué. Veuillez réessayer.',
            ], 401);
        }

        $user->update([
            'last_login_at' => now(),
            'device_token' => $request->device_token,
        ]);

        Notification::createAuthenticationNotification(
            $user,
            'Nouvelle connexion détectée'
        );

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie',
            'user' => $user,
            'token' => $token,
            // 'face_verified' => $faceVerified,
        ]);
    }

    public function logout(Request $request)
    {
        // $request->user()->currentAccessToken()->delete();
        $request->user()->tokens()->delete();
        Notification::createAuthenticationNotification(
            $request->user(),
            'Déconnexion réussie'
        );
        return response()->json([
            'message' => 'Déconnexion réussie',
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
    function storeUserFace($userFace, $idDocument, $userId){
        //Log user face and id document details
        Log::info('Storing user face and ID document', [
            'user_id' => $userId,
            'face_image' => $userFace->getClientOriginalName(),
            'id_document' => $idDocument->getClientOriginalName(),
        ]);
        $filename = 'user-face-' . $userId .'-'.uniqid(). '.' . $userFace->getClientOriginalExtension();
        $facePath = $userFace->storeAs('faces', $filename, 'local');
        $fullFacePath = storage_path('app/' . $facePath);

        $filename = 'user-id-' . $userId .'-'.uniqid(). '.' . $idDocument->getClientOriginalExtension();
        $idPath = $idDocument->storeAs('ids', $filename, 'local');
        return [
            'face_path' => $facePath,
            'id_path' => $idPath,
            'full_face_path' => $fullFacePath,
        ];
    }
} 