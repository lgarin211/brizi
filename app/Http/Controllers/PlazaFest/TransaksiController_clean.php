<?php

namespace App\Http\Controllers\PlazaFest;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;

class TransaksiController extends Controller
{

    public function makeTransaction(Request $request)
    {
        $validated = $request->validate([
            'idsubfacility' => 'required|integer',
            'time_start' => 'required|array|min:1',
            'price' => 'required|numeric',
            'transactionpoin' => 'required',
            'date_start' => 'required|date',
        ]);
        $now = now();
        $insertData = [
            'idsubfacility'    => $validated['idsubfacility'],
            'time_start'       => json_encode($validated['time_start']),
            'status'           => 'pending',
            'timesuccess'      => null,
            'timeexpaired'     => null,
            'price'            => $validated['price'],
            'transactionpoin'  => $validated['transactionpoin'],
            'date_start'       => $validated['date_start'],
            'created_at'       => $now,
            'updated_at'       => $now,
            'deleted_at'       => null,
        ];
        $transactionId = DB::table('transaction')->insertGetId($insertData);
        return response()->json([
            'success' => true,
            'transaction_id' => $transactionId
        ]);
    }

    public function getTransaction(Request $request)
    {
        $id = $request->id;
        $transactions = DB::table('listsuccesstransction')
            ->where('t_id',$id)
            ->first();
        if (!$transactions) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }
        if($transactions->barcode == null){
            $convert=json_encode($transactions);
            $convert=Hash::make($convert);
            $transactions->barcode =  "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data='".$convert."'";
        }
        return response()->json($transactions);
    }
}
