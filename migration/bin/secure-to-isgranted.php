<?php
/**
 * Converts JMS @Secure(roles="...") and Sensio @Security("...") docblocks to
 * Symfony 8 #[IsGranted].
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
 * Sensio's @Security carries an expression rather than a role list, and the
 * legacy application applied it at class level *in addition to* the method's
 * @Secure - so the two are an AND, which is what stacked #[IsGranted] gives.
 * Leaving it behind is not harmless: SensioFrameworkExtraBundle is not
 * installed, so the docblock is inert, and the class-level gate simply stops
 * being enforced. On ReportController that is the difference between
 * ROLE_REPORT_OPERATION_SALES reaching the financial reports and not.
 *
 * Only expressions built from is_granted(), and/or/not and parentheses are
 * converted. Anything referencing `subject`, `object` or `request` is left in
 * place and reported, because those need a per-site reading.
 *
 * usage: php migration/bin/secure-to-isgranted.php <dir> [--dry-run]
 */
$dir    = $argv[1] ?? null;
$dryRun = in_array('--dry-run', $argv, true);
if (!$dir || !is_dir($dir)) { fwrite(STDERR, "usage: secure-to-isgranted.php <dir> [--dry-run]\n"); exit(1); }

$GLOBALS['IS_GRANTED'] = 'Symfony\\Component\\Security\\Http\\Attribute\\IsGranted';
$GLOBALS['EXPRESSION'] = 'Symfony\\Component\\ExpressionLanguage\\Expression';

$files = 0; $single = 0; $multi = 0; $expressions = 0; $roleSet = []; $skipped = [];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $f) {
    if ($f->getExtension() !== 'php') continue;
    $src = (string) file_get_contents($f->getPathname());
    if (!str_contains($src, '@Secure') && !str_contains($src, '@Security')) continue;

    $needsExpression = false;
    $path = $f->getPathname();

    $out = preg_replace_callback(
        '#(?<indent>[ \t]*)/\*\*.*?\*/#s',
        static function (array $m) use (&$single, &$multi, &$expressions, &$needsExpression, &$roleSet, &$skipped, $path): string {
            $block  = $m[0];
            $indent = $m['indent'];
            if (!str_contains($block, '@Secure') && !str_contains($block, '@Security')) return $block;

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

            // Sensio @Security("<expression>"), the class-level gate.
            $stripped = preg_replace_callback(
                '/^[ \t]*\*[ \t]*@Security\(\s*"(?<expr>(?:[^"\\\\]|\\\\.)*)"\s*\)[ \t]*\R?/m',
                static function (array $mm) use (&$attrs, &$expressions, &$needsExpression, &$roleSet, &$skipped, $path): string {
                    $expr = stripslashes($mm['expr']);

                    // Only role checks. `subject`/`object`/`request` change
                    // meaning between the two bundles and need a human.
                    $bare = preg_replace('/is_granted\(\s*\'[A-Z0-9_]+\'\s*\)/', '', $expr);
                    if (trim(preg_replace('/\b(and|or|not)\b|[()\s]/', '', $bare)) !== '') {
                        $skipped[] = "$path: $expr";

                        return $mm[0];
                    }

                    foreach (preg_match_all('/is_granted\(\s*\'([A-Z0-9_]+)\'\s*\)/', $expr, $rm) ? $rm[1] : [] as $r) {
                        $roleSet[$r] = true;
                    }

                    $expressions++;
                    $needsExpression = true;
                    $attrs[] = '#[IsGranted(new Expression("' . $expr . '"))]';

                    return '';
                },
                $stripped
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

printf("%s %d files: %d single-role #[IsGranted], %d multi-role expressions, %d @Security expressions\n",
    $dryRun ? 'would convert' : 'converted', $files, $single, $multi, $expressions);
printf("distinct roles referenced: %d\n", count($roleSet));

if ($skipped) {
    fwrite(STDERR, "!! @Security left in place - not a plain role check, so it needs reading:\n");
    foreach ($skipped as $s) fwrite(STDERR, "   $s\n");
}
