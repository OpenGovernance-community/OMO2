<?php

if (!function_exists('commonMailEscape')) {
    function commonMailEscape($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('commonMailTextToHtml')) {
    function commonMailTextToHtml($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        $blocks = preg_split('/\r\n\r\n|\r\r|\n\n/', $value);
        $blocks = is_array($blocks) ? $blocks : [];
        $html = [];

        foreach ($blocks as $block) {
            $block = trim((string)$block);
            if ($block === '') {
                continue;
            }

            $html[] = '<p style="margin:0 0 14px; color:#475569; line-height:1.7;">'
                . nl2br(commonMailEscape($block), false)
                . '</p>';
        }

        return implode('', $html);
    }
}

if (!function_exists('commonRenderMailLayout')) {
    function commonRenderMailLayout(array $options)
    {
        $brandName = trim((string)($options['brand_name'] ?? ''));
        $brandColor = trim((string)($options['brand_color'] ?? ''));
        $logoUrl = trim((string)($options['logo_url'] ?? ''));
        $bannerUrl = trim((string)($options['banner_url'] ?? ''));
        $heading = trim((string)($options['heading'] ?? ''));
        $introHtml = trim((string)($options['intro_html'] ?? ''));
        $bodyHtml = trim((string)($options['body_html'] ?? ''));
        $detailsHtml = trim((string)($options['details_html'] ?? ''));
        $buttonLabel = trim((string)($options['button_label'] ?? ''));
        $buttonUrl = trim((string)($options['button_url'] ?? ''));
        $footerHtml = trim((string)($options['footer_html'] ?? ''));

        if ($brandColor === '' || stripos($brandColor, 'var(') !== false) {
            $brandColor = '#004663';
        }

        return "
<html>
<body style='margin:0; font-family:Arial, sans-serif; background:#f5f7fb;'>
<table width='100%' cellpadding='0' cellspacing='0'>
<tr>
<td align='center' style='padding:24px 12px;'>
<table width='640' cellpadding='0' cellspacing='0' style='max-width:640px; width:100%; background:white; border-radius:var(--radius-md); overflow:hidden; box-shadow:0 16px 40px rgba(15, 23, 42, 0.08);'>
<tr>
<td style='background:" . commonMailEscape($brandColor) . "; text-align:center; padding:32px 24px; position:relative;'>
    " . ($bannerUrl !== '' ? "<div style='background:url(" . commonMailEscape($bannerUrl) . ") center/cover; opacity:0.22; position:absolute; inset:0;'></div>" : "") . "
    <div style='position:relative;'>
        " . ($logoUrl !== '' ? "
        <div style='width:84px;height:84px;border-radius:var(--radius-md);background:white;margin:0 auto 14px;padding:8px;box-sizing:border-box;'>
            <img src='" . commonMailEscape($logoUrl) . "' alt='' style='width:100%;height:100%;object-fit:cover;border-radius:var(--radius-md);display:block;'>
        </div>
        " : "") . "
        " . ($brandName !== '' ? "<div style='color:white; font-size:13px; letter-spacing:0.08em; text-transform:uppercase; opacity:0.88; margin-bottom:10px;'>" . commonMailEscape($brandName) . "</div>" : "") . "
        " . ($heading !== '' ? "<h2 style='color:white; margin:0; font-size:28px; line-height:1.2;'>" . commonMailEscape($heading) . "</h2>" : "") . "
    </div>
</td>
</tr>
<tr>
<td style='padding:32px 32px 28px;'>
    " . $introHtml . "
    " . $bodyHtml . "
    " . ($detailsHtml !== '' ? "<div style='margin:18px 0 0; padding:18px 20px; border:1px solid #dbe3ef; border-radius:var(--radius-md); background:#f8fafc;'>" . $detailsHtml . "</div>" : "") . "
    " . ($buttonLabel !== '' && $buttonUrl !== '' ? "
    <div style='margin:24px 0 0; text-align:center;'>
        <a href='" . commonMailEscape($buttonUrl) . "' style='display:inline-block; padding:13px 22px; background:" . commonMailEscape($brandColor) . "; color:white; text-decoration:none; border-radius:999px; font-weight:700;'>
            " . commonMailEscape($buttonLabel) . "
        </a>
    </div>
    <p style='margin:14px 0 0; font-size:12px; word-break:break-all; text-align:center; color:#64748b;'>
        <a href='" . commonMailEscape($buttonUrl) . "' style='color:#2563eb; text-decoration:underline;'>" . commonMailEscape($buttonUrl) . "</a>
    </p>
    " : "") . "
    " . ($footerHtml !== '' ? "<div style='margin-top:22px; font-size:12px; color:#64748b; line-height:1.6;'>" . $footerHtml . "</div>" : "") . "
</td>
</tr>
</table>
</td>
</tr>
</table>
</body>
</html>
";
    }
}
