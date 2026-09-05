<?php
/**
 * Phase 4/5: lift the HTTP layer (controllers, forms, services, listeners,
 * twig extensions, commands) and every template out of the SF2.8 bundles.
 *
 * Bundle-only machinery is deliberately NOT copied: DependencyInjection
 * extensions, *Bundle.php classes and the SF2 routing loaders have no meaning
 * in a Flex application. Tests are stubs (see MIGRATION-PLAN.md §2) and are
 * dropped rather than ported.
 *
 * usage: php migration/bin/port-http.php [--dry-run]
 */
$root   = dirname(__DIR__, 2);
$dryRun = in_array('--dry-run', $argv, true);

// ---- bundle class name -> module ------------------------------------------
// Templates and routing reference bundles by CLASS name ("SettingToolBundle"),
// while the module name comes from the directory ("ToolBundle" -> "Tool").
$bundleToModule = [];
$nsMap = [];
foreach (array_merge(
    glob("$root/src/*/*Bundle.php") ?: [],
    glob("$root/src/*/*/*Bundle.php") ?: [],
    glob("$root/src/*/*/*/*Bundle.php") ?: []
) as $f) {
    $class = basename($f, '.php');
    $dir   = basename(dirname($f));
    if (!str_ends_with($dir, 'Bundle')) continue;
    $module = substr($dir, 0, -strlen('Bundle'));
    $bundleToModule[$class] = $module;

    $src = (string) file_get_contents($f);
    if (preg_match('/^namespace\s+([A-Za-z\\\\]+);/m', $src, $m)) {
        $nsMap[$m[1]] = 'App\\' . $module;
    }
}
uksort($nsMap, fn($a, $b) => strlen($b) <=> strlen($a));
foreach ($nsMap as $legacy => $new) {
    $lc = lcfirst($legacy);
    if ($lc !== $legacy && !isset($nsMap[$lc])) $nsMap[$lc] = $new;
}
uksort($nsMap, fn($a, $b) => strlen($b) <=> strlen($a));

// Templates from the two removed FOS bundles are vendored under protected
// "_"-prefixed directories (see vendor-fos-templates.php), so references to
// them keep resolving after the bundles are gone.
$bundleToModule['FOSUserBundle']    = '_fosuser';
$bundleToModule['FOSCommentBundle'] = '_foscomment';

printf("%d bundles mapped\n", count($bundleToModule));

// ---- rewrite helpers -------------------------------------------------------

$rewriteNs = static function (string $s) use ($nsMap): string {
    foreach ($nsMap as $legacy => $new) {
        $s = str_replace($legacy . '\\', $new . '\\', $s);
        $s = str_replace(
            str_replace('\\', '\\\\', $legacy) . '\\\\',
            str_replace('\\', '\\\\', $new) . '\\\\',
            $s
        );
    }
    return $s;
};

require __DIR__ . '/hand-written.php';

$handWritten = handWrittenFiles();
$skipped = 0;
$mismatched = [];

$stats = ['tpl_refs' => 0, 'ctrl_refs' => 0];

/**
 * "SettingToolBundle:Widget:foo.html.twig" -> "Tool/Widget/foo.html.twig", and
 * "::base.html.twig" -> "base.html.twig".
 *
 * The second form is Symfony 2's name for a template in app/Resources/views,
 * with the bundle and controller segments left empty. 1,435 references use it,
 * 1,262 of them to ::base.html.twig - which is to say nearly every page in the
 * application. Twig's linter does not resolve inheritance, so nothing catches
 * this until a page is actually rendered.
 */
$rewriteTemplateRefs = static function (string $s) use ($bundleToModule, &$stats): string {
    $s = preg_replace_callback(
        '/([\'"])::([A-Za-z0-9_.\/-]+\.twig)\1/',
        static function (array $m) use (&$stats): string {
            $stats['tpl_refs']++;

            return $m[1] . $m[2] . $m[1];
        },
        $s
    );

    return preg_replace_callback(
        '/\b([A-Za-z]+Bundle):([A-Za-z0-9_]*):([A-Za-z0-9_.\/\-{}$]+\.twig)\b/',
        static function (array $m) use ($bundleToModule, &$stats): string {
            $module = $bundleToModule[$m[1]] ?? null;
            if ($module === null) return $m[0];
            $stats['tpl_refs']++;
            return $m[2] === '' ? "$module/$m[3]" : "$module/$m[2]/$m[3]";
        },
        $s
    );
};

/** "AccountingBundle:Default:salesMigration" -> FQCN::method */
$rewriteControllerRefs = static function (string $s) use ($bundleToModule, &$stats): string {
    return preg_replace_callback(
        '/\b([A-Za-z]+Bundle):([A-Za-z0-9_]+):([A-Za-z0-9_]+)\b(?!\.)/',
        static function (array $m) use ($bundleToModule, &$stats): string {
            $module = $bundleToModule[$m[1]] ?? null;
            if ($module === null) return $m[0];
            $stats['ctrl_refs']++;
            return sprintf('App\\%s\\Controller\\%sController::%sAction', $module, $m[2], $m[3]);
        },
        $s
    );
};

// ---- 1. PHP layers ---------------------------------------------------------

$KINDS = ['Controller', 'Form', 'Service', 'Command', 'EventListener', 'Listener',
          'Twig', 'TwigExtension', 'Menu', 'Event', 'Manager', 'DQL', 'Importer',
          'Permission', 'Model', 'Utility', 'Validator', 'Helper', 'Extension'];

$phpFiles = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/src", FilesystemIterator::SKIP_DOTS)) as $f) {
    if ($f->getExtension() !== 'php') continue;
    $p = $f->getPathname();
    if (str_contains($p, '/Tests/') || str_contains($p, '/DependencyInjection/')) continue;
    if (str_contains($p, '/Routing/')) continue;          // SF2 route loaders
    if (preg_match('/\w+Bundle\.php$/', $p)) continue;    // bundle classes
    foreach ($KINDS as $k) {
        if (str_contains($p, "/$k/")) { $phpFiles[] = $p; break; }
    }
}

$written = 0; $unmapped = [];
foreach ($phpFiles as $p) {
    $src = (string) file_get_contents($p);
    if (!preg_match('/^namespace\s+([A-Za-z\\\\]+);/m', $src, $m)) continue;
    $ns = $m[1];

    $newNs = null;
    foreach ($nsMap as $legacy => $new) {
        if ($ns === $legacy || str_starts_with($ns, $legacy . '\\')) {
            $newNs = $new . substr($ns, strlen($legacy));
            break;
        }
    }
    if ($newNs === null) { $unmapped[$ns] = true; continue; }

    $out = $rewriteNs($src);
    $out = $rewriteTemplateRefs($out);

    $target = "$root/next/src/" . str_replace('\\', '/', substr($newNs, strlen('App\\'))) . '/' . basename($p);

    // Written for the legacy application only: the Symfony 8 half of the
    // session bridge is App\Security\SharedSessionAuthenticator.
    if (str_ends_with($p, 'Core/UserBundle/EventListener/SharedSessionListener.php')) { continue; }

    // A hand-written replacement is never overwritten by its ported original.
    if (isset($handWritten[substr($target, strlen("$root/next/"))])) { $skipped++; continue; }

    // A file whose class name does not match its file name was never loadable
    // under PSR-0 either, so it is dead in the legacy tree. Copying it in makes
    // it a duplicate declaration, because Symfony and Doctrine both scan the
    // directory rather than autoload it.
    if (preg_match('/^(?:final |abstract )?class\s+(\w+)/m', $out, $cm)
        && $cm[1] !== basename($p, '.php')) {
        $mismatched[] = substr($p, strlen("$root/")) . " declares class {$cm[1]}";
        continue;
    }

    if (!$dryRun) { @mkdir(dirname($target), 0775, true); file_put_contents($target, $out); }
    $written++;
}
printf("%s %d php files (controllers, forms, services, listeners)\n", $dryRun ? 'would write' : 'wrote', $written);
if ($skipped) printf("  kept %d hand-written file(s) - see migration/hand-written.txt\n", $skipped);
foreach ($mismatched as $line) printf("  skipped dead file: %s\n", $line);
if ($unmapped) printf("  unmapped namespaces: %s\n", implode(', ', array_keys($unmapped)));

// ---- 2. templates ----------------------------------------------------------

$tpl = 0;
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/src", FilesystemIterator::SKIP_DOTS)) as $f) {
    if ($f->getExtension() !== 'twig') continue;
    $p = $f->getPathname();
    if (!preg_match('#/([A-Za-z]+Bundle)/Resources/views/(.+)$#', $p, $m)) continue;

    $module = null;
    foreach ($bundleToModule as $class => $mod) {
        if (str_ends_with($m[1], 'Bundle') && $mod === substr($m[1], 0, -strlen('Bundle'))) { $module = $mod; break; }
    }
    $module ??= substr($m[1], 0, -strlen('Bundle'));

    $body = $rewriteTemplateRefs((string) file_get_contents($p));

    $target = "$root/next/templates/$module/" . $m[2];
    if (!$dryRun) { @mkdir(dirname($target), 0775, true); file_put_contents($target, $body); }
    $tpl++;
}
printf("%s %d templates -> next/templates/\n", $dryRun ? 'would write' : 'wrote', $tpl);

// ---- 3. the application's own templates ------------------------------------
//
// app/Resources/views holds the layouts every page extends - base.html.twig,
// login.html.twig, the print and POS bases, the customer and frontend trees.
// Symfony 2 resolved a bare "base.html.twig" there. Without them the Flex
// skeleton's base.html.twig stands in silently: the page renders, with no
// stylesheet, no menu and the wrong title.

$app = 0;

if (is_dir("$root/app/Resources/views")) {
    foreach (new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator("$root/app/Resources/views", FilesystemIterator::SKIP_DOTS)
    ) as $f) {
        if ($f->getExtension() !== 'twig') continue;

        $relative = substr($f->getPathname(), strlen("$root/app/Resources/views/"));
        $body = $rewriteTemplateRefs((string) file_get_contents($f->getPathname()));

        $target = "$root/next/templates/" . $relative;
        if (!$dryRun) { @mkdir(dirname($target), 0775, true); file_put_contents($target, $body); }
        $app++;
    }
}

printf("%s %d app-level templates (app/Resources/views) -> next/templates/\n",
    $dryRun ? 'would write' : 'wrote', $app);

// Routing is handled by port-routing.php, which also walks nested
// Resources/config directories and rewrites XML and import paths.

printf("\nrewrote %d template refs, %d controller refs\n", $stats['tpl_refs'], $stats['ctrl_refs']);
