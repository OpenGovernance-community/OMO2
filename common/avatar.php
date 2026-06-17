<?php

if (!function_exists('commonAvatarClampChannel')) {
    function commonAvatarClampChannel($value)
    {
        $value = (int)round((float)$value);
        if ($value < 0) {
            return 0;
        }

        if ($value > 255) {
            return 255;
        }

        return $value;
    }
}

if (!function_exists('commonAvatarHexFromRgb')) {
    function commonAvatarHexFromRgb($red, $green, $blue)
    {
        return sprintf(
            '#%02x%02x%02x',
            commonAvatarClampChannel($red),
            commonAvatarClampChannel($green),
            commonAvatarClampChannel($blue)
        );
    }
}

if (!function_exists('commonAvatarHexFromHsl')) {
    function commonAvatarHexFromHsl($hue, $saturation, $lightness)
    {
        $hue = fmod((float)$hue, 360.0);
        if ($hue < 0) {
            $hue += 360.0;
        }

        $saturation = max(0.0, min(100.0, (float)$saturation)) / 100.0;
        $lightness = max(0.0, min(100.0, (float)$lightness)) / 100.0;

        $chroma = (1.0 - abs((2.0 * $lightness) - 1.0)) * $saturation;
        $segment = $hue / 60.0;
        $secondary = $chroma * (1.0 - abs(fmod($segment, 2.0) - 1.0));
        $match = $lightness - ($chroma / 2.0);

        $red = 0.0;
        $green = 0.0;
        $blue = 0.0;

        if ($segment < 1.0) {
            $red = $chroma;
            $green = $secondary;
        } elseif ($segment < 2.0) {
            $red = $secondary;
            $green = $chroma;
        } elseif ($segment < 3.0) {
            $green = $chroma;
            $blue = $secondary;
        } elseif ($segment < 4.0) {
            $green = $secondary;
            $blue = $chroma;
        } elseif ($segment < 5.0) {
            $red = $secondary;
            $blue = $chroma;
        } else {
            $red = $chroma;
            $blue = $secondary;
        }

        return commonAvatarHexFromRgb(
            ($red + $match) * 255.0,
            ($green + $match) * 255.0,
            ($blue + $match) * 255.0
        );
    }
}

if (!function_exists('commonAvatarHexToRgb')) {
    function commonAvatarHexToRgb($hexColor)
    {
        $hexColor = trim((string)$hexColor);
        if ($hexColor === '') {
            return array(17, 24, 39);
        }

        $normalized = ltrim($hexColor, '#');
        if (strlen($normalized) === 3) {
            $normalized = $normalized[0] . $normalized[0]
                . $normalized[1] . $normalized[1]
                . $normalized[2] . $normalized[2];
        }

        if (!preg_match('/^[0-9a-fA-F]{6}$/', $normalized)) {
            return array(17, 24, 39);
        }

        return array(
            hexdec(substr($normalized, 0, 2)),
            hexdec(substr($normalized, 2, 2)),
            hexdec(substr($normalized, 4, 2)),
        );
    }
}

if (!function_exists('commonAvatarResolveTextColor')) {
    function commonAvatarResolveTextColor($backgroundColor)
    {
        list($red, $green, $blue) = commonAvatarHexToRgb($backgroundColor);
        $luminance = (($red * 299) + ($green * 587) + ($blue * 114)) / 1000;

        return $luminance >= 150 ? '#111827' : '#ffffff';
    }
}

if (!function_exists('commonBuildAvatarPalette')) {
    function commonBuildAvatarPalette($initials, $entityId = 0, $seedLabel = '')
    {
        $normalizedInitials = trim((string)$initials);
        if ($normalizedInitials === '') {
            $normalizedInitials = 'P';
        }

        $seed = strtoupper($normalizedInitials) . '|' . (int)$entityId . '|' . trim((string)$seedLabel);
        $hash = md5($seed);

        $hue = hexdec(substr($hash, 0, 4)) % 360;
        $saturation = 58 + (hexdec(substr($hash, 4, 2)) % 18);
        $lightness = 38 + (hexdec(substr($hash, 6, 2)) % 16);

        $background = commonAvatarHexFromHsl($hue, $saturation, $lightness);

        return array(
            'background' => $background,
            'foreground' => commonAvatarResolveTextColor($background),
        );
    }
}

if (!function_exists('commonBuildAvatarSeedLabel')) {
    function commonBuildAvatarSeedLabel($displayName = '', $email = '')
    {
        return trim((string)$displayName) . '|' . trim((string)$email);
    }
}
