<?php

require_once __DIR__ . '/email_layout.php';

function faqMailBrandOptions($organization)
{
	$brandName = $organization instanceof \dbObject\Organization
		? trim((string)$organization->get('name'))
		: '';
	if ($brandName === '') {
		$brandName = trim((string)($GLOBALS['siteTitle'] ?? '')) ?: 'FAQ';
	}

	return [
		'brand_name' => $brandName,
		'brand_color' => $organization instanceof \dbObject\Organization ? (string)$organization->get('color') : '',
		'logo_url' => $organization instanceof \dbObject\Organization ? commonBuildAbsoluteAssetUrl((string)$organization->get('logo')) : '',
		'banner_url' => $organization instanceof \dbObject\Organization ? commonBuildAbsoluteAssetUrl((string)$organization->get('banner')) : '',
	];
}

function faqMailRenderQuestionRequest(\dbObject\FAQ $faq, $faqUrl, $organization = null)
{
	$authorName = trim((string)$faq->get('request_author_name'));
	$authorEmail = trim((string)$faq->get('request_author_email'));
	$authorLabel = $authorName !== '' ? commonMailEscape($authorName) . ' (' . commonMailEscape($authorEmail) . ')' : commonMailEscape($authorEmail);

	return commonRenderMailLayout(faqMailBrandOptions($organization) + [
		'heading' => 'Nouvelle question dans la FAQ',
		'intro_html' => '<p style="margin:0 0 16px;color:#475569;line-height:1.7;">Une nouvelle question attend une réponse.</p>',
		'body_html' => '<p style="margin:0;color:#475569;line-height:1.7;"><strong>Auteur :</strong> ' . $authorLabel . '</p>',
		'details_html' => '<p style="margin:0 0 6px;color:#64748b;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Question</p>'
			. '<p style="margin:0 0 18px;color:#0f172a;font-size:17px;font-weight:700;line-height:1.5;">' . commonMailEscape($faq->get('question')) . '</p>'
			. '<p style="margin:0 0 6px;color:#64748b;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Description du problème</p>'
			. commonMailTextToHtml($faq->get('request_description')),
		'button_label' => 'Ouvrir la question',
		'button_url' => (string)$faqUrl,
	]);
}

function faqMailDetailAsText($html)
{
	$html = preg_replace('/<\s*br\b[^>]*>/i', "\n", (string)$html);
	$html = preg_replace('/<\s*\/\s*(?:p|div|li|h[1-6])\s*>/i', "\n\n", (string)$html);
	return trim(html_entity_decode(strip_tags((string)$html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

function faqMailRenderAnswer(\dbObject\FAQ $faq, $organization = null)
{
	$authorName = trim((string)$faq->get('request_author_name'));
	$greeting = $authorName !== '' ? 'Bonjour ' . commonMailEscape($authorName) . ',' : 'Bonjour,';
	$detailsHtml = '<p style="margin:0 0 8px;color:#64748b;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Réponse</p>'
		. commonMailTextToHtml($faq->get('answer'));
	$detailText = faqMailDetailAsText($faq->get('detail'));
	if ($detailText !== '') {
		$detailsHtml .= '<div style="border-top:1px solid #dbe3ef;margin-top:18px;padding-top:18px;">'
			. '<p style="margin:0 0 8px;color:#64748b;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Précisions</p>'
			. commonMailTextToHtml($detailText)
			. '</div>';
	}

	return commonRenderMailLayout(faqMailBrandOptions($organization) + [
		'heading' => 'Réponse à votre question',
		'intro_html' => '<p style="margin:0 0 14px;color:#475569;line-height:1.7;">' . $greeting . '</p>'
			. '<p style="margin:0 0 18px;color:#475569;line-height:1.7;">Un administrateur a répondu à votre question dans la FAQ.</p>',
		'body_html' => '<p style="margin:0 0 6px;color:#64748b;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Votre question</p>'
			. '<p style="margin:0;color:#0f172a;font-size:17px;font-weight:700;line-height:1.5;">' . commonMailEscape($faq->get('question')) . '</p>',
		'details_html' => $detailsHtml,
	]);
}

function faqMailRenderOrganizationRelay(\dbObject\FAQ $faq, $faqUrl, $organization)
{
	$organizationName = $organization instanceof \dbObject\Organization
		? trim((string)$organization->get('name'))
		: '';
	$organizationName = $organizationName !== '' ? $organizationName : 'votre organisation';

	return commonRenderMailLayout(faqMailBrandOptions($organization) + [
		'heading' => 'Question FAQ a traiter',
		'intro_html' => '<p style="margin:0 0 16px;color:#475569;line-height:1.7;">Un administrateur vous a relayé une question qui concerne ' . commonMailEscape($organizationName) . '.</p>',
		'body_html' => '<p style="margin:0;color:#475569;line-height:1.7;">Ouvrez la demande, puis utilisez le bouton « Éditer » pour rédiger et sauver la réponse dans la FAQ de votre organisation.</p>',
		'details_html' => '<p style="margin:0 0 6px;color:#64748b;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Question</p>'
			. '<p style="margin:0 0 18px;color:#0f172a;font-size:17px;font-weight:700;line-height:1.5;">' . commonMailEscape($faq->get('question')) . '</p>'
			. '<p style="margin:0 0 6px;color:#64748b;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Description du problème</p>'
			. commonMailTextToHtml($faq->get('request_description')),
		'button_label' => 'Ouvrir la question',
		'button_url' => (string)$faqUrl,
	]);
}
