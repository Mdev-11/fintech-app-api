<?php

namespace App\Services;

use Exception;
use Aws\Credentials\Credentials;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Aws\Rekognition\RekognitionClient;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class FaceRecognitionService
{
    private RekognitionClient $rekognition;
    private string $collectionId = 'user_faces';
    public function __construct()
    {
        
        $this->rekognition = new RekognitionClient([
            'version' => 'latest',
            'region' => Config::get('services.aws.region'),
            'credentials' => [
                'key'    => Config::get('services.aws.key'),
                'secret' => Config::get('services.aws.secret'),
                'token'  => Config::get('services.aws.token'),
            ]
        ]);
    }

    public function indexFace($faceImagePath,int $userId): bool
    {
        $faceBytes = file_get_contents($faceImagePath);
        try {
            


            // Index the face in AWS Collection
            $result = $this->rekognition->indexFaces([
                'CollectionId' => $this->collectionId,
                'Image' => [
                    'Bytes' => $faceBytes,
                ],
                'ExternalImageId' => (string) $userId,
                'DetectionAttributes' => ['ALL'],
                'MaxFaces' => 1,
                'QualityFilter' => 'AUTO'
            ]);

            return !empty($result['FaceRecords']);
             return true;
        } catch (Exception $e) {
            Log::error('Face storage failed: ' . $e->getMessage());
            return false;
        }
    }

    public function verifyFace($storedFacePath, $targetFacePath)
    {
        //Log stored and target face paths
        Log::info('Stored face path: ', ['path' => $storedFacePath]);
        Log::info('Target face path: ', ['path' => $targetFacePath]);
        $sourceImageBytes = file_get_contents($storedFacePath);
        $targetImageBytes = file_get_contents($targetFacePath);
        
        try {

            if (!$sourceImageBytes || !$targetImageBytes) {
                return false;
            }

            $result = $this->rekognition->compareFaces([
                'SourceImage' => [
                    'Bytes' => $sourceImageBytes,
                ],
                'TargetImage' => [
                    'Bytes' => $targetImageBytes,
                ],
                'SimilarityThreshold' => 90.0
            ]);
            // Log the result for debugging
            Log::info('Face comparison result:', ['result' => $result]);
            // return $result;
            return !empty($result['FaceMatches']) && $result['FaceMatches'][0]['Similarity'] >= 90.0;
        } catch (Exception $e) {
            Log::error('Face verification failed: ' . $e->getMessage());
            return false;
        }
    }

    public function createCollection(): bool
    {
        try {
            $this->rekognition->createCollection([
                'CollectionId' => $this->collectionId
            ]);
            return true;
        } catch (Exception $e) {
            Log::error('Collection creation failed: ' . $e->getMessage());
            return false;
        }
    }
} 