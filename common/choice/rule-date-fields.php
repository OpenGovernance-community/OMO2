<?php

function omoRuleDateRenderInput(string $name, $value): void
{
    $escape = static fn ($text): string => htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
    $value = $value instanceof DateTimeInterface ? $value->format('Y-m-d') : (string)$value;
    // Native date fields use the browser format while submitting an ISO date.
    ?>
    <input class="generic-form-control" type="date" name="<?= $escape($name) ?>" value="<?= $escape($value) ?>" required>
    <?php
}
