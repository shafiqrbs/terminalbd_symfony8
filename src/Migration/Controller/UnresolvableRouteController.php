<?php

declare(strict_types=1);

namespace App\Migration\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Answers the routes whose controller or action exists in neither tree.
 *
 * There are ~400 of them and each is a 500 in the legacy application today
 * (see migration/contract/unresolvable-routes.md). Deleting the routes outright
 * is worse than leaving them: 93 are still linked from templates, and a
 * path('order') for a route that no longer exists throws while rendering a page
 * that otherwise works. So the route keeps its name and path, and answers 404.
 *
 * Restoring a controller is what removes a route from this list - the pipeline
 * re-derives it on every run.
 */
final class UnresolvableRouteController
{
    public function __invoke(string $_route = '', string $missing = ''): never
    {
        throw new NotFoundHttpException(sprintf(
            'The route "%s" has no controller: %s exists in neither the legacy nor the ported tree. '
            . 'See migration/contract/unresolvable-routes.md.',
            $_route,
            $missing !== '' ? '"' . $missing . '"' : 'its controller',
        ));
    }
}
