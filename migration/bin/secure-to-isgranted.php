<?php
/**
 * Converts JMS @Secure(roles="...") docblocks to Symfony 8 #[IsGranted].
 *
 * SEMANTICS - this is a security boundary, so the reasoning is recorded here:
 * JMSSecurityExtraBundle's MethodSecurityInterceptor calls
 *     $accessDecisionManager->decide($token, $metadata->roles, $method)
 * passing every role as an access attribute. app/config/security.yml never sets
 * access_decision_manager.strategy, so Symfony's default "affirmative" applies:
 * access is granted if ANY attribute passes. @Secure(roles="A,B") therefore
 * means A OR B, not A AND B.
 *
 * #[IsGranted('A')] takes a single attribute, and stacking two of them is an
 * AND - which would lock users out. Multi-role checks are emitted as an
 * expression instead, preserving OR.
 *
 * usage: php migration/bin/secure-to-isgranted.php <dir> [--dry-run]
 */
$dir    = $argv[1] ?? null;
$dryRun = in_array('--dry-run', $argv, true);
if (!$dir || !is_dir($dir)) { fwrite(STDERR, "usage: secure-to-isgranted.php <dir> [--dry-run]\n"); exit(1); }

$GLOBALS['IS_GRANTED'] = 'Symfony\\Component\\Security\\Http\\Attribute\\IsGranted';
$GLOBALS['EXPRESSION'] = 'Symfony\\Component\\ExpressionLanguage\\Expression';

$files = 0; $single = 0; $multi = 0; $roleSet = [];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $f) {
    if ($f->getExtension() !== 'php') continue;
    $src = (string) file_get_contents($f->getPathname());
    if (!str_contains($src, '@Secure')) continue;

    $needsExpression = false;

    $out = preg_replace_callback(
        '#(?<indent>[ \t]*)/\*\*.*?\*/#s',
        static function (array $m) use (&$single, &$multi, &$needsExpression, &$roleSet): string {
            $block  = $m[0];
            $indent = $m['indent'];
            if (!str_contains($block, '@Secure')) return $block;

            $attrs = [];
            $stripped = preg_replace_callback(
                '/^[ \t]*\*[ \t]*@Secure\(\s*roles\s*=\s*"([^"]*)"\s*\)[ \t]*\R?/m',
                static function (array $mm) use (&$attrs, &$single, &$multi, &$needsExpression, &$roleSet): string {
                    $roles = array_values(array_filter(array_map('trim', explode(',', $mm[1]))));
                    foreach ($roles as $r) $roleSet[$r] = true;

                    if (count($roles) === 1) {
                        $single++;
                        $attrs[] = "#[IsGranted('{$roles[0]}')]";
                    } elseif (count($roles) > 1) {
                        $multi++;
                        $needsExpression = true;
                        $expr = implode(' or ', array_map(
                            static fn (string $r): string => "is_granted('$r')",
                            $roles
                        ));
                        $attrs[] = '#[IsGranted(new Expression("' . $expr . '"))]';
                    }
                    return '';
                },
                $block
            );

            if (!$attrs) return $block;

            // Drop the docblock entirely if @Secure was all it carried.
            $body = preg_replace('#^[ \t]*/\*\*\R?|^[ \t]*\*/\R?#m', '', $stripped);
            $body = trim(preg_replace('/^[ \t]*\*[ \t]?/m', '', $body));

            $attrText = implode("\n", array_map(static fn (string $a): string => $indent . $a, $attrs));

            if ($body === '') return $attrText;

            $rebuilt = $indent . "/**\n";
            foreach (explode("\n", $body) as $l) $rebuilt .= rtrim($indent . ' * ' . $l) . "\n";
            $rebuilt .= $indent . ' */';

            return $rebuilt . "\n" . $attrText;
        },
        $src
    );

    if ($out === $src) continue;

    // add imports after the namespace declaration
    $imports = ["use $GLOBALS[IS_GRANTED];"];
    if ($needsExpression) $imports[] = "use $GLOBALS[EXPRESSION];";
    foreach ($imports as $imp) {
        $cls = rtrim(substr($imp, 4), ';');
        if (!preg_match('/^use\s+' . preg_quote($cls, '/') . '\s*;/m', $out)) {
            $out = preg_replace('/^(namespace\s+[^;]+;\s*\R)/m', '$1' . $imp . "\n", $out, 1);
        }
    }

    $files++;
    if (!$dryRun) file_put_contents($f->getPathname(), $out);
}

printf("%s %d files: %d single-role #[IsGranted], %d multi-role expressions\n",
    $dryRun ? 'would convert' : 'converted', $files, $single, $multi);
printf("distinct roles referenced: %d\n", count($roleSet));
