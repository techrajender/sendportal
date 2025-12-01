<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FontProxyController extends Controller
{
    /**
     * Proxy font requests with CORS headers
     * Allows unisonwavepromote.com to load fonts from CDN
     *
     * @param Request $request
     * @return Response
     */
    public function proxy(Request $request)
    {
        $fontUrl = $request->get('url');
        
        if (!$fontUrl) {
            return response('Font URL is required', 400)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type');
        }
        
        // Validate URL to prevent SSRF attacks
        $parsedUrl = parse_url($fontUrl);
        if (!$parsedUrl || !isset($parsedUrl['host'])) {
            return response('Invalid URL', 400)
                ->header('Access-Control-Allow-Origin', '*');
        }
        
        // Only allow specific CDN domains
        $allowedHosts = [
            'cdn.intershop-cdn.com',
            'fonts.googleapis.com',
            'fonts.gstatic.com',
        ];
        
        if (!in_array($parsedUrl['host'], $allowedHosts)) {
            return response('Domain not allowed', 403)
                ->header('Access-Control-Allow-Origin', '*');
        }
        
        try {
            // Fetch the font file
            $response = Http::timeout(10)->get($fontUrl);
            
            if (!$response->successful()) {
                Log::warning('Font proxy failed to fetch font', [
                    'url' => $fontUrl,
                    'status' => $response->status(),
                ]);
                
                return response('Failed to fetch font', $response->status())
                    ->header('Access-Control-Allow-Origin', '*')
                    ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                    ->header('Access-Control-Allow-Headers', 'Content-Type');
            }
            
            // Determine content type based on file extension
            $contentType = $this->getContentType($fontUrl);
            
            // Return font with CORS headers
            return response($response->body(), 200)
                ->header('Content-Type', $contentType)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type')
                ->header('Access-Control-Max-Age', '86400')
                ->header('Cache-Control', 'public, max-age=31536000')
                ->header('Cross-Origin-Resource-Policy', 'cross-origin');
                
        } catch (\Exception $e) {
            Log::error('Font proxy error', [
                'url' => $fontUrl,
                'error' => $e->getMessage(),
            ]);
            
            return response('Error fetching font', 500)
                ->header('Access-Control-Allow-Origin', '*');
        }
    }
    
    /**
     * Handle CORS preflight requests
     *
     * @return Response
     */
    public function options()
    {
        return response('', 200)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type')
            ->header('Access-Control-Max-Age', '86400');
    }
    
    /**
     * Get content type based on font file extension
     *
     * @param string $url
     * @return string
     */
    protected function getContentType(string $url): string
    {
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        
        $contentTypes = [
            'woff2' => 'font/woff2',
            'woff' => 'font/woff',
            'ttf' => 'font/ttf',
            'otf' => 'font/otf',
            'eot' => 'application/vnd.ms-fontobject',
        ];
        
        return $contentTypes[$extension] ?? 'application/octet-stream';
    }
}

