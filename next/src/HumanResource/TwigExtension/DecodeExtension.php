<?php
/**
 * Created by PhpStorm.
 * User: shafiq
 * Date: 5/26/19
 * Time: 4:05 PM
 */

namespace App\HumanResource\TwigExtension;


use Twig\Extension\AbstractExtension;
use Twig_Filter_Method;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DecodeExtension extends AbstractExtension
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'some.extension';
    }

    public function getFilters(): array {
        return [
            'json_decode'   => new Twig_Filter_Method($this, 'jsonDecode'),
        ];
    }

    public function jsonDecode($str) {
        return json_decode($str);
    }
}