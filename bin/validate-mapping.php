<?php
/**
 * Loads every Doctrine metadata definition without touching a database.
 * This is the acceptance test for the entity-graph port: if all 335 entities
 * resolve their associations, the shared-DB strangler foundation is sound.
 */
require dirname(__DIR__) . '/vendor/autoload.php';

(new Symfony\Component\Dotenv\Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaValidator;

$kernel = new Kernel('dev', true);
$kernel->boot();
/** @var EntityManagerInterface $em */
$em = $kernel->getContainer()->get('doctrine')->getManager();

$factory = $em->getMetadataFactory();
$ok = 0; $errors = [];

try {
    $all = $factory->getAllMetadata();
} catch (\Throwable $e) {
    fwrite(STDERR, "FATAL loading metadata: " . $e->getMessage() . "\n");
    exit(2);
}

printf("loaded %d entity metadata definitions\n\n", count($all));

$validator = new SchemaValidator($em);
foreach ($all as $meta) {
    $problems = $validator->validateClass($meta);
    if ($problems) { $errors[$meta->getName()] = $problems; }
    else { $ok++; }
}

printf("%d entities valid, %d with mapping errors\n", $ok, count($errors));
if ($errors) {
    echo "\n--- mapping errors ---\n";
    $shown = 0;
    foreach ($errors as $class => $problems) {
        if ($shown++ >= 25) { printf("... and %d more classes\n", count($errors) - 25); break; }
        echo "\n$class\n";
        foreach ($problems as $p) echo "  - $p\n";
    }
}
exit($errors ? 1 : 0);
