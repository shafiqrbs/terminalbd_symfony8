<?php

declare(strict_types=1);

namespace App\User\Controller;

use App\User\Form\UserType;
use App\User\Manager\UserManager;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Was FOSUserBundle's RegistrationController.
 *
 * registerAction is the application's own override, unchanged in behaviour:
 * the user is enabled on the spot and given ROLE_DOMAIN, so the confirmation
 * flow below is only reachable through a hand-typed URL. It is reproduced
 * anyway because the routes exist.
 *
 * The registration form type is the one app/config/bundles/fos_user.yml named
 * (`Core_userbundle_user` = App\User\Form\UserType), created under the name
 * FOSUserBundle's factory used.
 */
class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly UserManager $userManager,
    ) {
    }

    public function registerAction(Request $request)
    {
        $user = $this->userManager->createUser();
        $user->setEnabled(true);

        $form = $this->formFactory->createNamed('fos_user_registration_form', UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->addRole('ROLE_DOMAIN');
            $this->userManager->updateUser($user);

            return new RedirectResponse($this->generateUrl('fos_user_registration_confirmed'));
        }

        return $this->render('_fosuser/Registration/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function checkEmailAction(Request $request)
    {
        $session = $request->getSession();
        $email = $session->get('fos_user_send_confirmation_email/email');

        if (empty($email)) {
            return new RedirectResponse($this->generateUrl('fos_user_registration_register'));
        }

        $session->remove('fos_user_send_confirmation_email/email');

        return $this->render('_fosuser/Registration/checkEmail.html.twig', [
            'user' => $this->userManager->findUserByEmail($email),
        ]);
    }

    public function confirmAction(string $token)
    {
        $user = $this->userManager->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with confirmation token "%s" does not exist', $token));
        }

        $user->setConfirmationToken(null);
        $user->setEnabled(true);
        $user->setLastLogin(new DateTime());

        $this->userManager->updateUser($user);

        return new RedirectResponse($this->generateUrl('fos_user_registration_confirmed'));
    }

    public function confirmedAction()
    {
        return $this->render('_fosuser/Registration/confirmed.html.twig', [
            'user' => $this->getUser(),
        ]);
    }
}
