<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * The legacy front controller defined this on line 24 of web/index.php, and
 * nine places still build filesystem paths out of it - upload directories on
 * SiteSlider and ItemGallery among them. The Symfony 8 front controller is
 * Flex's, which defines nothing, so every one of them was an "Undefined
 * constant" fatal.
 *
 * Defined here rather than in public/index.php because the entity methods that
 * use it also run under bin/console and the migration harnesses, which never
 * go through a front controller. The Kernel is the one thing every context
 * loads first.
 */
defined('WEB_PATH') || define('WEB_PATH', dirname(__DIR__) . '/public');

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * @return list<string> An array of allowed values for APP_ENV
     */
    private function getAllowedEnvs(): array
    {
        return ['prod', 'dev', 'test'];
    }
}
