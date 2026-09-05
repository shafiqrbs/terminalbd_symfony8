<?php

namespace App\Accounting\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Accounting\Form\VendorType;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\RunAs;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Accounting\Entity\AccountVendor;
use Symfony\Component\HttpFoundation\Response;

/**
 * AccountVendor controller.
 *
 */
class VendorController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
    ) {
    }


    public function paginate($entities)
    {

        $paginator  = $this->paginator;
        $pagination = $paginator->paginate(
            $entities,
            $this->requestStack->getCurrentRequest()->query->get('page', 1)/*page number*/,
            25  /*limit per page*/
        );
        return $pagination;
    }

    #[IsGranted('ROLE_DOMAIN_ACCOUNTING_CONFIG')]


    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\Accounting\Entity\AccountVendor::class)->findWithSearch($globalOption,$data);
        $pagination = $this->paginate($entities);
        return $this->render('Accounting/AccountVendor/index.html.twig', array(
            'entities' => $pagination,
            'searchForm' => $data,
            // 'overview' => $overview,
        ));
    }

    /**
     * Creates a new AccountVendor entity.
     */
    #[IsGranted('ROLE_DOMAIN_ACCOUNTING_CONFIG')]

    public function createAction(Request $request)
    {
        $entity = new AccountVendor();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isValid()) {

            $em = $this->em;
            $entity->setGlobalOption($this->getUser()->getGlobalOption());
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('account_vendor'));
        }

        return $this->render('Accounting/AccountVendor/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a AccountVendor entity.
     *
     * @param AccountVendor $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(AccountVendor $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(VendorType::class, $entity, array(
            'action' => $this->generateUrl('account_vendor_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }

    /**
     * Displays a form to create a new AccountVendor entity.
     */
    #[IsGranted('ROLE_DOMAIN_ACCOUNTING_CONFIG')]

    public function newAction()
    {
        $em = $this->em;
        $entity = new AccountVendor();
        $form   = $this->createCreateForm($entity);
        return $this->render('Accounting/AccountVendor/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a AccountVendor entity.
     */
    #[IsGranted('ROLE_DOMAIN_ACCOUNTING_CONFIG')]

    public function showAction($id)
    {
    }

    /**
     * Displays a form to edit an existing AccountVendor entity.
     */
    #[IsGranted('ROLE_DOMAIN_ACCOUNTING_CONFIG')]

    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Accounting\Entity\AccountVendor::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountVendor entity.');
        }
        $editForm = $this->createEditForm($entity);
        return $this->render('Accounting/AccountVendor/new.html.twig',[
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }


    /**
     * Creates a form to edit a AccountVendor entity.
     *
     * @param AccountVendor $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(AccountVendor $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(VendorType::class, $entity, array(
            'action' => $this->generateUrl('account_vendor_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form purchase',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }


    /**
     * Edits an existing AccountVendor entity.
     */
    #[IsGranted('ROLE_DOMAIN_ACCOUNTING_CONFIG')]


    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Accounting\Entity\AccountVendor::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountVendor entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->em->getRepository(\App\Accounting\Entity\AccountVendor::class)->updateVendorCompanyName($entity);
            return $this->redirect($this->generateUrl('account_vendor'));
        }

        return $this->render('Accounting/AccountVendor/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Deletes a Expenditure entity.
     */
    #[IsGranted('ROLE_DOMAIN_ACCOUNTING_CONFIG')]

    public function deleteAction(AccountVendor $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountPurchase entity.');
        }
        $em->remove($entity);
        $em->flush();
        return new Response('success');
        exit;
    }

    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Request $request, $id)
    {

        $em = $this->em;
        $entity = $em->getRepository(\App\Accounting\Entity\AccountVendor::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find District entity.');
        }

        $status = $entity->isStatus();
        if($status == 1){
            $entity->setStatus(false);
        } else{
            $entity->setStatus(true);
        }
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Status has been changed successfully"
        );
        exit;

    }

    public function autoSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $global = $this->getUser()->getGlobalOption();
            $item = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->searchAutoComplete($item,$global);
        }
        return new JsonResponse($item);
    }

    public function searchVendorNameAction($name)
    {
        return new JsonResponse(array(
            'id' => $name,
            'text' => $name
        ));
    }

    public function ledgerAction()
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $type = $_REQUEST['type'];
        $vendor = $_REQUEST['vendor'];
        $balance = 0;
        if(!empty($vendor)){
            $result = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->vendorSingleOutstanding($globalOption,$type,$vendor);
            $balance = empty($result) ? 0 : $result;
        }
        $taka = number_format($balance).' Taka';
        return new Response($taka);
        exit;

    }

}
