<?php

namespace App\Accounting\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Accounting\Entity\AccountingConfig;
use App\Accounting\Entity\BusinessConfig;
use App\Accounting\Form\AccountConfigType;
use App\Accounting\Form\ConfigType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * BusinessConfigController.
 *
 */
class AccountConfigController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }



    public function manageAction()
    {


        $em = $this->em;
        $entity = $this->getUser()->getGlobalOption()->getAccountingConfig();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity preparation.');
        }
        $form = $this->createEditForm($entity);
        return $this->render('Accounting/Config/manage.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView(),
        ));
    }

    public function resetAction()
    {
        $em = $this->em;
        $entity = $this->getUser()->getGlobalOption()->getAccountingConfig();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity preparation.');
        }
        $globalOption = $this->getUser()->getGlobalOption();
        $this->em->getRepository(AccountingConfig::class)->accountingReset($globalOption);
        return $this->redirect($this->generateUrl('account_config_manage'));
    }

    /**
     * Creates a form to edit a Invoice entity.wq
     *
     * @param AccountingConfig $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(AccountingConfig $entity)
    {
        $config = $this->getUser()->getGlobalOption();
        $form = $this->createForm(AccountConfigType::class, $entity, array('action' => $this->generateUrl('account_config_update'),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'global' => $config));
        return $form;
    }

    /**
     * Edits an existing Particular entity.
     *
     */
    public function updateAction(Request $request)
    {
        $em = $this->em;

        $entity = $this->getUser()->getGlobalOption()->getAccountingConfig();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Report has been created successfully"
            );
            return $this->redirect($this->generateUrl('account_config_manage'));
        }

        return $this->render('Accounting/Config/manage.html.twig', array(
            'entity'        => $entity,
            'form'          => $editForm->createView(),
        ));
    }

    public function autoSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $go = $this->getUser()->getGlobalOption();
            $item = $this->em->getRepository(\App\User\Entity\User::class)->searchAutoComplete($item,$go);
        }
        return new JsonResponse($item);
    }

    public function searchUserNameAction($user)
    {
        return new JsonResponse(array(
            'id'=>$user,
            'text'=>$user
        ));
    }
}

