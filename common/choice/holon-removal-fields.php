<?php

function omoHolonRemovalT(string $key): string
{
    static $sourceLang = [
        'label' => ['text' => 'Que faire des autorités de cet espace et de ses descendants ?', 'context' => 'Required authority disposition before holon archive or deletion'],
        'choose' => ['text' => 'Choisir une option', 'context' => 'Empty required authority disposition option'],
        'delete_all' => ['text' => 'Supprimer les autorités et les règles associées', 'context' => 'Holon removal authority disposition'],
        'delete_keep_rules' => ['text' => 'Supprimer les autorités et remonter les règles à l’autorité précédente', 'context' => 'Holon removal authority disposition'],
        'reassign' => ['text' => 'Remonter les autorités au holon de leur autorité parente', 'context' => 'Holon removal authority disposition'],
        'local' => ['text' => 'Sans autorité parente extérieure, les autorités locales restent archivées avec leur espace, ou sont supprimées avec lui. Le choix de tout supprimer les supprime aussi lors d’un archivage.', 'context' => 'Local authority handling when there is no destination'],
        'archive' => ['text' => 'La restauration de cet espace ne recréera pas les autorités ou règles supprimées et ne ramènera pas celles qui ont été déplacées.', 'context' => 'Archive authority disposition is not undone by holon restoration'],
        'required' => ['text' => 'Choisissez le traitement des autorités.', 'context' => 'Missing authority disposition validation'],
        'deferred' => ['text' => 'La suppression et le traitement des autorités seront appliqués uniquement après validation de cette proposition.', 'context' => 'Deferred holon deletion confirmation'],
    ];
    static $bundle = null;
    $bundle ??= omoLoadTranslationBundle('omo_holon_removal', $sourceLang);
    return t($key, [], $bundle, $sourceLang);
}

function omoRenderHolonRemovalFields(string $selected = '', bool $archiving = false): void
{
    $escape = static fn(string $text): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    ?>
    <label class="generic-form-field">
        <span class="generic-form-label"><?= $escape(omoHolonRemovalT('label')) ?></span>
        <select name="authority_choice" class="generic-form-control" required>
            <option value=""><?= $escape(omoHolonRemovalT('choose')) ?></option>
            <?php foreach (\dbObject\Authority::holonRemovalChoices() as $choice): ?>
            <option value="<?= $choice ?>"<?= $selected === $choice ? ' selected' : '' ?>><?= $escape(omoHolonRemovalT($choice)) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <p class="generic-description"><?= $escape(omoHolonRemovalT('local')) ?></p>
    <?php if ($archiving): ?><p class="generic-description"><?= $escape(omoHolonRemovalT('archive')) ?></p><?php endif; ?>
    <?php
}
