<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ArgentaController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function getIndexData()
    {
        try {
            // Try to get data from Firebase first
            $firebaseResult = $this->firebaseService->getActiveData('argenta/index');

            // Debug information
            Log::info('Firebase result for index data:', [
                'success' => $firebaseResult['success'] ?? false,
                'has_data' => !empty($firebaseResult['data'] ?? null),
                'data_count' => is_array($firebaseResult['data'] ?? null) ? count($firebaseResult['data']) : 0,
                'message' => $firebaseResult['message'] ?? 'no message'
            ]);

            if ($firebaseResult['success'] && !empty($firebaseResult['data'])) {
                return response()->json([
                    'data' => $firebaseResult['data'],
                    'source' => 'firebase',
                    'message' => 'Data retrieved from Firebase',
                    'status' => 200,
                    'by'=>'firebase'
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Firebase retrieval failed for index data: ' . $e->getMessage());
        }

        // Fallback to database if Firebase fails
        $data = DB::table('view_konten_argenta_flat')->get();
        foreach ($data as $k => $item) {
            foreach ($item as $key => $value) {
                $decoded = json_decode($value, true);
                $item->$key = $decoded;
            }
            $data[$k] = $item;
        }

        return response()->json([
            'data' => $data,
            'source' => 'database',
            'message' => 'Data retrieved from database (Firebase fallback)',
            'status' => 200,
            'by'=>'database'
        ]);
    }

    public function getBeritaData()
    {
        try {
            // Try to get data from Firebase first
            $firebaseResult = $this->firebaseService->getActiveData('argenta/berita');

            if ($firebaseResult['success'] && !empty($firebaseResult['data'])) {
                return response()->json([
                    'data' => $firebaseResult['data'],
                    'source' => 'firebase',
                    'message' => 'Data retrieved from Firebase',
                    'status' => 200,
                    'by'=>'firebase'
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Firebase retrieval failed for berita data: ' . $e->getMessage());
        }

        // Fallback to database if Firebase fails
        $data = DB::table('argenta_beritas')->get();
        return response()->json([
            'data' => $data,
            'source' => 'database',
            'message' => 'Data retrieved from database (Firebase fallback)',
            'status' => 200,
            'by'=>'database'
        ]);
    }

    public function getKarirData()
    {
        try {
            // Try to get data from Firebase first
            $firebaseResult = $this->firebaseService->getActiveData('argenta/karir');

            if ($firebaseResult['success'] && !empty($firebaseResult['data'])) {
                return response()->json([
                    'data' => $firebaseResult['data'],
                    'source' => 'firebase',
                    'message' => 'Data retrieved from Firebase',
                    'status' => 200,
                    'by'=>'firebase'
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Firebase retrieval failed for karir data: ' . $e->getMessage());
        }

        // Fallback to database if Firebase fails
        $data = DB::table('argenta_karirs')->get();
        return response()->json([
            'data' => $data,
            'source' => 'database',
            'message' => 'Data retrieved from database (Firebase fallback)',
            'status' => 200,
            'by'=>'database'
        ]);
    }

    public function getMapData()
    {
        try {
            // Try to get data from Firebase first
            $firebaseResult = $this->firebaseService->getActiveData('argenta/locations');

            if ($firebaseResult['success'] && !empty($firebaseResult['data'])) {
                return response()->json($firebaseResult['data']);
            }
        } catch (\Exception $e) {
            Log::warning('Firebase retrieval failed for map data: ' . $e->getMessage());
        }

        // Fallback to database if Firebase fails
        $locations = DB::table('argenta_locations')->get();
        $features = $locations->map(function ($location) {
            return [
                'type' => 'Feature',
                'properties' => [
                    'namaCabang'   => $location->nama_cabang,
                    'namaPegawai'  => $location->nama_pegawai,
                    'nomorTelepon' => $location->nomor_telepon,
                    'jenisTitik'   => $location->jenis_titik,
                    'warna'        => $location->warna,
                    'timestamp'    => $location->waktu_input,
                ],
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [
                        (float) $location->longitude,
                        (float) $location->latitude,
                    ],
                ],
                'id' => (int) $location->id,
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
            'source' => 'database'
        ]);
    }

    public function storeBranchLocation(Request $request)
    {
        $validated = $request->validate([
            'id'            => 'required|numeric|unique:argenta_locations,id',
            'pointType'     => 'required|in:service-center,staf-teknis,cabang-penjualan',
            'latitude'      => 'required|numeric',
            'longitude'     => 'required|numeric',
            'branchName'    => 'required|string|max:255',
            'staffName'     => 'required|string|max:255',
            'phoneNumber'   => 'required|string|max:20',
            'timestamp'     => 'required|date',
            'color'         => 'required|string|max:7',
        ]);

        DB::table('argenta_locations')->insert([
            'id'            => $validated['id'],
            'jenis_titik'    => $validated['pointType'],
            'latitude'      => $validated['latitude'],
            'longitude'     => $validated['longitude'],
            'nama_cabang'   => $validated['branchName'],
            'nama_pegawai'    => $validated['staffName'],
            'nomor_telepon'  => $validated['phoneNumber'],
            'waktu_input'    => $validated['timestamp'],
            'warna'         => $validated['color'],
            'status'        => 'active',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return response()->json([
            'message' => 'Location data saved successfully',
            'data' => $validated
        ], 201);
    }

    public function testFirebaseConnection()
    {
        try {
            // Get diagnostic info
            $diagnosticInfo = $this->firebaseService->getDiagnosticInfo();

            // Test Firebase connection
            $testResult = $this->firebaseService->getActiveData('test');

            return response()->json([
                'firebase_service_available' => $this->firebaseService !== null,
                'firebase_initialized' => $this->firebaseService->isInitialized(),
                'test_result' => $testResult,
                'diagnostic_info' => $diagnosticInfo
            ]);
        } catch (\Exception $e) {
            $diagnosticInfo = $this->firebaseService->getDiagnosticInfo();
            return response()->json([
                'error' => $e->getMessage(),
                'firebase_available' => false,
                'diagnostic_info' => $diagnosticInfo
            ], 500);
        }
    }
}
