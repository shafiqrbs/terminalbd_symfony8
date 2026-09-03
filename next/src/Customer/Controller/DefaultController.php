<?php

namespace App\Customer\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
class DefaultController extends AbstractController
{
    public function indexAction($name)
    {
        return $this->render('Customer/Default/index.html.twig', ['name' => $name]);
    }

}
