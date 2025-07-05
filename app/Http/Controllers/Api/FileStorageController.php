<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileStorageController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function uploadFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // Max 10MB
            'folder' => 'string|nullable'
        ]);

        try {
            $file = $request->file('file');
            $folder = $request->input('folder', 'uploads');

            // Generate unique filename
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $firebasePath = $folder . '/' . $filename;

            // Save temporarily to local storage
            $tempPath = $file->storeAs('temp', $filename);
            $fullTempPath = storage_path('app/' . $tempPath);

            // Upload to Firebase Storage
            $result = $this->firebaseService->uploadFile($fullTempPath, $firebasePath, [
                'contentType' => $file->getMimeType(),
                'metadata' => [
                    'originalName' => $file->getClientOriginalName(),
                    'uploadedAt' => now()->toISOString()
                ]
            ]);

            // Delete temporary file
            Storage::delete($tempPath);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => [
                    'firebase_path' => $result['firebase_path'] ?? null,
                    'public_url' => $result['public_url'] ?? null,
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType()
                ]
            ], $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function uploadBase64(Request $request)
    {
        $request->validate([
            'base64_data' => 'required|string',
            'filename' => 'required|string',
            'folder' => 'string|nullable'
        ]);

        try {
            $base64Data = $request->input('base64_data');
            $filename = $request->input('filename');
            $folder = $request->input('folder', 'uploads');

            // Decode base64
            if (preg_match('/^data:([a-zA-Z0-9]+\/[a-zA-Z0-9-.+]+);base64,(.*)$/', $base64Data, $matches)) {
                $mimeType = $matches[1];
                $data = base64_decode($matches[2]);
            } else {
                $data = base64_decode($base64Data);
                $mimeType = 'application/octet-stream';
            }

            // Generate unique filename
            $uniqueFilename = time() . '_' . Str::random(10) . '_' . $filename;
            $firebasePath = $folder . '/' . $uniqueFilename;

            // Upload to Firebase Storage
            $result = $this->firebaseService->uploadFromString($data, $firebasePath, [
                'contentType' => $mimeType,
                'metadata' => [
                    'originalName' => $filename,
                    'uploadedAt' => now()->toISOString()
                ]
            ]);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => [
                    'firebase_path' => $result['firebase_path'] ?? null,
                    'public_url' => $result['public_url'] ?? null,
                    'original_name' => $filename,
                    'size' => strlen($data),
                    'mime_type' => $mimeType
                ]
            ], $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteFile(Request $request)
    {
        $request->validate([
            'firebase_path' => 'required|string'
        ]);

        $firebasePath = $request->input('firebase_path');
        $result = $this->firebaseService->deleteFile($firebasePath);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message']
        ], $result['success'] ? 200 : 500);
    }

    public function getFileUrl(Request $request)
    {
        $request->validate([
            'firebase_path' => 'required|string',
            'expires_hours' => 'integer|nullable|min:1|max:168' // Max 1 week
        ]);

        $firebasePath = $request->input('firebase_path');
        $expiresHours = $request->input('expires_hours', 1);

        $expiresAt = new \DateTime("+{$expiresHours} hours");
        $result = $this->firebaseService->getFileUrl($firebasePath, $expiresAt);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => [
                'public_url' => $result['public_url'] ?? null,
                'expires_at' => $expiresAt->format('Y-m-d H:i:s')
            ]
        ], $result['success'] ? 200 : 500);
    }
}
