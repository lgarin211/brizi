<?php

namespace App\Http\Controllers\PlazaFest;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class WelcomeController extends Controller
{
    public function index()
    {
        $datacomponent1 = [
            'title' => setting('landing.welcome_text'),
            'big_banner' =>url('/lgarin211/'.setting('landing.big_banner')),
            'big_welcome' => setting('landing.big_welcome')
        ];
        $datacomponent2 = DB::table('event')
            ->select('id', 'banner','title', 'description','created_at', 'updated_at')
            ->get();
        foreach ($datacomponent2 as $event) {
            $event->banner = url('/lgarin211/' . $event->banner);
        }


        $datacomponent3 = DB::table('sport_facility')
            ->select('id','image','description','created_at','updated_at')
            ->get();
        foreach ($datacomponent3 as $event) {
            $event->image = url('/lgarin211/' . $event->image);
        }
        $datacomponent4 = DB::table('tenants')
            ->select('id', 'img as image','location', 'created_at', 'updated_at')
            ->get();

        foreach ($datacomponent4 as $tenan) {
            $tenan->image = url('/lgarin211/' . $tenan->image);
        }

        $datacomponent5 = [
            'fb' => setting('landing.fb'),
            'ig' => setting('landing.ig')
        ];
        $response = [
            'component1' => $datacomponent1,
            'component2' => $datacomponent2,
            'component3' => $datacomponent3,
            'component4' => $datacomponent4,
            'component5' => $datacomponent5
        ];
        return response()->json(['message' => 'Data berhasil diambil', 'data' => $response], 200);
    }
}
