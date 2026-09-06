<?php

declare(strict_types=1);

/**
 * Turns a route with placeholders into a path that can actually be requested.
 *
 * 1,783 of the routes carry a {placeholder}, and both harnesses skipped every
 * one of them - which left the majority of the application unvisited, including
 * every edit form and every show page. The ids have to come from the database:
 * an invented one answers 404 and tells you nothing.
 *
 * Finding out *which* entity an id belongs to is the whole problem, and there is
 * no single answer:
 *
 *   - 635 actions type-hint it, because the legacy application leaned on
 *     SensioFrameworkExtraBundle's ParamConverter. Reflection is enough.
 *   - 765 take a bare $id and look it up themselves. The entity is named in the
 *     first getRepository() call in the body, so that is read out of the source.
 *   - The rest are tokens, slugs and modes, handled by name where the meaning is
 *     obvious and skipped where it is not. A guess that 404s is worse than a
 *     skip, because it looks like a finding.
 *
 * Ids are drawn from the harness user's own tenant wherever the entity has a
 * globalOption, since most actions filter by it and a row from another tenant
 * comes back as a null the page did not expect - which reads exactly like a port
 * defect.
 *
 * Required by smoke-test.php and write-test.php; not a script in its own right.
 */

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Route;

final class RouteValues
{
    /** @var array<string, int|string|null> entity class -> an id that exists */
    private array $ids = [];

    /** @var array<string, string|null> route name -> the entity its id belongs to */
    private array $entities = [];

    private ?string $slug = null;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly int|string|null $globalOptionId = null,
    ) {
    }

    /**
     * A concrete path, or null when any placeholder cannot be filled honestly.
     */
    public function fill(string $name, Route $route): ?string
    {
        $path = $route->getPath();

        if (!preg_match_all('/\{(\w+)\}/', $path, $matches)) {
            return $path;
        }

        $controller = $route->getDefault('_controller');
        $entity = is_string($controller) ? $this->entityFor($name, $controller) : null;

        foreach ($matches[1] as $placeholder) {
            $value = $this->valueFor($placeholder, $entity, $controller);

            if ($value === null) {
                return null;
            }

            // Honour a requirement rather than produce a path that cannot match.
            $requirement = $route->getRequirement($placeholder);

            if ($requirement !== null && !preg_match('#^(?:' . $requirement . ')$#', (string) $value)) {
                return null;
            }

            $path = str_replace('{' . $placeholder . '}', rawurlencode((string) $value), $path);
        }

        return $path;
    }

    private function valueFor(string $placeholder, ?string $entity, mixed $controller): int|string|null
    {
        // A placeholder named after a type-hinted entity parameter.
        $named = is_string($controller) ? $this->typeHinted($controller, $placeholder) : null;

        if ($named !== null) {
            return $this->idFor($named);
        }

        return match (strtolower($placeholder)) {
            'id', 'entity' => $entity === null ? null : $this->idFor($entity),
            'page'         => 1,
            'year'         => (int) date('Y'),
            'month'        => (int) date('m'),
            'limit'        => 10,
            'shop', 'slug', 'domain' => $this->globalOptionSlug(),
            default        => $entity === null ? null : $this->idFor($entity),
        };
    }

    /** The entity class a route's id belongs to, or null. */
    private function entityFor(string $name, string $controller): ?string
    {
        if (array_key_exists($name, $this->entities)) {
            return $this->entities[$name];
        }

        $this->entities[$name] = null;

        if (!str_contains($controller, '::')) {
            return null;
        }

        [$class, $method] = explode('::', $controller, 2);

        if (!class_exists($class) || !method_exists($class, $method)) {
            return null;
        }

        $reflection = new ReflectionMethod($class, $method);

        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && $this->isEntity($type->getName())) {
                return $this->entities[$name] = $type->getName();
            }
        }

        // Not type-hinted: the action looks the row up itself, and the entity is
        // named in the first getRepository() call it makes.
        $file = $reflection->getFileName();

        if ($file === false) {
            return null;
        }

        $lines = file($file) ?: [];
        $body = implode('', array_slice(
            $lines,
            $reflection->getStartLine() - 1,
            $reflection->getEndLine() - $reflection->getStartLine() + 1
        ));

        if (!preg_match('/getRepository\(\s*\\\\?([A-Za-z0-9_\\\\]+)::class\s*\)/', $body, $m)) {
            return null;
        }

        $candidate = $m[1];

        if ($this->isEntity($candidate)) {
            return $this->entities[$name] = $candidate;
        }

        // A short name, resolved against the controller's own imports.
        if (!str_contains($candidate, '\\')) {
            $source = file_get_contents($file) ?: '';

            if (preg_match('/^use\s+([A-Za-z0-9_\\\\]+\\\\' . preg_quote($candidate, '/') . ')\s*;/m', $source, $u)
                && $this->isEntity($u[1])
            ) {
                return $this->entities[$name] = $u[1];
            }
        }

        return null;
    }

    private function typeHinted(string $controller, string $parameter): ?string
    {
        if (!str_contains($controller, '::')) {
            return null;
        }

        [$class, $method] = explode('::', $controller, 2);

        if (!class_exists($class) || !method_exists($class, $method)) {
            return null;
        }

        foreach ((new ReflectionMethod($class, $method))->getParameters() as $p) {
            if ($p->getName() !== $parameter) {
                continue;
            }

            $type = $p->getType();

            return $type instanceof ReflectionNamedType && $this->isEntity($type->getName())
                ? $type->getName()
                : null;
        }

        return null;
    }

    private function isEntity(string $class): bool
    {
        return str_starts_with($class, 'App\\')
            && class_exists($class)
            && !$this->em->getMetadataFactory()->isTransient($class);
    }

    /** An id that exists, from the harness user's tenant where the entity has one. */
    private function idFor(string $class): int|string|null
    {
        if (array_key_exists($class, $this->ids)) {
            return $this->ids[$class];
        }

        $this->ids[$class] = null;

        try {
            $meta = $this->em->getClassMetadata($class);

            if ($meta->isMappedSuperclass || count($meta->getIdentifierFieldNames()) !== 1) {
                return null;
            }

            $field = $meta->getSingleIdentifierFieldName();

            $scoped = null;

            if ($this->globalOptionId !== null && $meta->hasAssociation('globalOption')) {
                $scoped = $this->em->createQueryBuilder()
                    ->select('e.' . $field)->from($class, 'e')
                    ->where('e.globalOption = :option')
                    ->setParameter('option', $this->globalOptionId)
                    ->setMaxResults(1)
                    ->getQuery()->getOneOrNullResult();
            }

            $row = $scoped ?? $this->em->createQueryBuilder()
                ->select('e.' . $field)->from($class, 'e')
                ->setMaxResults(1)
                ->getQuery()->getOneOrNullResult();

            return $this->ids[$class] = is_array($row) ? reset($row) : $row;
        } catch (Throwable) {
            return null;
        }
    }

    private function globalOptionSlug(): ?string
    {
        if ($this->slug !== null) {
            return $this->slug;
        }

        try {
            $row = $this->em->createQueryBuilder()
                ->select('e.slug')->from(App\Tool\Entity\GlobalOption::class, 'e')
                ->where('e.id = :id')
                ->setParameter('id', $this->globalOptionId)
                ->setMaxResults(1)
                ->getQuery()->getOneOrNullResult();

            return $this->slug = is_array($row) ? (string) reset($row) : null;
        } catch (Throwable) {
            return null;
        }
    }
}
