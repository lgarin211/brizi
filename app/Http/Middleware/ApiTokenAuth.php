<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Authorization token required',
                'error' => 'No Bearer token provided'
            ], 401);
        }

        try {
            // Find user by API token
            $user = DB::table('users')
                ->where('api_token', $token)
                ->where('is_active', true)
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired token',
                    'error' => 'Authentication failed'
                ], 401);
            }

            // Add user to request for use in controllers
            $request->attributes->add(['authenticated_user' => $user]);

            return $next($request);

        } catch (\Exception $e) {
            Log::error('API Token Authentication Error: ' . $e->getMessage(), [
                'token' => substr($token, 0, 10) . '...',
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Authentication error',
                'error' => 'Failed to validate token'
            ], 500);
        }
    }
}
