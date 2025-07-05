<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Contract\Database;
use Kreait\Firebase\Contract\Storage;
use Exception;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    protected $database;
    protected $storage;

    public function __construct()
    {
        try {
            $databaseUrl = config('firebase.database_url');
            $credentialsPath = config('firebase.credentials.file');

            if (empty($databaseUrl)) {
                throw new Exception('Firebase database URL is not configured');
            }

            $factory = (new Factory)->withDatabaseUri($databaseUrl);

            // Check if credentials file exists and is valid
            if (!empty($credentialsPath)) {
                $fullCredentialsPath = base_path($credentialsPath);

                if (file_exists($fullCredentialsPath)) {
                    $factory = $factory->withServiceAccount($fullCredentialsPath);
                    Log::info('Firebase initialized with service account: ' . $fullCredentialsPath);
                } else {
                    Log::warning('Firebase credentials file not found: ' . $fullCredentialsPath);
                    // Try to initialize without service account (might work in some cases)
                }
            } else {
                Log::warning('Firebase credentials file path not configured');
            }

            $this->database = $factory->createDatabase();
            $this->storage = $factory->createStorage();

            Log::info('Firebase services initialized successfully');

        } catch (Exception $e) {
            Log::error('Firebase initialization error: ' . $e->getMessage(), [
                'database_url' => config('firebase.database_url'),
                'credentials_path' => config('firebase.credentials.file'),
                'full_credentials_path' => base_path(config('firebase.credentials.file')),
                'credentials_exists' => file_exists(base_path(config('firebase.credentials.file')))
            ]);
            $this->database = null;
            $this->storage = null;
        }
    }    public function isInitialized()
    {
        return $this->database !== null && $this->storage !== null;
    }

    public function getDiagnosticInfo()
    {
        return [
            'database_initialized' => $this->database !== null,
            'storage_initialized' => $this->storage !== null,
            'database_url' => config('firebase.database_url'),
            'credentials_path' => config('firebase.credentials.file'),
            'full_credentials_path' => base_path(config('firebase.credentials.file')),
            'credentials_exists' => file_exists(base_path(config('firebase.credentials.file'))),
            'php_version' => PHP_VERSION,
            'extensions' => [
                'curl' => extension_loaded('curl'),
                'json' => extension_loaded('json'),
                'openssl' => extension_loaded('openssl')
            ]
        ];
    }

    public function pushData($path, $data)
    {
        try {
            if (!$this->database) {
                throw new Exception('Firebase database not initialized');
            }

            $timestamp = now()->format('Y-m-d_H-i-s');

            // Push ke active (data terbaru)
            $activeReference = $this->database->getReference($path . '/active');
            $activeReference->set($data);

            // Push ke history dengan timestamp
            $historyReference = $this->database->getReference($path . '/history/' . $timestamp);
            $historyReference->set($data);

            return [
                'success' => true,
                'message' => 'Data pushed to Firebase successfully (active & history)',
                'timestamp' => $timestamp
            ];
        } catch (Exception $e) {
            Log::error('Firebase push error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to push data to Firebase: ' . $e->getMessage()
            ];
        }
    }

    public function updateData($path, $data)
    {
        try {
            if (!$this->database) {
                throw new Exception('Firebase database not initialized');
            }

            $reference = $this->database->getReference($path);
            $reference->update($data);

            return [
                'success' => true,
                'message' => 'Data updated in Firebase successfully'
            ];
        } catch (Exception $e) {
            Log::error('Firebase update error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update data in Firebase: ' . $e->getMessage()
            ];
        }
    }

    public function getHistoryData($path, $limit = 10)
    {
        try {
            if (!$this->database) {
                throw new Exception('Firebase database not initialized');
            }

            $reference = $this->database->getReference($path . '/history');
            $snapshot = $reference->orderByKey()->limitToLast($limit)->getSnapshot();

            return [
                'success' => true,
                'data' => $snapshot->getValue(),
                'message' => 'History data retrieved successfully'
            ];
        } catch (Exception $e) {
            Log::error('Firebase get history error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get history data from Firebase: ' . $e->getMessage()
            ];
        }
    }

    public function getActiveData($path)
    {
        try {
            if (!$this->database) {
                throw new Exception('Firebase database not initialized');
            }

            $reference = $this->database->getReference($path . '/active');
            $snapshot = $reference->getSnapshot();

            return [
                'success' => true,
                'data' => $snapshot->getValue(),
                'message' => 'Active data retrieved successfully'
            ];
        } catch (Exception $e) {
            Log::error('Firebase get active error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get active data from Firebase: ' . $e->getMessage()
            ];
        }
    }

    // Firebase Storage Methods
    public function uploadFile($localPath, $firebasePath, $options = [])
    {
        try {
            if (!$this->storage) {
                throw new Exception('Firebase storage not initialized');
            }

            $bucket = $this->storage->getBucket();
            $file = fopen($localPath, 'r');

            $object = $bucket->upload($file, [
                'name' => $firebasePath,
                'metadata' => $options
            ]);

            // Get public URL
            $publicUrl = $object->signedUrl(new \DateTime('+100 years'));

            return [
                'success' => true,
                'message' => 'File uploaded to Firebase Storage successfully',
                'firebase_path' => $firebasePath,
                'public_url' => $publicUrl,
                'object' => $object
            ];
        } catch (Exception $e) {
            Log::error('Firebase storage upload error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to upload file to Firebase Storage: ' . $e->getMessage()
            ];
        }
    }

    public function uploadFromString($content, $firebasePath, $options = [])
    {
        try {
            if (!$this->storage) {
                throw new Exception('Firebase storage not initialized');
            }

            $bucket = $this->storage->getBucket();

            $object = $bucket->upload($content, [
                'name' => $firebasePath,
                'metadata' => $options
            ]);

            // Get public URL
            $publicUrl = $object->signedUrl(new \DateTime('+100 years'));

            return [
                'success' => true,
                'message' => 'Content uploaded to Firebase Storage successfully',
                'firebase_path' => $firebasePath,
                'public_url' => $publicUrl,
                'object' => $object
            ];
        } catch (Exception $e) {
            Log::error('Firebase storage upload error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to upload content to Firebase Storage: ' . $e->getMessage()
            ];
        }
    }

    public function deleteFile($firebasePath)
    {
        try {
            if (!$this->storage) {
                throw new Exception('Firebase storage not initialized');
            }

            $bucket = $this->storage->getBucket();
            $object = $bucket->object($firebasePath);
            $object->delete();

            return [
                'success' => true,
                'message' => 'File deleted from Firebase Storage successfully'
            ];
        } catch (Exception $e) {
            Log::error('Firebase storage delete error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete file from Firebase Storage: ' . $e->getMessage()
            ];
        }
    }

    public function getFileUrl($firebasePath, $expiresAt = null)
    {
        try {
            if (!$this->storage) {
                throw new Exception('Firebase storage not initialized');
            }

            $bucket = $this->storage->getBucket();
            $object = $bucket->object($firebasePath);

            $expiresAt = $expiresAt ?: new \DateTime('+1 hour');
            $publicUrl = $object->signedUrl($expiresAt);

            return [
                'success' => true,
                'public_url' => $publicUrl,
                'message' => 'File URL generated successfully'
            ];
        } catch (Exception $e) {
            Log::error('Firebase storage get URL error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get file URL from Firebase Storage: ' . $e->getMessage()
            ];
        }
    }

    public function isConnected()
    {
        return $this->database !== null && $this->storage !== null;
    }
}
