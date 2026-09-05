<?php

namespace App\Accounting\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Accounting\Entity\AccountBank;
use App\Accounting\Form\AccountBankType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * AccountBank controller.
 *
 */
class AccountBankController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }



    #[IsGranted('ROLE_DOMAIN_ACCOUNTING_CONFIG')]


    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\Accounting\Entity\AccountBank::class)->findWithSearch($globalOption,$data);

       // $this->em->getRepository(AccountBank::class)->selectBankRecords();
        return $this->render('Accounting/AccountBank/index.html.twig', array(
            'entities' => $entities,
            'searchForm' => $data,
           // 'overview' => $overview,
        ));
    }

    /**
     * Creates a new AccountBank entity.
     */
    #[IsGranted('ROLE_DOMAIN_ACCOUNTING_CONFIG')]

    public function createAction(Request $request)
    {
        $entity = new AccountBank();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isValid()) {

            $em = $this->em;
            $entity->setGlobalOption($this->getUser()->getGlobalOption());
            $name = $entity->getServiceName();
            $entity->setName($name);
            $entity->upload();
            $em->persist($entity);
            $em->flush();
            if($entity->isDefault() == 1){
                $this->em->getRepository(AccountBank::class)->setupDefault($entity);
            }
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->insertBankAccount($entity);
            return $this->redirect($this->generateUrl('accountbank'));
        }

        return $this->render('Accounting/AccountBank/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a AccountBank entity.
     *
     * @param AccountBank $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(AccountBank $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(AccountBankType::class, $entity, array('action' => $this->generateUrl('accountbank_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption));
      return $form;
    }

    /**
     * Displays a form to create a new AccountBank entity.
     */
    #[IsGranted('ROLE_DOMAIN_ACCOUNTING_CONFIG')]

    public function newAction()
    {
        $em = $this->em;
        $entity = new AccountBank();
        $form   = $this->createCreateForm($entity);
        return $this->render('Accounting/AccountBank/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a AccountBank entity.
     */
    #[IsGranted('ROLE_DOMAIN_ACCOUNTING_CONFIG')]

    public function showAction($id)
    {

        $em = $this->em;

        $entity = $em->getRepository(\App\Accounting\Entity\AccountBank::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountBank entity.');
        }
        return $this->render('Accounting/AccountBank/show.html.twig', array(
            'entity'      => $entity,
        ));

    }

    /**
     * Displays a form to edit an existing AccountBank entity.
     */
    #[IsGranted('ROLE_DOMAIN_ACCOUNTING_CONFIG')]

    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Accounting\Entity\AccountBank::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountBank entity.');
        }

        $editForm = $this->createEditForm($entity);
        return $this->render('Accounting/AccountBank/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }


    /**
    * Creates a form to edit a AccountBank entity.
    *
    * @param AccountBank $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(AccountBank $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(AccountBankType::class, $entity, array('action' => $this->generateUrl('accountbank_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form purchase',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption));
        return $form;
    }


    /**
     * Edits an existing AccountBank entity.
     */
    #[IsGranted('ROLE_DOMAIN_ACCOUNTING_CONFIG')]


    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Accounting\Entity\AccountBank::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountBank entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $name = $entity->getServiceName();
            $entity->setName($name);
            if($entity->getWebPath()){
                $entity->removeUpload();
            }
            $entity->upload();
            $em->flush();
            if($entity->isDefault() == 1){
                $this->em->getRepository(AccountBank::class)->setupDefault($entity);
            }
            $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->insertBankAccount($entity);
            return $this->redirect($this->generateUrl('accountbank'));
        }

        return $this->render('Accounting/AccountBank/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Deletes a Expenditure entity.
     */
    #[IsGranted('ROLE_DOMAIN_ACCOUNTING_CONFIG')]

    public function deleteAction(AccountBank $entity)
    {

        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountPurchase entity.');
        }
        try {

            $em->remove($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'error',"Data has been deleted successfully"
            );

        } catch (ForeignKeyConstraintViolationException $e) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice',"Data has been relation another Table"
            );
        }
        return $this->redirect($this->generateUrl('accountbank'));
    }

}
