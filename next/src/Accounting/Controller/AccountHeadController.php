<?php

namespace App\Accounting\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use App\Accounting\Entity\AccountHead;
use App\Accounting\Form\AccountHeadSubType;
use App\Accounting\Form\AccountHeadType;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AccountHead controller.
 *
 */
class AccountHeadController extends AbstractController
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
           100  /*limit per page*/
        );
        return $pagination;
    }

    /**
     * Lists all AccountHead entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
	    $data = $_REQUEST;
	    $global = $this->getUser()->getGlobalOption();
        $accountHead = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->findBy(['isParent' => 1,'status' => 1],['name'=>'ASC']);
        $heads = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->getAllChildrenAccount($global->getId());
        return $this->render('Accounting/AccountHead/index.html.twig', [
            'accountHead' => $accountHead,
            'heads' => $heads,
            'searchForm'  => $data,
        ]);
    }


    /**
     * Creates a new AccountHead entity.
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_JOURNAL') or is_granted('ROLE_DOMAIN_ACCOUNTING')"))]
    public function createAction(Request $request)
    {
        $entity = new AccountHead();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $global = $this->getUser()->getGlobalOption();
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity->setGlobalOption($global);
            $entity->setCode($entity->getCode());
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('accounthead_new', ['id' => $entity->getId()]));
        }

        return $this->render('Accounting/AccountHead/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a AccountHead entity.
     *
     * @param AccountHead $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(AccountHead $entity)
    {
        $form = $this->createForm(AccountHeadSubType::class, $entity, [
            'action' => $this->generateUrl('accounthead_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }

    /**
     * Displays a form to create a new AccountHead entity.
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_JOURNAL') or is_granted('ROLE_DOMAIN_ACCOUNTING')"))]
    public function newAction()
    {
        $entity = new AccountHead();
        $form   = $this->createCreateForm($entity);
        return $this->render('Accounting/AccountHead/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a AccountHead entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Accounting\Entity\AccountHead::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountHead entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Accounting/AccountHead/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing AccountHead entity.
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_JOURNAL') or is_granted('ROLE_DOMAIN_ACCOUNTING')"))]
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Accounting\Entity\AccountHead::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountHead entity.');
        }

        $editForm = $this->createEditForm($entity);
        return $this->render('Accounting/AccountHead/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a AccountHead entity.
     *
     * @param AccountHead $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(AccountHead $entity)
    {
        $form = $this->createForm(AccountHeadType::class, $entity, [
            'action' => $this->generateUrl('accounthead_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'form-horizontal purchase',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }
    /**
     * Edits an existing AccountHead entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Accounting\Entity\AccountHead::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountHead entity.');
        }
        
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $entity->setSlug($entity->getName());
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('accounthead'));
        }

        return $this->render('Accounting/AccountHead/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }
    /**
     * Deletes a AccountHead entity.
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_JOURNAL') or is_granted('ROLE_DOMAIN_ACCOUNTING')"))]
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity = $em->getRepository(\App\Accounting\Entity\AccountHead::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find AccountHead entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('accounthead'));
    }

    /**
     * Creates a form to delete a AccountHead entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('accounthead_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
            ->getForm()
        ;
    }

	public function parentSelectAction()
	{

		$accountHeads = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->findBy(['isParent'=>1],['name'=>'ASC']);
		$items      = [];
		$items[]    = ['value' => '','text'=> '---Select Parent Head---'];
		foreach ($accountHeads as $entity):
			$items[]=['value' => $entity->getId(),'text'=> $entity->getName()];
		endforeach;
		return new JsonResponse($items);

	}

    public function subAccountSelectAction($id)
    {
        $global = $this->getUser()->getGlobalOption();
        $accountHeads = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->findBy(['parent' => $id , 'globalOption'=> $global , 'status'=>1],['name'=>'ASC']);
        $items      = '';
        $items    .="<option value=''>--Choose Sub-account Head--</option>";
        foreach ($accountHeads as $entity):
            $items .="<option value='{$entity->getId()}'>{$entity->getName()}</option>";
        endforeach;
        return new Response($items);

    }

    public function inlineUpdateAction(Request $request)
	{
		$data = $request->request->all();
		$em = $this->em;
		$entity = $em->getRepository(\App\Accounting\Entity\AccountHead::class)->find($data['pk']);
		if (!$entity) {
			throw $this->createNotFoundException('Unable to find AccountHead entity.');
		}
		if($data['name'] == 'code'){
			$entity->setCode($data['value']);
		}
		if($data['name'] == 'name'){
			$entity->setName($data['value']);
		}
		if($data['name'] == 'parent'){
			$parent = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->find($data['value']);
			$entity->setParent($parent);
		}
		$em->flush();
		exit;
	}

}
