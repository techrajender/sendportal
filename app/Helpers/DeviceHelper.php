<?php

namespace App\Helpers;

class DeviceHelper
{
    /**
     * Detect device type from user agent
     *
     * @param string|null $userAgent
     * @return string
     */
    public static function detectDeviceType(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown';
        }

        $userAgent = strtolower($userAgent);

        // Mobile devices
        if (preg_match('/mobile|android|iphone|ipod|blackberry|iemobile|opera mini/i', $userAgent)) {
            return 'Mobile';
        }

        // Tablet devices
        if (preg_match('/tablet|ipad|playbook|silk/i', $userAgent)) {
            return 'Tablet';
        }

        // Laptop/Desktop (default for non-mobile devices)
        if (preg_match('/windows|macintosh|linux|ubuntu/i', $userAgent)) {
            return 'Desktop';
        }

        // Default to Desktop if we can't determine
        return 'Desktop';
    }

    /**
     * Get browser name from user agent
     *
     * @param string|null $userAgent
     * @return string
     */
    public static function detectBrowser(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown';
        }

        $userAgent = strtolower($userAgent);

        if (strpos($userAgent, 'chrome') !== false && strpos($userAgent, 'edg') === false) {
            return 'Chrome';
        }
        if (strpos($userAgent, 'safari') !== false && strpos($userAgent, 'chrome') === false) {
            return 'Safari';
        }
        if (strpos($userAgent, 'firefox') !== false) {
            return 'Firefox';
        }
        if (strpos($userAgent, 'edg') !== false) {
            return 'Edge';
        }
        if (strpos($userAgent, 'opera') !== false || strpos($userAgent, 'opr') !== false) {
            return 'Opera';
        }
        if (strpos($userAgent, 'msie') !== false || strpos($userAgent, 'trident') !== false) {
            return 'Internet Explorer';
        }

        return 'Unknown';
    }

    /**
     * Get operating system from user agent
     *
     * @param string|null $userAgent
     * @return string
     */
    public static function detectOS(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown';
        }

        $userAgent = strtolower($userAgent);

        if (strpos($userAgent, 'windows') !== false) {
            if (strpos($userAgent, 'windows nt 10') !== false) {
                return 'Windows 10/11';
            }
            if (strpos($userAgent, 'windows nt 6.3') !== false) {
                return 'Windows 8.1';
            }
            if (strpos($userAgent, 'windows nt 6.2') !== false) {
                return 'Windows 8';
            }
            if (strpos($userAgent, 'windows nt 6.1') !== false) {
                return 'Windows 7';
            }
            return 'Windows';
        }
        if (strpos($userAgent, 'mac os x') !== false || strpos($userAgent, 'macintosh') !== false) {
            return 'macOS';
        }
        if (strpos($userAgent, 'linux') !== false) {
            return 'Linux';
        }
        if (strpos($userAgent, 'android') !== false) {
            return 'Android';
        }
        if (strpos($userAgent, 'iphone') !== false || strpos($userAgent, 'ipad') !== false) {
            return 'iOS';
        }

        return 'Unknown';
    }
}

