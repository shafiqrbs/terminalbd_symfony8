<?php
/**
 * Vendors the Twig templates from FOSUserBundle and FOSCommentBundle into the
 * repository.
 *
 * Both bundles are removed by the migration, but the app's own templates
 * {% include %} their partials (e.g. FOSUserBundle:Resetting:reset_content).
 * The files are copied under "_"-prefixed directories, which migrate.sh treats
 * as hand-maintained and never wipes.
 *
 * Sources are the exact revisions pinned in the legacy composer.lock.
 *
 * usage: php migration/bin/vendor-fos-templates.php <fosuser-dir> <foscomment-dir>
 */
$root = dirname(__DIR__, 2);

$sources = [
    '_fosuser'    => $argv[1] ?? null,
    '_foscomment' => $argv[2] ?? null,
];

$total = 0;
foreach ($sources as $target => $src) {
    if ($src === null || !is_dir($src)) { fwrite(STDERR, "skip $target: source not available\n"); continue; }
    $views = "$src/Resources/views";
    if (!is_dir($views)) { fwrite(STDERR, "skip $target: no Resources/views\n"); continue; }

    $n = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($views, FilesystemIterator::SKIP_DOTS)) as $f) {
        if ($f->getExtension() !== 'twig') continue;
        $rel  = substr($f->getPathname(), strlen($views) + 1);
        $dest = "$root/next/templates/$target/$rel";
        @mkdir(dirname($dest), 0775, true);

        // These carry the same Bundle:Dir:file refs as the app's own templates.
        $body = (string) file_get_contents($f->getPathname());
        $body = preg_replace('/\bFOSUserBundle:([A-Za-z0-9_]*):/', '_fosuser/$1/', $body);
        $body = preg_replace('/\bFOSCommentBundle:([A-Za-z0-9_]*):/', '_foscomment/$1/', $body);
        $body = str_replace(['_fosuser//', '_foscomment//'], ['_fosuser/', '_foscomment/'], $body);

        file_put_contents($dest, $body);
        $n++;
    }
    printf("vendored %d templates -> next/templates/%s/\n", $n, $target);
    $total += $n;
}
printf("total %d\n", $total);
