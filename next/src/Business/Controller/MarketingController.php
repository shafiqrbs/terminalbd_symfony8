<?php

namespace App\Business\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Exception;
use Symfony\Component\Form\Form;
use App\Business\Entity\Marketing;
use App\Business\Form\MarketingType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * Marketing controller.
 *u
 */
class MarketingController extends AbstractController
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
    public function ledgerAction()
    {

        $data = $_REQUEST;
        $global = $this->getUser()->getGlobalOption();
        $config = $global->getBusinessConfig();
        $em = $this->em;
        $option = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository(\App\Business\Entity\BusinessInvoice::class)->srLedger($option,$data);
        $pagination = $this->paginate($entities);
        $marketings = $this->em->getRepository(\App\Business\Entity\Marketing::class)->findBy(['businessConfig' => $config,'status'=>1],['name'=>"ASC"]);
        return $this->render('Business/Marketing/ledger.html.twig', [
            'entities' => $pagination,
            'marketings' => $marketings,
            'searchForm' => $data,
        ]);
    }

    /**
     * Lists all Marketing entities.
     *
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]
    public function indexAction()
    {

        $entity = new Marketing();
        $em = $this->em;
        $option = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository(\App\Business\Entity\Marketing::class)->findBy(['businessConfig' => $option],[ 'name' =>'asc' ]);
        $form   = $this->createCreateForm($entity);
        return $this->render('Business/Marketing/index.html.twig', [
            'entity' => $entity,
            'entities' => $entities,
            'form'   => $form->createView(),
        ]);
    }

    public function updateSalesTargetAction(Request $request)
    {
        $em = $this->em;
        $data = $_REQUEST;
        foreach ($data['user'] as $key => $value){
            $user = $this->em->getRepository(Marketing::class)->find($value);
            $user->setMonthlySales($data['monthlySales'][$key]);
            $user->setYearlySales($data['yearlySales'][$key]);
            $user->setDiscount($data['discount'][$key]);
            $em->persist($user);
        }
        $em->flush();
        return $this->redirect($request->headers->get('referer'));
    }




    /**
     * Creates a new Marketing entity.
     *
     */
    public function createAction(Request $request)
    {
        $em = $this->em;
        $option = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository(\App\Business\Entity\Marketing::class)->findBy(['businessConfig' => $option],[ 'name' =>'asc' ]);

        $entity = new Marketing();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entity->setBusinessConfig($option);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('business_marketing'));
        }
        return $this->render('Business/Marketing/index.html.twig', [
            'entity' => $entity,
            'entities'      => $entities,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a Marketing entity.
     *
     * @param Marketing $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(Marketing $entity)
    {
        $form = $this->createForm(new MarketingType(), $entity, [
            'action' => $this->generateUrl('business_marketing_create', ['id' => $entity->getId()]),
            'method' => 'POST',
            'attr' => [
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }



    /**
     * Displays a form to edit an existing Marketing entity.
     *
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]
    public function editAction($id)
    {
        $em = $this->em;
        $option = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository(\App\Business\Entity\Marketing::class)->findBy(['businessConfig' => $option],[ 'name' =>'asc' ]);

        $entity = $em->getRepository(\App\Business\Entity\Marketing::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Marketing entity.');
        }
        $editForm = $this->createEditForm($entity);

        return $this->render('Business/Marketing/index.html.twig', [
            'entity'      => $entity,
            'entities'      => $entities,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a Marketing entity.
     *
     * @param Marketing $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(Marketing $entity )
    {
        $form = $this->createForm(new MarketingType(), $entity, [
            'action' => $this->generateUrl('business_marketing_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ]
        ]);


        return $form;
    }
    /**
     * Edits an existing Marketing entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;
        $option = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository(\App\Business\Entity\Marketing::class)->findBy(['businessConfig' => $option],[ 'name' =>'asc' ]);
        $entity = $em->getRepository(\App\Business\Entity\Marketing::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Marketing entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('business_marketing'));
        }

        return $this->render('Business/Marketing/index.html.twig', [
            'entity'      => $entity,
            'entities'      => $entities,
            'form'   => $editForm->createView(),
        ]);
    }
    /**
     * Deletes a Marketing entity.
     *
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]
    public function deleteAction(Marketing $entity)
    {
        $em = $this->em;
        try {

            $em->remove($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'error',"Data has been deleted successfully"
            );

        } catch (ForeignKeyConstraintViolationException) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice',"Data has been relation another Table"
            );
        }catch (Exception) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice', 'Please contact system administrator further notification.'
            );
        }
        return $this->redirect($this->generateUrl('business_marketing'));
    }

    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Request $request, $id)
    {

        $em = $this->em;
        $entity = $this->em->getRepository(\App\Business\Entity\Marketing::class)->find($id);

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
        return $this->redirect($this->generateUrl('business_marketing'));
    }



}
