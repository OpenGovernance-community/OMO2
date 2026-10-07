<?php
namespace dbObject;

/** Portable ZIP transport shared by export, import and the import preview. */
class OrganizationArchive
{
    private const MAX_TOTAL = 134217728;
    private const MAX_JSON = 33554432;
    private const MAX_IMAGE = 16777216;
    private const TYPES = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/webp' => 'webp', 'image/avif' => 'avif'];

    public static function create(array $payload, string $destination): array
    {
        $root = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__, 2)));
        if (!$root) { throw new \RuntimeException('Repertoire des images introuvable.'); }
        $assets = [];
        $warnings = [];
        $total = 0;
        self::media($payload, function (string $path) use ($root, &$assets, &$warnings, &$total): string {
            if ($path === '') { return ''; }
            // No remote fetches, application endpoints or files outside the public image directory.
            $file = str_starts_with($path, '/img/') && !str_contains($path, "\0") ? realpath($root.$path) : false;
            $imageRoot = realpath($root.'/img');
            if (!$file || !$imageRoot || !str_starts_with(str_replace('\\', '/', $file), str_replace('\\', '/', $imageRoot).'/') || !is_file($file)) {
                $warnings[] = 'Image non incluse : '.$path;
                return '';
            }
            $size = filesize($file);
            $info = @getimagesize($file);
            $extension = is_array($info) ? (self::TYPES[$info['mime']] ?? null) : null;
            if (!$extension || $size > self::MAX_IMAGE) { throw new \RuntimeException('Image non prise en charge ou trop volumineuse : '.$path); }
            $hash = hash_file('sha256', $file);
            $entry = 'images/'.$hash.'.'.$extension;
            if (!isset($assets[$entry])) {
                if (count($assets) >= 2000) { throw new \RuntimeException('Trop d images dans l export (2000 maximum).'); }
                $total += $size;
                if ($total > self::MAX_TOTAL) { throw new \RuntimeException('Les images depassent la taille maximale de 128 Mio.'); }
                $assets[$entry] = ['file' => $file, 'sha256' => $hash, 'mime' => $info['mime'], 'size' => $size];
            }
            return $entry;
        });
        $payload['media'] = [];
        foreach ($assets as $entry => $asset) { $payload['media'][$entry] = array_diff_key($asset, ['file' => true]); }
        $payload['source']['archive'] = 'omo-zip-1';
        $payload['source']['mediaWarnings'] = array_values(array_unique($warnings));
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (strlen($json) > self::MAX_JSON || strlen($json) + $total > self::MAX_TOTAL) { throw new \RuntimeException('Archive trop volumineuse.'); }
        $zip = new \ZipArchive();
        if ($zip->open($destination, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) { throw new \RuntimeException('Impossible de creer le ZIP.'); }
        try {
            if (!$zip->addFromString('organization.json', $json)) { throw new \RuntimeException('Ecriture du JSON impossible.'); }
            foreach ($assets as $entry => $asset) {
                if (!$zip->addFile($asset['file'], $entry)) { throw new \RuntimeException('Ecriture d une image impossible.'); }
            }
        } catch (\Throwable $e) { $zip->close(); @unlink($destination); throw $e; }
        if (!$zip->close()) { @unlink($destination); throw new \RuntimeException('Finalisation du ZIP impossible.'); }
        return ['images' => count($assets), 'warnings' => $payload['source']['mediaWarnings'], 'payload' => $payload];
    }

    /** Preview validates the same bytes as import but never writes images. */
    public static function read(string $filename, bool $materialize = false): array
    {
        if (!is_file($filename) || filesize($filename) > self::MAX_TOTAL) { throw new \RuntimeException('Fichier absent ou trop volumineux (128 Mio maximum).'); }
        $stream = fopen($filename, 'rb');
        $signature = fread($stream, 4);
        fclose($stream);
        if (!str_starts_with($signature, 'PK')) {
            if (filesize($filename) > self::MAX_JSON) { throw new \RuntimeException('JSON trop volumineux.'); }
            $payload = json_decode(file_get_contents($filename), true, 128, JSON_THROW_ON_ERROR);
            self::validatePayload($payload);
            if (!empty($payload['media'])) { throw new \RuntimeException('Importez le ZIP complet pour retrouver ses images.'); }
            return ['payload' => $payload, 'files' => []];
        }
        $zip = new \ZipArchive();
        if ($zip->open($filename, \ZipArchive::RDONLY) !== true) { throw new \RuntimeException('ZIP invalide.'); }
        $files = [];
        try {
            if ($zip->numFiles > 2001) { throw new \RuntimeException('Trop de fichiers dans le ZIP.'); }
            $entries = [];
            $total = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $name = $stat['name'];
                if (isset($entries[$name]) || ($name !== 'organization.json' && !preg_match('/^images\/[a-f0-9]{64}\.(png|jpg|gif|webp|avif)$/D', $name))) { throw new \RuntimeException('Entree ZIP non autorisee.'); }
                $limit = $name === 'organization.json' ? self::MAX_JSON : self::MAX_IMAGE;
                $total += $stat['size'];
                if ($stat['size'] > $limit || $total > self::MAX_TOTAL || !empty($stat['encryption_method'])) { throw new \RuntimeException('ZIP chiffre ou trop volumineux.'); }
                $entries[$name] = $stat;
            }
            if (!isset($entries['organization.json'])) { throw new \RuntimeException('organization.json manque dans le ZIP.'); }
            $payload = json_decode($zip->getFromName('organization.json'), true, 128, JSON_THROW_ON_ERROR);
            self::validatePayload($payload);
            $media = $payload['media'] ?? [];
            if (!is_array($media) || count($entries) !== count($media) + 1) { throw new \RuntimeException('Inventaire des images invalide.'); }
            $bytes = [];
            foreach ($media as $entry => $meta) {
                if ($entry === 'organization.json' || !isset($entries[$entry]) || !is_array($meta)) { throw new \RuntimeException('Image manquante dans le ZIP.'); }
                $data = $zip->getFromName($entry);
                $info = is_string($data) ? @getimagesizefromstring($data) : false;
                $hash = is_string($data) ? hash('sha256', $data) : '';
                if (!$info || !isset(self::TYPES[$info['mime']]) || ($meta['mime'] ?? '') !== $info['mime'] || ($meta['sha256'] ?? '') !== $hash || strlen($data) !== (int)($meta['size'] ?? -1)
                    || $entry !== 'images/'.$hash.'.'.self::TYPES[$info['mime']]) { throw new \RuntimeException('Image corrompue ou format non autorise.'); }
                $bytes[$entry] = $data;
            }
            // Validate every reference before creating any file.
            self::media($payload, function (string $path) use ($media): string {
                if ($path !== '' && !isset($media[$path])) { throw new \RuntimeException('Reference image absente du ZIP.'); }
                return $path;
            });
            if ($materialize && $bytes !== []) {
                $root = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__, 2)));
                if (!$root) { throw new \RuntimeException('Repertoire des images introuvable.'); }
                $importRoot = $root.'/img/upload/import';
                if ((!is_dir($importRoot) && !@mkdir($importRoot, 0755, true) && !is_dir($importRoot)) || !is_writable($importRoot)) {
                    throw new \RuntimeException('Le repertoire d import des images n est pas accessible en ecriture. Verifiez les droits du serveur web.');
                }
                $relative = '/img/upload/import/'.bin2hex(random_bytes(8));
                $directory = $root.$relative;
                if (!@mkdir($directory, 0755)) { throw new \RuntimeException('Impossible d enregistrer les images.'); }
                $localPaths = [];
                foreach ($bytes as $entry => $data) {
                    $localPaths[$entry] = $relative.'/'.bin2hex(random_bytes(16)).'.'.pathinfo($entry, PATHINFO_EXTENSION);
                    $path = $root.$localPaths[$entry];
                    $files[] = $path;
                    if (file_put_contents($path, $data, LOCK_EX) !== strlen($data)) { throw new \RuntimeException('Ecriture image incomplete.'); }
                }
                self::media($payload, fn(string $path): string => $path === '' ? '' : $localPaths[$path]);
            }
            return ['payload' => $payload, 'files' => $files];
        } catch (\Throwable $e) { self::cleanup($files); throw $e; }
        finally { $zip->close(); }
    }

    public static function cleanup(array $files): void
    {
        foreach ($files as $file) { @unlink($file); }
        foreach (array_unique(array_map('dirname', $files)) as $directory) { @rmdir($directory); }
    }

    private static function validatePayload($payload): void
    {
        if (!is_array($payload) || ($payload['format'] ?? '') !== OrganizationExport::FORMAT || (int)($payload['version'] ?? 0) !== 4) { throw new \RuntimeException('Le fichier doit etre un export OMO compact version 4.'); }
    }

    private static function media(array &$node, callable $convert): void
    {
        foreach ($node as $key => &$value) {
            if ($key === 'media' || $key === 'source') { continue; }
            if (is_array($value)) { self::media($value, $convert); }
            elseif (is_string($value) && in_array($key, ['image', 'icon', 'logo', 'banner'], true)) { $value = $convert($value); }
            elseif (is_string($value) && str_contains($value, '<img')) {
                $value = preg_replace_callback('~(<img\b[^>]*\bsrc\s*=\s*)(["\x27])([^"\x27]+)\2~i', function ($m) use ($convert) {
                    return $m[1].$m[2].htmlspecialchars($convert(html_entity_decode($m[3], ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8').$m[2];
                }, $value);
            }
        }
        unset($value);
    }
}
