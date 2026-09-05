<?php

namespace App\Restaurant\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Restaurant\Entity\RestaurantConfig;
use App\Restaurant\Form\ConfigType;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\RunAs;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * HospitalConfigController.
 *
 */
class RestaurantConfigController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all Particular entities.
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_RESTAURANT_MANAGER') or is_granted('ROLE_DOMAIN')"))]

    public function manageAction()
    {

        $entity = $this->getUser()->getGlobalOption()->getRestaurantConfig();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity preparation.');
        }
        $form = $this->createEditForm($entity);
        return $this->render('Restaurant/Config/manage.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView(),
        ));
    }

    /**
     * Creates a form to edit a Invoice entity.wq
     *
     * @param Invoice $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(RestaurantConfig $entity)
    {

        $config = $this->getUser()->getGlobalOption()->getRestaurantConfig();
        $form = $this->createForm(ConfigType::class, $entity, array(
            'action' => $this->generateUrl('restaurant_config_update'),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }

    /**
     * Lists all Particular entities.
     */
    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_RESTAURANT_MANAGER') or is_granted('ROLE_DOMAIN')"))]

    public function updateAction(Request $request)
    {
        $em = $this->em;

        $entity = $this->getUser()->getGlobalOption()->getRestaurantConfig();

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

            return $this->redirect($this->generateUrl('restaurant_config_manage'));
        }

        return $this->render('Restaurant/Config/manage.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

}

