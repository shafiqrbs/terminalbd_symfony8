<?php

namespace App\Customer\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\DomainUser\Entity\Customer;
use App\DomainUser\Form\MemberEditProfileType;
use App\Tool\Entity\Module;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MemberController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
    ) {
    }



    public function paginate($entities)
    {
        $paginator = $this->paginator;
        $pagination = $paginator->paginate(
            $entities,
            $this->requestStack->getCurrentRequest()->query->get('page', 1)/*page number*/,
            25  /*limit per page*/
        );
        $pagination->setTemplate('Tool/Widget/pagination.html.twig');
        return $pagination;
    }

    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $data['type'] = 'member';
        $data['process'] = 'Approved';
        $entities = $em->getRepository(\App\DomainUser\Entity\Customer::class)->findWithSearch($globalOption,$data);
        $pagination = $this->paginate($entities);
        $batches = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->studentBatchChoiceList();
        return $this->render('Customer/Member/index.html.twig', array(
            'globalOption' => $globalOption,
            'batches' => $batches,
            'entities' => $pagination,
            'searchForm' => $data,
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
        return $this->render('Customer/Member/editProfile.html.twig', array(
            'globalOption' => $globalOption,
            'entity'      => $profile,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing DomainUser entity.
     *
     */
    public function showAction()
    {
        $user = $this->getUser();
        $profile = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array('globalOption' => $user->getGlobalOption(),'user' => $user->getId()));
        $editForm = $this->createEditForm($profile);
        $globalOption = $this->getUser()->getGlobalOption();
        return $this->render('Customer/Member/show.html.twig', array(
            'globalOption' => $globalOption,
            'entity'      => $profile,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing DomainUser entity.
     *
     */
    public function memberProfileAction($customer)
    {
        $user = $this->getUser();
        $profile = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array('globalOption' => $user->getGlobalOption(),'customerId' => $customer));
        $editForm = $this->createEditForm($profile);
        $globalOption = $this->getUser()->getGlobalOption();
        return $this->render('Customer/Member/show.html.twig', array(
            'globalOption' => $globalOption,
            'entity'      => $profile,
            'form'   => $editForm->createView(),
        ));
    }



    /**
     * Creates a form to edit a DomainUser entity.
     *
     * @param Customer $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(Customer $profile)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $em = $this->em->getRepository(\App\DomainUser\Entity\Customer::class);
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into MemberEditProfileType::configureOptions() and pass them as form options: $em), $profile, array(
        $form = $this->createForm(new MemberEditProfileType($em), $profile, array(
            'action' => $this->generateUrl('member_profile_update', array('shop' => $globalOption->getSlug())),
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
            return $this->redirect($this->generateUrl('member_edit_profile', array('shop' => $user->getGlobalOption()->getSlug())));
        }
        return $this->render('Customer/Member/editProfile.html.twig', array(
            'globalOption'      =>  $user->getGlobalOption(),
            'entity'            => $profile,
            'form'              => $editForm->createView(),

        ));
    }


}
