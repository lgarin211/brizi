<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FirebaseController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    // Push Data Methods
    public function pushIndexData()
    {
        $data = DB::table('view_konten_argenta_flat')->get();
        foreach ($data as $k => $item) {
            foreach ($item as $key => $value) {
                $decoded = json_decode($value, true);
                $item->$key = $decoded;
            }
            $data[$k] = $item;
        }

        $result = $this->firebaseService->pushData('argenta/index', $data);

        return response()->json([
            'data' => $data,
            'firebase_result' => $result,
            'message' => $result['success'] ? 'Index data pushed to Firebase successfully' : 'Failed to push index data to Firebase',
            'status' => $result['success'] ? 200 : 500
        ], $result['success'] ? 200 : 500);
    }

    public function pushBeritaData()
    {
        $data = DB::table('argenta_beritas')->get();

        $result = $this->firebaseService->pushData('argenta/berita', $data);

        return response()->json([
            'data' => $data,
            'firebase_result' => $result,
            'message' => $result['success'] ? 'Berita data pushed to Firebase successfully' : 'Failed to push berita data to Firebase',
            'status' => $result['success'] ? 200 : 500
        ], $result['success'] ? 200 : 500);
    }

    public function pushKarirData()
    {
        $data = DB::table('argenta_karirs')->get();

        $result = $this->firebaseService->pushData('argenta/karir', $data);

        return response()->json([
            'data' => $data,
            'firebase_result' => $result,
            'message' => $result['success'] ? 'Karir data pushed to Firebase successfully' : 'Failed to push karir data to Firebase',
            'status' => $result['success'] ? 200 : 500
        ], $result['success'] ? 200 : 500);
    }

    public function pushLocationData()
    {
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

        $geoJsonData = [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];

        $result = $this->firebaseService->pushData('argenta/locations', $geoJsonData);

        return response()->json([
            'data' => $geoJsonData,
            'firebase_result' => $result,
            'message' => $result['success'] ? 'Location data pushed to Firebase successfully' : 'Failed to push location data to Firebase',
            'status' => $result['success'] ? 200 : 500
        ], $result['success'] ? 200 : 500);
    }

    public function pushAllData()
    {
        $results = [];

        // Push Index Data
        $indexData = DB::table('view_konten_argenta_flat')->get();
        foreach ($indexData as $k => $item) {
            foreach ($item as $key => $value) {
                $decoded = json_decode($value, true);
                $item->$key = $decoded;
            }
            $indexData[$k] = $item;
        }
        $results['index'] = $this->firebaseService->pushData('argenta/index', $indexData);

        // Push Berita Data
        $beritaData = DB::table('argenta_beritas')->get();
        $results['berita'] = $this->firebaseService->pushData('argenta/berita', $beritaData);

        // Push Karir Data
        $karirData = DB::table('argenta_karirs')->get();
        $results['karir'] = $this->firebaseService->pushData('argenta/karir', $karirData);

        // Push Location Data
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
        $geoJsonData = [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
        $results['locations'] = $this->firebaseService->pushData('argenta/locations', $geoJsonData);

        $allSuccess = collect($results)->every(function ($result) {
            return $result['success'];
        });

        return response()->json([
            'results' => $results,
            'message' => $allSuccess ? 'All data pushed to Firebase successfully' : 'Some data failed to push to Firebase',
            'status' => $allSuccess ? 200 : 500
        ], $allSuccess ? 200 : 500);
    }

    // Get Active Data Methods
    public function getActiveIndexData()
    {
        $result = $this->firebaseService->getActiveData('argenta/index');

        return response()->json([
            'firebase_result' => $result,
            'data' => $result['data'] ?? null,
            'message' => $result['message'],
            'status' => $result['success'] ? 200 : 500
        ], $result['success'] ? 200 : 500);
    }

    public function getActiveBeritaData()
    {
        $result = $this->firebaseService->getActiveData('argenta/berita');

        return response()->json([
            'firebase_result' => $result,
            'data' => $result['data'] ?? null,
            'message' => $result['message'],
            'status' => $result['success'] ? 200 : 500
        ], $result['success'] ? 200 : 500);
    }

    public function getActiveKarirData()
    {
        $result = $this->firebaseService->getActiveData('argenta/karir');

        return response()->json([
            'firebase_result' => $result,
            'data' => $result['data'] ?? null,
            'message' => $result['message'],
            'status' => $result['success'] ? 200 : 500
        ], $result['success'] ? 200 : 500);
    }

    public function getActiveLocationData()
    {
        $result = $this->firebaseService->getActiveData('argenta/locations');

        return response()->json([
            'firebase_result' => $result,
            'data' => $result['data'] ?? null,
            'message' => $result['message'],
            'status' => $result['success'] ? 200 : 500
        ], $result['success'] ? 200 : 500);
    }

    // Get History Data Methods
    public function getHistoryIndexData(Request $request)
    {
        $limit = $request->query('limit', 10);
        $result = $this->firebaseService->getHistoryData('argenta/index', $limit);

        return response()->json([
            'firebase_result' => $result,
            'data' => $result['data'] ?? null,
            'message' => $result['message'],
            'status' => $result['success'] ? 200 : 500
        ], $result['success'] ? 200 : 500);
    }

    public function getHistoryBeritaData(Request $request)
    {
        $limit = $request->query('limit', 10);
        $result = $this->firebaseService->getHistoryData('argenta/berita', $limit);

        return response()->json([
            'firebase_result' => $result,
            'data' => $result['data'] ?? null,
            'message' => $result['message'],
            'status' => $result['success'] ? 200 : 500
        ], $result['success'] ? 200 : 500);
    }

    public function getHistoryKarirData(Request $request)
    {
        $limit = $request->query('limit', 10);
        $result = $this->firebaseService->getHistoryData('argenta/karir', $limit);

        return response()->json([
            'firebase_result' => $result,
            'data' => $result['data'] ?? null,
            'message' => $result['message'],
            'status' => $result['success'] ? 200 : 500
        ], $result['success'] ? 200 : 500);
    }

    public function getHistoryLocationData(Request $request)
    {
        $limit = $request->query('limit', 10);
        $result = $this->firebaseService->getHistoryData('argenta/locations', $limit);

        return response()->json([
            'firebase_result' => $result,
            'data' => $result['data'] ?? null,
            'message' => $result['message'],
            'status' => $result['success'] ? 200 : 500
        ], $result['success'] ? 200 : 500);
    }
}
