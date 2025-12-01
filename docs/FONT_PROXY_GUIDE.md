# Font Proxy Guide - Fixing CORS Errors

## Problem

When loading email content on external domains (like `unisonwavepromote.com`), fonts from CDNs like `cdn.intershop-cdn.com` are blocked by CORS policy:

```
Access to font at 'https://cdn.intershop-cdn.com/files/layout/src/fonts/source-sans-pro-v13-latin-600.woff2' 
from origin 'https://unisonwavepromote.com' has been blocked by CORS policy: 
No 'Access-Control-Allow-Origin' header is present on the requested resource.
```

## Solution

A font proxy has been created that:
- Fetches fonts from CDN servers
- Adds proper CORS headers to allow `unisonwavepromote.com`
- Serves fonts with correct content types
- Includes security checks to prevent SSRF attacks

## Usage

### In HTML/CSS (Email Templates or Landing Pages)

**Before (causing CORS error):**
```html
<link rel="preload" 
      href="https://cdn.intershop-cdn.com/files/layout/src/fonts/source-sans-pro-v13-latin-600.woff2" 
      as="font" 
      type="font/woff2" 
      crossorigin>
```

**After (using proxy):**
```html
<link rel="preload" 
      href="{{ app_url }}/fonts/proxy?url=https://cdn.intershop-cdn.com/files/layout/src/fonts/source-sans-pro-v13-latin-600.woff2" 
      as="font" 
      type="font/woff2" 
      crossorigin>
```

### In CSS @font-face

**Before:**
```css
@font-face {
    font-family: 'Source Sans Pro';
    src: url('https://cdn.intershop-cdn.com/files/layout/src/fonts/source-sans-pro-v13-latin-600.woff2') format('woff2');
}
```

**After:**
```css
@font-face {
    font-family: 'Source Sans Pro';
    src: url('{{ app_url }}/fonts/proxy?url=https://cdn.intershop-cdn.com/files/layout/src/fonts/source-sans-pro-v13-latin-600.woff2') format('woff2');
}
```

### In JavaScript

**Before:**
```javascript
const fontUrl = 'https://cdn.intershop-cdn.com/files/layout/src/fonts/source-sans-pro-v13-latin-600.woff2';
```

**After:**
```javascript
const fontUrl = '{{ app_url }}/fonts/proxy?url=' + encodeURIComponent('https://cdn.intershop-cdn.com/files/layout/src/fonts/source-sans-pro-v13-latin-600.woff2');
```

## Proxy Endpoint

**URL:** `GET /fonts/proxy?url=<encoded-font-url>`

**Example:**
```
https://d40f8f179fc6.ngrok-free.app/fonts/proxy?url=https://cdn.intershop-cdn.com/files/layout/src/fonts/source-sans-pro-v13-latin-600.woff2
```

## Supported Font Formats

- `.woff2` → `font/woff2`
- `.woff` → `font/woff`
- `.ttf` → `font/ttf`
- `.otf` → `font/otf`
- `.eot` → `application/vnd.ms-fontobject`

## Allowed CDN Domains

For security, only these domains are allowed:
- `cdn.intershop-cdn.com`
- `fonts.googleapis.com`
- `fonts.gstatic.com`

To add more domains, edit `app/Http/Controllers/FontProxyController.php` and add to the `$allowedHosts` array.

## CORS Headers

The proxy automatically adds these headers:
- `Access-Control-Allow-Origin: *` (allows all origins including `unisonwavepromote.com`)
- `Access-Control-Allow-Methods: GET, OPTIONS`
- `Access-Control-Allow-Headers: Content-Type`
- `Cross-Origin-Resource-Policy: cross-origin`
- `Cache-Control: public, max-age=31536000` (1 year caching)

## Security Features

1. **URL Validation** - Prevents SSRF attacks by validating URLs
2. **Domain Whitelist** - Only allows specific CDN domains
3. **Timeout Protection** - 10-second timeout to prevent hanging requests
4. **Error Logging** - Logs errors for debugging

## Testing

Test the proxy by accessing:
```
https://your-app-url/fonts/proxy?url=https://cdn.intershop-cdn.com/files/layout/src/fonts/source-sans-pro-v13-latin-600.woff2
```

You should receive the font file with proper CORS headers.

## Troubleshooting

### Font still not loading?

1. **Check URL encoding** - Make sure the font URL is properly URL-encoded
2. **Check allowed domains** - Verify the CDN domain is in the allowed list
3. **Check logs** - Look in `storage/logs/laravel.log` for proxy errors
4. **Test directly** - Try accessing the proxy URL directly in browser

### 403 Forbidden?

- The CDN domain might not be in the allowed list
- Add it to `$allowedHosts` in `FontProxyController.php`

### 500 Error?

- Check Laravel logs for detailed error messages
- Verify the CDN URL is accessible
- Check network connectivity

