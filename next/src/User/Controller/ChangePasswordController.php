<?php

declare(strict_types=1);

namespace App\User\Controller;

use App\User\Entity\User;
use App\User\Form\Type\ChangePasswordFormType;
use App\User\Manager\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Was FOSUserBundle's ChangePasswordController.
 *
 * The FOSUserEvents dispatches are gone: nothing in this application ever
 * listened to them (they existed for FOSUserBundle's own listeners, which no
 * longer exist), and no listener subscribes to them in either tree.
 *
 * The form is still created as `fos_user_change_password_form` because that is
 * the name FOSUserBundle's form factory used, and both the templates and the
 * POST body below read that key.
 */
class ChangePasswordController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FormFactoryInterface $formFactory,
        private readonly UserManager $userManager,
    ) {
    }

    /**
     * Change user password
     */
    public function changePasswordAction(Request $request)
    {
        $user = $this->requireUser();

        $form = $this->formFactory->createNamed(
            'fos_user_change_password_form',
            ChangePasswordFormType::class,
            $user,
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->userManager->updateUser($user);

            $data = $request->request->all();
            $password = $data['fos_user_change_password_form']['plainPassword']['first'];
            $user->setAppPassword($password);
            $this->em->flush();

            return new RedirectResponse($this->generateUrl('domain_change_password'));
        }

        return $this->render('User/ChangePassword/changePassword.html.twig', [
            'entity' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Change user password
     */
    public function customerChangePasswordAction(Request $request)
    {
        $user = $this->requireUser();

        $form = $this->formFactory->createNamed(
            'fos_user_change_password_form',
            ChangePasswordFormType::class,
            $user,
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->userManager->updateUser($user);

            return new RedirectResponse($this->generateUrl('customer_self_update_profile'));
        }

        return $this->render('User/ChangePassword/customerChangePassword.html.twig', [
            'globalOption' => '',
            'entity' => $user,
            'form' => $form->createView(),
        ]);
    }

    private function requireUser(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        return $user;
    }
}
