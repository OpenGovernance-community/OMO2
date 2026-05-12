<?php

if (!function_exists('omoRenderCompetenceTypeOptions')) {
	function omoRenderCompetenceTypeOptions($selected = '')
	{
		$options = \dbObject\UserCompetence::getTypeOptions();
		$selected = \dbObject\UserCompetence::normalizeCategory($selected);

		foreach ($options as $value => $label) {
			echo '<option value="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"' . ($value === $selected ? ' selected' : '') . '>' . htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8') . '</option>';
		}
	}
}

if (!function_exists('omoRenderCompetenceLevelOptions')) {
	function omoRenderCompetenceLevelOptions($selected = 0, $includePlaceholder = false)
	{
		$options = \dbObject\UserCompetence::getLevelOptions();
		$selected = (int)$selected;

		if ($includePlaceholder) {
			echo '<option value="">Choisir</option>';
		}

		foreach ($options as $value => $label) {
			echo '<option value="' . (int)$value . '"' . ((int)$value === $selected ? ' selected' : '') . '>' . htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8') . '</option>';
		}
	}
}

if (!function_exists('omoRenderCompetenceAvatar')) {
	function omoRenderCompetenceAvatar(array $person, $className = 'omo-competence-avatar')
	{
		$photoUrl = trim((string)($person['photoUrl'] ?? ''));
		$displayName = trim((string)($person['displayName'] ?? ''));
		$initials = trim((string)($person['initials'] ?? 'P'));
		$titleParts = [];

		if ($displayName !== '') {
			$titleParts[] = $displayName;
		}
		if (!empty($person['levelLabel'])) {
			$titleParts[] = (string)$person['levelLabel'];
		}
		$title = implode(' - ', $titleParts);

		if ($photoUrl !== '') {
			echo '<img src="' . htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($displayName !== '' ? $displayName : $initials, ENT_QUOTES, 'UTF-8') . '" class="' . htmlspecialchars($className, ENT_QUOTES, 'UTF-8') . '"' . ($title !== '' ? ' title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '"' : '') . '>';
			return;
		}

		echo '<span class="' . htmlspecialchars($className . ' ' . $className . '--placeholder', ENT_QUOTES, 'UTF-8') . '"' . ($title !== '' ? ' title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '"' : '') . '>' . htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') . '</span>';
	}
}

?>
