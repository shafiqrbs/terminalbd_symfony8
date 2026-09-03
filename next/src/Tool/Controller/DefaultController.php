<?php

namespace App\Tool\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DefaultController extends AbstractController
{
    public function indexAction($name)
    {
        return $this->render('Tool/Default/index.html.twig', ['name' => $name]);
    }
}
