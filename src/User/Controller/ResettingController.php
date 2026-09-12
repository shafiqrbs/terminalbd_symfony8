<?php

declare(strict_types=1);

namespace App\User\Controller;

use App\Tool\Event\PasswordChangeSmsEvent;
use App\Tool\Service\ToolManager;
use App\User\Entity\Profile;
use App\User\Entity\User;
use App\User\Form\Type\ResettingFormType;
use App\User\Mailer\ResettingMailer;
use App\User\Manager\UserManager;
use App\User\Service\MobileDetect;
use App\User\Util\TokenGenerator;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Was FOSUserBundle's ResettingController.
 *
 * fos_user.user_manager, fos_user.util.token_generator and fos_user.mailer are
 * now App\User\Manager\UserManager, App\User\Util\TokenGenerator and
 * App\User\Mailer\ResettingMailer. The FOSUserEvents dispatches are dropped -
 * nothing subscribed to them.
 */
class ResettingController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly ToolManager $toolManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly FormFactoryInterface $formFactory,
        private readonly UserManager $userManager,
        private readonly TokenGenerator $tokenGenerator,
        private readonly ResettingMailer $mailer,
        private readonly int $tokenTtl,
    ) {
    }

    /**
     * Request reset user password: show form
     */
    public function requestAction()
    {
        $detect = new MobileDetect();
        $theme = ($detect->isMobile() || $detect->isTablet()) ? 'Resetting/Mobile' : 'Resetting';

        return $this->render('User/' . $theme . '/reset.html.twig');
    }

    /**
     * Request reset user password: submit form and send email
     */
    public function sendEmailAction(Request $request)
    {
        $username = (string) $request->request->get('username');

        $user = $this->userManager->findUserByUsernameOrEmail($username);

        if (null === $user) {
            return $this->render('_fosuser/Resetting/request.html.twig', [
                'invalid_username' => $username
            ]);
        }

        if ($user->isPasswordRequestNonExpired($this->tokenTtl)) {
            return $this->render('_fosuser/Resetting/passwordAlreadyRequested.html.twig');
        }

        if (null === $user->getConfirmationToken()) {
            $user->setConfirmationToken($this->tokenGenerator->generateToken());
        }

        $this->mailer->sendResettingEmailMessage($user);
        $user->setPasswordRequestedAt(new DateTime());
        $this->userManager->updateUser($user);

        return new RedirectResponse($this->generateUrl('fos_user_resetting_check_email',
            ['email' => $this->getObfuscatedEmail($user)]
        ));
    }

    /**
     * Tell the user to check his email provider
     */
    public function checkEmailAction(Request $request)
    {
        $email = $request->query->get('email');

        if (empty($email)) {
            // the user does not come from the sendEmail action
            return new RedirectResponse($this->generateUrl('fos_user_resetting_request'));
        }

        return $this->render('_fosuser/Resetting/checkEmail.html.twig', [
            'email' => $email,
        ]);
    }

    /**
     * Reset user password
     */
    public function resetAction(Request $request, $token)
    {
        $user = $this->userManager->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with "confirmation token" does not exist for value "%s"', $token));
        }

        // FOSUserBundle's form factory named this form; the template and the
        // POST body still use that name.
        $form = $this->formFactory->createNamed('fos_user_resetting_form', ResettingFormType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Clearing both is what FOSUserBundle's RESETTING_RESET_SUCCESS
            // listener did: without it the reset link stays usable.
            $user->setConfirmationToken(null);
            $user->setPasswordRequestedAt(null);
            $user->setEnabled(true);

            $this->userManager->updateUser($user);

            return new RedirectResponse($this->generateUrl('fos_user_profile_show'));
        }

        return $this->render('_fosuser/Resetting/reset.html.twig', [
            'token' => $token,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Get the truncated email displayed when requesting the resetting.
     *
     * The default implementation only keeps the part following @ in the address.
     */
    protected function getObfuscatedEmail(User $user): string
    {
        $email = (string) $user->getEmail();

        if (false !== $pos = strpos($email, '@')) {
            $email = '...' . substr($email, $pos);
        }

        return $email;
    }

    public function forgetPasswordAction(Request $request)
    {
        $intlMobile = $request->request->get('username');
        $mobile = $this->toolManager->specialExpClean($intlMobile);
        $profile = $this->em->getRepository(Profile::class)->findOneBy(['mobile' => $mobile]);

        if (empty($profile)) {

            $detect = new MobileDetect();
            $theme = ($detect->isMobile() || $detect->isTablet()) ? 'Resetting/Mobile' : 'Resetting';

            return $this->render('User/' . $theme . '/reset.html.twig', [
                'invalid_username' => $mobile
            ]);

        }

        $entity = $profile->getUser();
        $a = mt_rand(1000, 9999);
        $entity->setPlainPassword((string) $a);
        $this->userManager->updateUser($entity);
        $this->requestStack->getSession()->getFlashBag()->add(
            'success', "Send sms on your mobile"
        );
        $this->eventDispatcher->dispatch(new PasswordChangeSmsEvent($entity, $a), 'setting_tool.post.change_password');

        return $this->redirect($this->generateUrl('homepage'));
    }
}
