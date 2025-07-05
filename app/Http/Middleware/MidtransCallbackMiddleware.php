<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MidtransCallbackMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */    public function handle(Request $request, Closure $next): Response
    {
        // Store raw content for later use
        $rawContent = $request->getContent();
        $contentType = $request->header('Content-Type', '');

        // Add raw content to request attributes for easy access
        $request->attributes->set('midtrans_raw_body', $rawContent);

        // Log incoming request for debugging
        \Log::info('Midtrans Callback Middleware Processing', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'content_type' => $contentType,
            'raw_content_length' => strlen($rawContent),
            'has_input' => !empty($request->all()),
            'input_data' => $request->all()
        ]);

        // Parse JSON body if content type indicates JSON
        if ((str_contains($contentType, 'application/json') || str_contains($contentType, 'text/json')) && !empty($rawContent)) {
            $jsonData = json_decode($rawContent, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                // Merge JSON data with request
                $request->merge($jsonData);
                \Log::info('JSON data merged into request', ['json_data' => $jsonData]);
            }
        }

        // Parse form data from raw body if content type indicates form data or if no data in request
        if ((str_contains($contentType, 'application/x-www-form-urlencoded') || empty($request->all())) && !empty($rawContent)) {
            parse_str($rawContent, $formData);
            if (is_array($formData) && !empty($formData)) {
                $request->merge($formData);
                \Log::info('Form data merged into request', ['form_data' => $formData]);
            }
        }

        // Handle query string in raw body (sometimes Midtrans sends data this way)
        if (!empty($rawContent) && empty($request->all())) {
            // Check if raw body looks like a query string
            if (strpos($rawContent, '=') !== false && strpos($rawContent, '&') !== false) {
                parse_str($rawContent, $queryData);
                if (is_array($queryData) && !empty($queryData)) {
                    $request->merge($queryData);
                    \Log::info('Query string data merged into request', ['query_data' => $queryData]);
                }
            }
        }

        return $next($request);
    }
}
