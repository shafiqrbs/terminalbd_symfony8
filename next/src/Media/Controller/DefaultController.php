<?php

namespace App\Media\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DefaultController extends AbstractController
{
    public function indexAction($name)
    {
        return $this->render('Media/Default/index.html.twig', ['name' => $name]);
    }
}
