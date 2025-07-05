<?php

namespace App\Http\Controllers\PlazaFest;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class MenuRestoController extends Controller
{
    public function getListMenu(Request $request,$id)
    {
        $datacomponent1 =DB::table('tenan')
                        ->where('id', $id)
                        ->first();
        $datacomponent1->img = asset('storage/' . $datacomponent1->img);

        $datacomponent2 = DB::table('list_menu')
                ->where('id_tenant', $id)
                ->get();
        foreach ($datacomponent2 as $menu) {
            $menu->image = asset('storage/' . $menu->image);
        }

        $response = [
                'component1' => $datacomponent1,
                'component2' => $datacomponent2,
        ];

        return response()->json(['message' => 'Data berhasil diambil', 'data' => $response], 200);
    }
}
