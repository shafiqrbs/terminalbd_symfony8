<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\User\Controller;

use Symfony\Component\Routing\RouterInterface;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\HttpFoundation\Response;
use RuntimeException;
use App\User\Service\MobileDetect;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContextInterface;

class SecurityController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly RouterInterface $router,
    ) {
    }


    public function loginAction(Request $request)
    {

        if($this->isGranted('IS_AUTHENTICATED_REMEMBERED')){
            return $this->redirect($this->generateUrl('homepage'));
        }
        /** @var $session \Symfony\Component\HttpFoundation\Session\Session */
        $session = $request->getSession();
        if (class_exists('\Symfony\Component\Security\Core\Security')) {
            $authErrorKey = SecurityRequestAttributes::AUTHENTICATION_ERROR;
            $lastUsernameKey = SecurityRequestAttributes::LAST_USERNAME;
        } else {
            // BC for SF < 2.6
            $authErrorKey = SecurityContextInterface::AUTHENTICATION_ERROR;
            $lastUsernameKey = SecurityContextInterface::LAST_USERNAME;
        }

        // get the error if any (works with forward and redirect -- see below)
        if ($request->attributes->has($authErrorKey)) {
            $error = $request->attributes->get($authErrorKey);
        } elseif (null !== $session && $session->has($authErrorKey)) {
            $error = $session->get($authErrorKey);
            $session->remove($authErrorKey);
        } else {
            $error = null;
        }
        if (!$error instanceof AuthenticationException) {
            $error = null; // The value does not come from the security component.
        }

        // last username entered by the user
        $lastUsername = (null === $session) ? '' : $session->get($lastUsernameKey);

        if ($this->has('security.csrf.token_manager')) {
            // TODO(migration): AbstractController::get() was removed; inject this service.
            $csrfToken = $this->csrfTokenManager->getToken('authenticate')->getValue();
        } else {
            // BC for SF < 2.4
            $csrfToken = $this->has('form.csrf_provider')
                // TODO(migration): AbstractController::get() was removed; inject this service.
                ? $this->csrfTokenManager->generateCsrfToken('authenticate')
                : null;
        }
        if ($error) {
            $error = $error->getMessage();
        }
	    $slides = $this->em->getRepository(\App\Content\Entity\SiteSlider::class)->findBy([],['id'=>'DESC']);
	    $apps =$this->em->getRepository(\App\Tool\Entity\AppModule::class)->findBy(['status'=>1]);
	    $clients =$this->em->getRepository(\App\Tool\Entity\GlobalOption::class)->findBySubdomain();
	    $testimonials =$this->em->getRepository(\App\Content\Entity\Testimonial::class)->findBy(['status'=>1]);
	    return $this->renderLogin([
            'last_username'         => $lastUsername,
            'error'                 => $error,
            'slides'                => $slides,
            'apps'                  => $apps,
            'testimonials'          => $testimonials,
            'clients'               => $clients,
            'csrf_token'            => $csrfToken,
        ]);
    }

    /**
     * Renders the login template with the given parameters. Overwrite this function in
     * an extended controller to provide additional data for the login template.
     *
     * @param array $data
     *
     * @return Response
     */
    protected function renderLogin(array $data)
    {

        $detect = new MobileDetect();
        $currentDomain = $_SERVER['SERVER_NAME'];
        return $this->render('User/Security/login.html.twig', $data);

    }

    public function domainLoginAction($subdomain,Request $request)
    {

        /** @var $session \Symfony\Component\HttpFoundation\Session\Session */
        $session = $request->getSession();
        if (class_exists('\Symfony\Component\Security\Core\Security')) {
            $authErrorKey = SecurityRequestAttributes::AUTHENTICATION_ERROR;
            $lastUsernameKey = SecurityRequestAttributes::LAST_USERNAME;
        } else {
            // BC for SF < 2.6
            $authErrorKey = SecurityContextInterface::AUTHENTICATION_ERROR;
            $lastUsernameKey = SecurityContextInterface::LAST_USERNAME;
        }
        // get the error if any (works with forward and redirect -- see below)
        if ($request->attributes->has($authErrorKey)) {
            $error = $request->attributes->get($authErrorKey);
        } elseif (null !== $session && $session->has($authErrorKey)) {
            $error = $session->get($authErrorKey);
            $session->remove($authErrorKey);
        } else {
            $error = null;
        }

        if (!$error instanceof AuthenticationException) {
            $error = null; // The value does not come from the security component.
        }

        // last username entered by the user
        $lastUsername = (null === $session) ? '' : $session->get($lastUsernameKey);

        if ($this->has('security.csrf.token_manager')) {
            // TODO(migration): AbstractController::get() was removed; inject this service.
            $csrfToken = $this->csrfTokenManager->getToken('authenticate')->getValue();
        } else {
            // BC for SF < 2.4
            $csrfToken = $this->has('form.csrf_provider')
                // TODO(migration): AbstractController::get() was removed; inject this service.
                ? $this->csrfTokenManager->generateCsrfToken('authenticate')
                : null;
        }
        if ($error) {
            $error = $error->getMessage();
        }

        $authChecker = $this->authorizationChecker;
        $router = $this->router;

       if (!empty($authChecker)) {
            return $this->redirect($this->generateUrl('landing_page'));
       }else{
            return new RedirectResponse($router->generate('homepage'), Response::HTTP_TEMPORARY_REDIRECT);
       }
    }

    /**
     * Renders the login template with the given parameters. Overwrite this function in
     * an extended controller to provide additional data for the login template.
     *
     * @param array $data
     *
     * @return Response
     */
    protected function renderDomainLogin(array $data,$themeName)
    {
        $detect = new MobileDetect();
        if( ! $detect->isMobile() && ! $detect->isTablet() ) {
            $theme = 'Template/Mobile/'.$themeName;
        }else{
            $theme = 'Template/Desktop/'.$themeName;
        }
        return $this->render('FrontendBundle:'.$theme.':login.html.twig', $data);
    }

    public function checkAction()
    {
        throw new RuntimeException('You must configure the check path to be handled by the firewall using form_login in your security firewall configuration.');
    }

    public function logoutAction()
    {

        throw new RuntimeException('You must activate the logout in your security firewall configuration.');
       // return $this->render('FrontendBundle:'.$theme.':login.html.twig', $data);
       // return $this->render('User/Security/login.html.twig', $data);

    }
}
