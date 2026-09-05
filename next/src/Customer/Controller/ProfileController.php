<?php

namespace App\Customer\Controller;
use Doctrine\ORM\EntityManagerInterface;
use App\DomainUser\Entity\Customer;
use App\DomainUser\Form\MemberEditProfileType;
use App\User\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;



/**
 * DomainUser controller.
 *
 */
class ProfileController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }




    /**
     * Finds and displays a DomainUser entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\User\Entity\User::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find DomainUser entity.');
        }
        return $this->render('Customer/Profile/show.html.twig', array(
            'user'      => $entity,
        ));
    }

    /**
     * Displays a form to edit an existing DomainUser entity.
     *
     */
    public function editAction()
    {
        $user = $this->getUser();
        $profile = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array('globalOption' => $user->getGlobalOption(),'user' => $user->getId()));
        $editForm = $this->createEditForm($profile);
        $globalOption = $this->getUser()->getGlobalOption();

        return $this->render('Customer/Student/editProfile.html.twig', array(
            'globalOption' => $globalOption,
            'entity'      => $profile,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Creates a form to edit a DomainUser entity.
     *
     * @param User $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(Customer $profile)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $em = $this->em->getRepository(\App\DomainUser\Entity\Customer::class);
        $form = $this->createForm(new MemberEditProfileType($em), $profile, array(
            'action' => $this->generateUrl('customerweb_profile_update', array('shop' => $globalOption->getSlug())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }
    /**
     * Edits an existing DomainUser entity.
     *
     */
    public function updateAction(Request $request)
    {
        $em = $this->em;
        $user = $this->getUser();
        $profile = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array('globalOption' => $user->getGlobalOption(),'user' => $user->getId()));
        if (!$profile) {
            throw $this->createNotFoundException('Unable to find customer entity.');
        }
        $editForm = $this->createEditForm($profile);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $profile->upload();
            $em->flush();
            return $this->redirect($this->generateUrl('customerweb_profile', array('shop' => $user->getGlobalOption()->getSlug())));
        }
        return $this->render('Customer/Student/editProfile.html.twig', array(
            'globalOption'      =>  $user->getGlobalOption(),
            'entity'            => $profile,
            'form'              => $editForm->createView(),

        ));
    }


  

}
