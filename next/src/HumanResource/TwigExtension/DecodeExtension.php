<?php
/**
 * Created by PhpStorm.
 * User: shafiq
 * Date: 5/26/19
 * Time: 4:05 PM
 */

namespace App\HumanResource\TwigExtension;


use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DecodeExtension extends AbstractExtension
{
    public function getName()
    {
        return 'some.extension';
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('json_decode', $this->jsonDecode(...)),
        ];
    }

    public function jsonDecode($str)
    {
        return json_decode($str);
    }
}
