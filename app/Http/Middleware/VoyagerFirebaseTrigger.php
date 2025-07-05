<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class VoyagerFirebaseTrigger
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // if (in_array($request->method(), ['POST', 'PUT'])) {
        //     try {
        //         // Trigger async push to /api/firebase/push/all
        //         $client = new Client(['timeout' => 2, 'http_errors' => false]);
        //         $url = url('/api/firebase/push/all');
        //         $client->post($url, [
        //             'headers' => [
        //                 'Accept' => 'application/json',
        //                 'X-Requested-With' => 'VoyagerFirebaseTrigger',
        //             ],
        //         ]);
        //         Log::info('Triggered Firebase push after admin POST/PUT', ['url' => $url]);
        //     } catch (\Exception $e) {
        //         Log::error('Failed to trigger Firebase push: ' . $e->getMessage());
        //     }
        // }

        return $response;
    }
}
