<?php

namespace App\Http\Controllers\PlazaFest;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;

class FasilitasController extends Controller
{
    public function getListFasility(Request $request)
    {
        $facilities = DB::table('facility')->get();
        // dd($facilities);
        foreach ($facilities as $d=>$facility) {
            $facilities[$d]->benner=json_decode($facility->benner, true);
            foreach ($facilities[$d]->benner as $key => $value) {
                if (is_string($value)) {
                    $facilities[$d]->benner[$key] = asset('storage/' . $value);
                }
            }
        }
        return response()->json($facilities);
    }

    public function getListFasilityById(Request $request, $id)
    {
        $facilities = DB::table('listfasilitas')->where('idfacility', $id)->get();
        foreach ($facilities as $d => $facility) {
            $facility->var = DB::table('sub_facility')
                ->where('id', $facility->id)
                ->first();
            if ($facility->var) {
                // Parse JSON fields if present
                if (isset($facility->var->banner)) {
                    $facility->var->banner = json_decode($facility->var->banner, true);
                    // Convert banner paths to asset URLs if needed
                    if (is_array($facility->var->banner)) {
                        foreach ($facility->var->banner as $k => $v) {
                            if (is_string($v)) {
                                $facility->var->banner[$k] = asset('storage/' . $v);
                            }
                        }
                    }
                }
                if (isset($facility->var->additional)) {
                    $facility->var->additional = json_decode($facility->var->additional, true);
                }
                if (isset($facility->var->additonalday)) {
                    $facility->var->additonalday = json_decode($facility->var->additonalday, true);
                }
                if (isset($facility->var->additonaltime)) {
                    $facility->var->additonaltime = json_decode($facility->var->additonaltime, true);
                    // cek jamnya lalu urutkan
                    usort($facility->var->additonaltime, function ($a, $b) {
                        return strtotime($a) - strtotime($b);
                    });
                }
            }
        }
        return response()->json($facilities);
    }

    public function getListFasilityByIdSid(Request $request, $id, $sid)
    {
        if (!is_numeric($id) || !is_numeric($sid)) {
            return response()->json(['error' => 'Invalid parameter'], 400);
        }
        $facility = DB::table('listfasilitas')
            ->where(['idfacility' => $id, 'id' => $sid])
            ->first();
        if (!$facility) {
            return response()->json(['error' => 'Facility not found'], 404);
        }
        $prelix=[
            'idsubfacility'=> $sid,
            'date_start' =>date('Y-m-d')
        ];
        if(!empty($request->sethel)){
            $prelix['date_start'] = $request->sethel;
        }
        $usedTimes = DB::table('transaction')
            ->where($prelix)
            ->pluck('time_start')
            ->flatMap(function ($json) {
                $arr = json_decode($json, true);
                if (!is_array($arr)) return [];
                return collect($arr)->map(function ($t) {
                    return date('H:i', strtotime($t));
                });
            })
            ->unique()
            ->values();
        $activeTimes = collect(range(1, 24))
            ->map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT) . ':00');
        $availableTimes = $activeTimes->diff($usedTimes)->values();
        $setrespontime=[];
        foreach ($availableTimes as $time) {
            if (strtotime($time) < strtotime('10:00')) {
                $label = 'Morning';
            } elseif (strtotime($time) < strtotime('14:00')) {
                $label = 'Soon';
            } elseif (strtotime($time) < strtotime('18:00')) {
                $label = 'Afternoon';
            } else {
                $label = 'Evening';
            }
            $setrespontime[] = [
                'time' => $time,
                'label' => $label,
                'hour' => (int)explode(':', $time)[0]
            ];
        }
        $response = [
            'facility' => $facility,
            'available_times' => $setrespontime
        ];
        return response()->json($response);
    }

    public function getArtikelList(Request $request)
    {
        $filter = $request->s?$request->s:'';
        $data = DB::table('posts')
            ->select('id', 'title', 'body','image', 'created_at')
            ->where('status', $filter)
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($data);
    }
}
