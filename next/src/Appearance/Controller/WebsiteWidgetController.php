<?php

namespace App\Appearance\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use JMS\SecurityExtraBundle\Annotation\Secure;
use App\Appearance\Entity\FeatureWidget;
use App\Appearance\Form\FeatureWidgetType;
use App\Appearance\Form\WebsiteWidgetType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;


/**
 * WebsiteWidgetController controller.
 *
 */
class WebsiteWidgetController extends Controller
{

    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_WEBSITE_WEDGET') or is_granted('ROLE_DOMAIN')"))]

    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\Appearance\Entity\FeatureWidget::class)->findBy(array('globalOption'=> $globalOption ,'widgetFor'=>'website'),array('updated'=>'DESC'));
        return $this->render('Appearance/WebsiteWidget/index.html.twig', array(
            'entities' => $entities,
        ));
    }

    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_WEBSITE_WEDGET') or is_granted('ROLE_DOMAIN')"))]
    public function sortingAction()
    {
        $em = $this->getDoctrine()->getManager();
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\Appearance\Entity\FeatureWidget::class)->findBy(array('globalOption'=> $globalOption ,'widgetFor'=>'website' ),array('sorting'=>'ASC'));
        return $this->render('Appearance/WebsiteWidget/sorting.html.twig', array(
            'entities' => $entities,
        ));
    }

    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_WEBSITE_WEDGET') or is_granted('ROLE_DOMAIN')"))]
    public function sortedListAction(Request $request)
    {
        $data = $request ->request->get('item');
        $this->getDoctrine()->getRepository(\App\Appearance\Entity\FeatureWidget::class)->setListOrdering($data);
        exit;
    }


    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_WEBSITE_WEDGET') or is_granted('ROLE_DOMAIN')"))]
    public function createAction(Request $request)
    {
        $entity = new FeatureWidget();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $data = $request->request->all();
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $user = $this->getUser();
            $entity->setGlobalOption($user->getGlobalOption());
            $entity->setPageName($entity->getMenu()->getSlug());
            $name = $entity->getPageName().'-'.$entity->getPosition();
            $entity->setName($name);
            $entity->setWidgetFor('website');

            $em->persist($entity);
            $em->flush();
            $this->getDoctrine()->getRepository(\App\Appearance\Entity\FeatureWidgetItem::class)->insert($entity,$data);

            if(!empty($entity->getJsFeature()) and $entity->getJsFeature()->getSlug() == 'feature'){
                return $this->redirect($this->generateUrl('appearancewebsitewidget_feature',array('id'=>$entity->getId())));
            }else{
                return $this->redirect($this->generateUrl('appearancewebsitewidget'));
            }

        }
        $global = $this->getUser()->getGlobalOption();
        $features = $this->getDoctrine()->getRepository(\App\Appearance\Entity\Feature::class)->findBy(array('globalOption' => $global));


        return $this->render('Appearance/WebsiteWidget/new.html.twig', array(
            'entity' => $entity,
            'features' => $features,
            'featureIds'      => '',
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a FeatureWidget entity.
     *
     * @param FeatureWidget $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(FeatureWidget $entity)
    {

        $globalOption = $this->getUser()->getGlobalOption();
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into WebsiteWidgetType::configureOptions() and pass them as form options: $globalOption), $entity, array(
        $form = $this->createForm(new WebsiteWidgetType($globalOption), $entity, array(
            'action' => $this->generateUrl('appearancewebsitewidget_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }

    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_WEBSITE_WEDGET') or is_granted('ROLE_DOMAIN')"))]
    public function newAction()
    {
        $entity = new FeatureWidget();
        $form   = $this->createCreateForm($entity);
        $global = $this->getUser()->getGlobalOption();
        $features = $this->getDoctrine()->getRepository(\App\Appearance\Entity\Feature::class)->findBy(array('globalOption' => $global));
        return $this->render('Appearance/WebsiteWidget/new.html.twig', array(
            'entity'    => $entity,
            'features'  => $features,
            'featureIds'      => '',
            'form'      => $form->createView(),
        ));
    }

    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_WEBSITE_WEDGET') or is_granted('ROLE_DOMAIN')"))]
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Appearance\Entity\FeatureWidget::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find FeatureWidget entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Appearance/WebsiteWidget/show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_WEBSITE_WEDGET') or is_granted('ROLE_DOMAIN')"))]
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Appearance\Entity\FeatureWidget::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find FeatureWidget entity.');
        }
        $global = $this->getUser()->getGlobalOption();
        $editForm = $this->createEditForm($entity);
        $features = $this->getDoctrine()->getRepository(\App\Appearance\Entity\Feature::class)->findBy(array('globalOption' => $global));
        $featureIds = array();
        foreach ($entity->getFeatureWidgetItems() as $row ){
            $featureIds[] = $row->getFeature()->getId();;
        }
        return $this->render('Appearance/WebsiteWidget/new.html.twig', array(
            'entity'      => $entity,
            'features'      => $features,
            'featureIds'      => $featureIds,
            'form'   => $editForm->createView(),
        ));
    }


    /**
     * Creates a form to edit a FeatureWidget entity.
     *
     * @param FeatureWidget $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(FeatureWidget $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $category = $this->getDoctrine()->getRepository(\App\Product\Entity\Category::class);
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into WebsiteWidgetType::configureOptions() and pass them as form options: $globalOption,$category), $entity, array(
        $form = $this->createForm(new WebsiteWidgetType($globalOption,$category), $entity, array(
            'action' => $this->generateUrl('appearancewebsitewidget_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }
    /**
     * Edits an existing FeatureWidget entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(\App\Appearance\Entity\FeatureWidget::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find FeatureWidget entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        $data = $request->request->all();
        if ($editForm->isValid()) {

            $entity->setPageName($entity->getMenu()->getSlug());
            $name = $entity->getPageName().'-'.$entity->getPosition();
            $entity->setName($name);
            $em->flush();
            $this->getDoctrine()->getRepository(\App\Appearance\Entity\FeatureWidgetItem::class)->update($entity,$data);
            return $this->redirect($this->generateUrl('appearancewebsitewidget_edit', array('id' => $id)));

        }
        $global = $this->getUser()->getGlobalOption();
        $features = $this->getDoctrine()->getRepository(\App\Appearance\Entity\Feature::class)->findBy(array('globalOption' => $global));


        return $this->render('Appearance/WebsiteWidget/new.html.twig', array(
            'entity'      => $entity,
            'features'      => $features,
            'featureIds'      => '',
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }


    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_WEBSITE_WEDGET') or is_granted('ROLE_DOMAIN')"))]
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $globalOption = $this->getUser()->getGlobalOption();
        $entity = $em->getRepository(\App\Appearance\Entity\FeatureWidget::class)->findOneBy(array('globalOption'=>$globalOption,'id'=>$id));
        if (!empty($entity)) {
            $em->remove($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been deleted successfully"
            );
        }else{
            $this->get('session')->getFlashBag()->add(
                'error',"Sorry! Data not deleted"
            );
        }
        return $this->redirect($this->generateUrl('appearancewebsitewidget'));
    }

    /**
     * Creates a form to delete a FeatureWidget entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('appearancewebsitewidget_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
            ;
    }

    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_WEBSITE_WEDGET') or is_granted('ROLE_DOMAIN')"))]
    public function statusAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(\App\Appearance\Entity\FeatureWidget::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find District entity.');
        }

        $status = $entity->getStatus();
        if($status == 1){
            $entity->setStatus(0);
        } else{
            $entity->setStatus(1);
        }
        $em->flush();
        $this->get('session')->getFlashBag()->add(
            'error',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('appearancewebsitewidget'));
    }

    public function  featureAction(FeatureWidget $entity){

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find FeatureWidget entity.');
        }
        return $this->render('Appearance/WebsiteWidget/feature.html.twig', array(
            'entity'      => $entity,
        ));
    }

    public function sortedAction(Request $request)
    {
        $data = $request ->request->get('item');
        $this->getDoctrine()->getRepository(\App\Appearance\Entity\FeatureWidget::class)->setDivOrdering($data);
        exit;

    }

    public function resizeAction(Request $request)
    {
        $data = $request ->request->all();
        $this->getDoctrine()->getRepository(\App\Appearance\Entity\FeatureWidget::class)->setDivResize($data);
        exit;
    }
}
