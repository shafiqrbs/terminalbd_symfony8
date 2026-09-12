<?php

namespace App\Appearance\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DefaultController extends AbstractController
{
    public function indexAction($name)
    {
        return $this->render('Appearance/Default/index.html.twig', ['name' => $name]);
    }
}
