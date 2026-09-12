<?php

namespace App\Location\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DefaultController extends AbstractController
{
    public function indexAction($name)
    {
        return $this->render('Location/Default/index.html.twig', ['name' => $name]);
    }
}
