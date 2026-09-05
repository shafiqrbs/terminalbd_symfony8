<?php

namespace App\Business\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Business\Entity\BusinessParticular;
use App\Business\Entity\BusinessStore;
use App\Business\Form\BusinessStoreLedgerType;
use App\Business\Form\BusinessStoreType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * BusinessStore controller.
 *u
 */
class BusinessStoreController extends AbstractController
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

    /**
     * Lists all BusinessStore entities.
     *
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]
    public function indexAction()
    {
        $global = $this->getUser()->getGlobalOption();
        $config = $global->getBusinessConfig();
        $em = $this->em;
        $data = $_REQUEST;
        $option = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository(\App\Business\Entity\BusinessStore::class)->invoiceLists($option,$data);
        $pagination = $this->paginate($entities);
        $areas = $this->em->getRepository(\App\Business\Entity\BusinessArea::class)->findBy(array('businessConfig' => $config,'status'=>1),array('name'=>"ASC"));
        $marketings = $this->em->getRepository(\App\Business\Entity\Marketing::class)->findBy(array('businessConfig' => $config,'status'=>1),array('name'=>"ASC"));
        $stores = $this->em->getRepository(\App\Business\Entity\BusinessStore::class)->findBy(array('businessConfig' => $config,'status'=>1),array('name'=>"ASC"));
        $customers = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findBy(array('globalOption' => $global),array('name'=>"ASC"));
        return $this->render('Business/BusinessStore/index.html.twig', array(
            'entities' => $pagination,
            'customers' => $customers,
            'marketings' => $marketings,
            'areas' => $areas,
            'stores' => $stores,
            'searchForm' => $data,
        ));
    }

    /**
     * Displays a form to create a new Vendor entity.
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]

    public function newAction()
    {
        $entity = new BusinessStore();
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $form   = $this->createCreateForm($entity);
        return $this->render('Business/BusinessStore/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }




    /**
     * Creates a new BusinessStore entity.
     *
     */
    public function createAction(Request $request)
    {
        $em = $this->em;
        $option = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entity = new BusinessStore();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $entity->setBusinessConfig($option);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('business_store', array('id' => $entity->getId())));
        }
        return $this->render('Business/BusinessStore/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a BusinessStore entity.
     *
     * @param BusinessStore $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(BusinessStore $entity)
    {
        $option = $this->getUser()->getGlobalOption();
        $form = $this->createForm(BusinessStoreType::class, $entity, array('action' => $this->generateUrl('business_store_create', array('id' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ), 'option' => $option));
        return $form;
    }

    /**
     * Creates a form to create a BusinessStore entity.
     *
     * @param BusinessStore $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createLedgerForm(BusinessStore $entity)
    {
        $option = $this->getUser()->getGlobalOption();
        $form = $this->createForm(BusinessStoreLedgerType::class, $entity, array('action' => $this->generateUrl('business_store_create', array('id' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ), 'option' => $option));
        return $form;
    }



    /**
     * Displays a form to edit an existing BusinessStore entity.
     *
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]
    public function editAction($id)
    {
        $em = $this->em;
        $option = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entity = $em->getRepository(\App\Business\Entity\BusinessStore::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find BusinessStore entity.');
        }
        $editForm = $this->createEditForm($entity);
        return $this->render('Business/BusinessStore/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Creates a form to edit a BusinessStore entity.
     *
     * @param BusinessStore $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(BusinessStore $entity )
    {
        $option = $this->getUser()->getGlobalOption();
        $form = $this->createForm(BusinessStoreType::class, $entity, array('action' => $this->generateUrl('business_store_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ), 'option' => $option));


        return $form;
    }
    /**
     * Edits an existing BusinessStore entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;
        $option = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entity = $em->getRepository(\App\Business\Entity\BusinessStore::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find BusinessStore entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('business_store'));
        }

        return $this->render('Business/BusinessStore/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }
    /**
     * Deletes a BusinessStore entity.
     *
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]
    public function deleteAction(BusinessStore $entity)
    {
        $em = $this->em;
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
        }catch (\Exception $e) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice', 'Please contact system administrator further notification.'
            );
        }
        return $this->redirect($this->generateUrl('business_area'));
    }

    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Request $request, $id)
    {

        $em = $this->em;
        $entity = $this->em->getRepository(\App\Business\Entity\BusinessStore::class)->find($id);

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
        return $this->redirect($this->generateUrl('business_area'));
    }



}
