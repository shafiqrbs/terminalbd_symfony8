<?php

namespace App\DomainUser\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DefaultController extends AbstractController
{
    public function indexAction($name)
    {
        return $this->render('DomainUser/Default/index.html.twig', ['name' => $name]);
    }
}
