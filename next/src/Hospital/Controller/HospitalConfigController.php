<?php

namespace App\Hospital\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Hospital\Entity\HospitalConfig;
use App\Hospital\Entity\Invoice;
use App\Hospital\Entity\InvoiceParticular;
use App\Hospital\Entity\InvoicePathologicalReport;
use App\Hospital\Entity\Particular;
use App\Hospital\Form\ConfigType;
use App\Hospital\Form\InvoiceParticularType;
use App\Hospital\Form\InvoiceType;
use CodeItNow\BarcodeBundle\Utils\BarcodeGenerator;
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
class HospitalConfigController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }



    /**
     * ;
     */
    #[IsGranted('ROLE_DOMAIN')]

    public function manageAction()
    {

        $entity = $this->getUser()->getGlobalOption()->getHospitalConfig();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity preparation.');
        }
        $form = $this->createEditForm($entity);
        return $this->render('Hospital/Config/manage.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView(),
        ));
    }

    /**
     * Creates a form to edit a Invoice entity.wq
     *
     * @param HospitalConfig $entity The entity
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(HospitalConfig $entity)
    {

        $hospitalConfig = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $form = $this->createForm(ConfigType::class, $entity, array(
            'action' => $this->generateUrl('hms_config_update'),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }

    /**
     * Edits an existing Particular entity.
     * ;
     */
    #[IsGranted('ROLE_DOMAIN')]
    public function updateAction(Request $request)
    {
        $em = $this->em;

        /** @var  $entity HospitalConfig */
        $entity = $this->getUser()->getGlobalOption()->getHospitalConfig();

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            if($entity->HeaderUpload() && !empty($entity->getHeaderFile())){
                $entity->removeHeaderUpload();
            }
            if($entity->footerUpload() && !empty($entity->getFooterFile())){
                $entity->removeFooterUpload();
            }
            $entity->headerUpload();
            $entity->footerUpload();
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Report has been created successfully"
            );

            return $this->redirect($this->generateUrl('hms_config_manage'));
        }

        return $this->render('Hospital/Config/manage.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

}

