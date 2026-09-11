<?php

declare(strict_types=1);

/**
 * What each Twig screen's FORM asks for, read from its Symfony form type.
 *
 * 84 screens carry an Add or New action and 81 of them cannot render it: the
 * client offers an Add button only where the resource declares `formFields`, and
 * only 29 of 284 resources do. The same gap leaves 8 screens whose original IS a
 * form - "Add Expense", "New Journal" - with nothing to render at all.
 *
 * The fields are not in the template. `{{ form_widget(form) }}` renders whatever
 * the form type built, so the answer is in `ExpenditureType::buildForm` and its
 * 361 siblings, as `->add('amount', TextType::class, [...])`.
 *
 * Entities are the join. A form type names its `data_class`, a resource names
 * its `entity`, and where exactly one form type builds a given entity the match
 * is unambiguous. Where several do - a create form and a narrower edit form on
 * the same entity - this reports them all and declares nothing, because picking
 * one by name would put the wrong fields on a screen.
 *
 * The form type's OWN type is recorded rather than mapped here. Whether
 * `TextType` on `amount` means a string or a decimal is a question about the
 * column, and Doctrine knows; the consumer decides.
 *
 * usage: php8.5 migration/bin/form-fields.php [--out=form-fields.json]
 */

$root = dirname(__DIR__, 2);
$out = null;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--out=')) {
        $out = substr($arg, 6);
    }
}

$directory = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/next/src'));
$byEntity = [];
$scanned = 0;

foreach ($directory as $file) {
    if (!$file->isFile() || !str_ends_with($file->getFilename(), 'Type.php')) {
        continue;
    }

    if (!str_contains($file->getPathname(), '/Form/')) {
        continue;
    }

    $source = (string) file_get_contents($file->getPathname());

    if (!str_contains($source, 'buildForm')) {
        continue;
    }

    $scanned++;

    // `'data_class' => Expenditure::class` names the entity, and the `use`
    // statements say which Expenditure that is.
    if (preg_match("/'data_class'\s*=>\s*([A-Za-z0-9_\\\\]+)::class/", $source, $dm) !== 1) {
        continue;
    }

    $short = $dm[1];
    $entity = $short;

    if (preg_match('/^use\s+([A-Za-z0-9_\\\\]+\\\\' . preg_quote($short, '/') . ');/m', $source, $um) === 1) {
        $entity = $um[1];
    } elseif (preg_match('/^namespace\s+([A-Za-z0-9_\\\\]+);/m', $source, $nm) === 1 && !str_contains($short, '\\')) {
        $entity = $nm[1] . '\\' . $short;
    }

    // Only the builder's own adds: a form type that embeds another would
    // otherwise contribute its child's fields to this entity.
    $builderStart = strpos($source, 'buildForm');
    $body = $builderStart === false ? $source : substr($source, $builderStart);

    preg_match_all(
        "/->add\(\s*'([a-zA-Z0-9_]+)'\s*(?:,\s*([A-Za-z0-9_]+)::class)?(.*?)(?=->add\(|;\s*$|\n\s*}\s*$)/s",
        $body,
        $matches,
        PREG_SET_ORDER
    );

    $fields = [];

    foreach ($matches as $match) {
        $name = $match[1];
        $type = $match[2] ?? '';
        $options = $match[3] ?? '';

        // `'required' => false` is the only reliable signal; Symfony's default
        // is true and most of these never say so.
        $required = !preg_match("/'required'\s*=>\s*false/", $options);

        $target = null;

        if ($type === 'EntityType' && preg_match("/'class'\s*=>\s*([A-Za-z0-9_\\\\]+)::class/", $options, $cm) === 1) {
            $target = $cm[1];
        }

        $fields[$name] = ['name' => $name, 'formType' => $type ?: null, 'required' => $required, 'target' => $target];
    }

    if ($fields === []) {
        continue;
    }

    $byEntity[$entity][] = [
        'type' => str_replace($root . '/', '', $file->getPathname()),
        'fields' => array_values($fields),
    ];
}

$unambiguous = 0;
$ambiguous = 0;

foreach ($byEntity as $forms) {
    if (count($forms) === 1) {
        $unambiguous++;
    } else {
        $ambiguous++;
    }
}

printf(
    "%d form types scanned, %d entities: %d with one form type, %d with several\n",
    $scanned,
    count($byEntity),
    $unambiguous,
    $ambiguous
);

if ($out !== null) {
    file_put_contents($out, json_encode($byEntity, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "wrote {$out}\n";
}
