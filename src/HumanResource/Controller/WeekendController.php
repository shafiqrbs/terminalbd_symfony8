<?php

namespace App\HumanResource\Controller;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use App\HumanResource\Entity\Weekend;
use App\HumanResource\Form\WeekendType;
use Symfony\Component\HttpFoundation\Request;

/**
 * DomainUser controller.
 *
 */
class WeekendController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * Lists all Blackout entities.
     *
     */
    public function indexAction()
    {

        $option = $this->getUser()->getGlobalOption();
        $em = $this->em;

        $entity = $em->getRepository(\App\HumanResource\Entity\Weekend::class)->findOneBy(['globalOption' => $option]);
        if (empty($entity)) {
            $entity = new  Weekend();
            $em = $this->em;
            $entity->setGlobalOption($option);
            $em->persist($entity);
            $em->flush();
        }

        $blackout ='';
        $editForm = $this->createEditForm($entity);
        $blackOutDate =  $entity ->getWeekendDate();
        $blackoutdate = $blackOutDate ?? '';
        if($blackoutdate){
            $blackoutdate = (array_map(trim(...),array_filter(explode(',',$blackoutdate))));
            $blackout=implode("','",$blackoutdate);
            $blackout="'".$blackout."'";
        }
        return $this->render('HumanResource/Weekend/index.html.twig', [
            'entity'        => $entity,
            'form'          => $editForm->createView(),
            'blackout'      => $blackout,
        ]);


    }

    /**
     * Creates a form to edit a Blackout entity.
     *
     * @param Weekend $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(Weekend $entity)
    {

        $form = $this->createForm(WeekendType::class, $entity, [
            'action' => $this->generateUrl('weekend_update', ['id' => $entity->getId()]),
            'method' => 'POST',
            'attr' => [
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }
    /**
     * Edits an existing Blackout entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;
        $entity = $em->getRepository(\App\HumanResource\Entity\Weekend::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Weekend entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
        }
        return $this->redirect($this->generateUrl('weekend'));

    }

}
