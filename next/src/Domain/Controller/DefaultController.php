<?php

namespace App\Domain\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Yaml\Yaml;

class DefaultController extends AbstractController
{
    public function indexAction()
    {
        return $this->render('Domain/index.html.twig', ['name' => 'mobile']);
    }

    public function educationHomeAction()
    {
        $domains = [
            [
                'resource' => '@FrontendBundle/Resources/config/routing/webservice.yml',
                'domain' => 'www.tlsbd.org',
                'subdomain' => 'tlsbd'
            ]
        ];

        $routes = [];

        foreach ($domains as $data)

            $routes['_domain_app_' . strtolower(str_replace('.', '_', $data['domain']))] = [
                'resource' => $data['resource'],
                'host' => $data['domain'],
                'name_prefix' => $data['subdomain'] . "_",
                'defaults' => [
                    'subdomain' => $data['subdomain']
                ]
            ];


        $routesString = Yaml::dump($routes);

        file_put_contents(realpath(WEB_PATH . "../app/config/dynamic/sites.yml"), $routesString);

        return $this->render('Domain/index.html.twig', ['name' => 'Education']);
    }

    public function educationAppHomeAction($subdomain = "")
    {
        return $this->render('Domain/index.html.twig', ['name' => "Education sub ". $subdomain]);
    }

    public function educationAppMobileHomeAction($subdomain = "")
    {
        return $this->render('Domain/index.html.twig', ['name' => "Education sub mobile ". $subdomain]);
    }

    public function ecomHomeAction()
    {
        return $this->render('Domain/index.html.twig', ['name' => 'Ecom']);
    }

    public function ecomAppHomeAction($subdomain = "")
    {
        return $this->render('Domain/index.html.twig', ['name' => "Ecom sub ". $subdomain]);
    }


    public function errorAction()
    {
        return $this->render('Domain/index.html.twig', ['name' => 'error']);
    }
}
