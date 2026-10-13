<?php

function omoHolonRemovalT(string $key): string
{
    static $sourceLang = [
        'label' => ['text' => 'Que faire des autorites de cet espace et de ses descendants ?', 'context' => 'Required authority disposition before holon archive or deletion'],
        'choose' => ['text' => 'Choisir une option', 'context' => 'Empty required authority disposition option'],
        'delete_all' => ['text' => 'Supprimer les autorites et les regles associees', 'context' => 'Holon removal authority disposition'],
        'delete_keep_rules' => ['text' => 'Supprimer les autorites et remonter les regles a l autorite precedente', 'context' => 'Holon removal authority disposition'],
        'reassign' => ['text' => 'Remonter les autorites au holon de leur autorite parente', 'context' => 'Holon removal authority disposition'],
        'local' => ['text' => 'Sans autorite parente exterieure, les autorites locales restent archivees avec leur espace, ou sont supprimees avec lui. Le choix de tout supprimer les supprime aussi lors d un archivage.', 'context' => 'Local authority handling when there is no destination'],
        'archive' => ['text' => 'La restauration de cet espace ne recreera pas les autorites ou regles supprimees et ne ramenera pas celles qui ont ete deplacees.', 'context' => 'Archive authority disposition is not undone by holon restoration'],
        'required' => ['text' => 'Choisissez le traitement des autorites.', 'context' => 'Missing authority disposition validation'],
        'deferred' => ['text' => 'La suppression et le traitement des autorites seront appliques uniquement apres validation de cette proposition.', 'context' => 'Deferred holon deletion confirmation'],
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
