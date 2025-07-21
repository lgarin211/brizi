<?php

namespace App\Http\Controllers\PlazaFest;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class WelcomeController extends Controller
{
    public function index()
    {
        $datacomponent0 = DB::table('sliders')->get();
        $allbanners=[];
        foreach ($datacomponent0 as $slide) {
            $slide->banner = url('/storage/' . $slide->banner);
            $allbanners[] = $slide->banner;
        }

        $datacomponent1 = [
            'title' => setting('landing.welcome_text'),
            'big_banner' => $allbanners,
            'big_welcome' => setting('landing.big_welcome')
        ];
        $datacomponent2 = DB::table('event')
            ->select('id', 'banner','title', 'description','created_at', 'updated_at')
            ->get();
        foreach ($datacomponent2 as $event) {
            $event->banner = url('/storage/' . $event->banner);
        }


        $datacomponent3 = DB::table('sport_facility')
            ->select('id','image','description','created_at','updated_at')
            ->get();
        foreach ($datacomponent3 as $event) {
            $event->image = url('/storage/' . $event->image);
        }
        $datacomponent4 = DB::table('tenants')
            ->select('id', 'img','location', 'created_at', 'updated_at')
            ->get();

        foreach ($datacomponent4 as $tenan) {
            $tenan->img = url('/storage/' . $tenan->img);
        }

        $datacomponent5 = [
            'fb' => setting('landing.fb'),
            'ig' => setting('landing.ig')
        ];

        $datacomponent6 = [
            setting('lantai.ug_map'),
            setting('lantai.gf_map')
        ];

        foreach ($datacomponent6 as $key => $map) {
            if ($map) {
                $datacomponent6[$key] = url('/storage/' . $map);
            }
        }



        $response = [
            'component0' => $datacomponent0,
            'component1' => $datacomponent1,
            'component2' => $datacomponent2,
            'component3' => $datacomponent3,
            'component4' => $datacomponent4,
            'component5' => $datacomponent5,
            'component6' => $datacomponent6
        ];
        return response()->json(['message' => 'Data berhasil diambil', 'data' => $response], 200);
    }
}
