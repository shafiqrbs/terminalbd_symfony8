<?php

namespace App\Content\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DefaultController extends AbstractController
{
    public function indexAction()
    {
        return $this->render('Content/Default/index.html.twig', ['name' => 'Text']);
    }
}
