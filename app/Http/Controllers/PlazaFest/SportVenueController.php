<?php

namespace App\Http\Controllers\PlazaFest;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class SportVenueController extends Controller
{
    public function getListVenue()
    {
        $datacomponent1 = [
            'banner' =>setting('sport-venue.big_banner'),
            'title' =>setting('sport-venue.title_venue')
        ];
        $datacomponent2 = DB::table('sport_venue')
            ->select('id','image','description','created_at', 'updated_at')
            ->get();
        $datacomponent3 = DB::table('sport_promo')
            ->select('id','image','description','created_at','updated_at')
            ->get();
        $datacomponent4 = [
            'endpic' =>setting('sport-venue.endpic')
        ];
        $datacomponent5 =[
            'fb' => setting('sport-venue.fb'),
            'ig' => setting('sport-venue.ig')
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

