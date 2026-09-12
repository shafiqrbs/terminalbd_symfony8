<?php

namespace App\Product\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DefaultController extends AbstractController
{
    public function indexAction($name)
    {
        return $this->render('Product/Default/index.html.twig', ['name' => $name]);
    }
}
