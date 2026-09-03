<?php

namespace App\HumanResource\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DefaultController extends AbstractController
{
    public function indexAction($name)
    {
        return $this->render('HumanResource/Default/index.html.twig', ['name' => $name]);
    }
}
