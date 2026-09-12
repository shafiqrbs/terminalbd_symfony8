<?php
namespace App\DomainUser\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CulculationExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('gpPercentage', $this->gpPercentage(...)),
        ];
    }

    public function gpPercentage($number, $percentage = 8)
    {
        return round(($number * $percentage)/100);
    }

    public function productVat($number, $percentage = 15)
    {
        return round(($number * $percentage)/100);
    }

    public function getName()
    {
        // TODO: Implement getName() method.
    }
}