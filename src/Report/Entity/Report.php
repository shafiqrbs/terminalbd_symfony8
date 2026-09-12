<?php


namespace App\Report\Entity;

use App\Report\Repository\ReportRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Sales
 */
#[ORM\Table(name: 'mis_report')]
#[ORM\Entity(repositoryClass: ReportRepository::class)]
class Report
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

}

