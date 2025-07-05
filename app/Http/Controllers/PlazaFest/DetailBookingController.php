<?php

namespace App\Http\Controllers\PlazaFest;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class DetailBookingController extends Controller
{
     public function getListDetailBooking()
    {
        $datacomponent1 = DB::table('img_dbooking')
            ->select('id','image','created_at','updated_at')
            ->get();
        $datacomponent2 = [
            'description' => setting('detailbooking.dbook')
        ];
        $datacomponent3 = DB::table('promo_dbooking')
            ->select('id','image','text','created_at','updated_at')
            ->get();
        $datacomponent4 = DB::table('testimoni')
            ->select('id','user','description','created_at','updated_at')
            ->get();
        $datacomponent5 =[
            'fb' => setting('detailbooking.fb'),
            'ig' => setting('detailbooking.ig'),
        ];
        $response = [
            'component1' => $datacomponent1,
            'component2' => $datacomponent2,
            'component3' => $datacomponent3,
            'component4' => $datacomponent4,
            'component5' => $datacomponent5,
        ];

        return response()->json(['message' => 'Data berhasil diambil', 'data' => $response], 200);
    }
}
