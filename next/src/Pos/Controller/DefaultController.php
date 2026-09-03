<?php

namespace App\Pos\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DefaultController extends AbstractController
{
    public function indexAction($name)
    {
        return $this->render('Pos/Default/index.html.twig', ['name' => $name]);
    }
}
