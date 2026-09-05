<?php

namespace App\DoctorPrescription\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\DoctorPrescription\Entity\DpsParticular;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Medicine\Entity\DiagnosticReport;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\DoctorPrescription\Entity\DpsInvoice;
use App\DomainUser\Form\PatientForPrescriptionType;
use App\Medicine\Repository\DiagnosticReportRepository;
use Doctrine\ORM\EntityRepository;
use App\Location\Repository\LocationRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class InvoiceType extends AbstractType
{
    protected $globalOption;
    protected $location;
    protected $diagnostic;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];
        $this->location = $options['location'];
        $this->diagnostic = $options['diagnostic'];

        $builder
            ->add('diseasesProfile', EntityType::class, [
                'required' => true,
                'class' => DpsParticular::class,
                'choice_label' => 'name',
                'multiple' => false,
                'expanded' => false,
                'attr' => ['class' => 'm-wrap span12'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.mode = 'diseases'")
                    ->andWhere('e.dpsConfig =:dpsConfig')
                    ->setParameter('dpsConfig', $this->globalOption->getDpsConfig()->getId())
                    ->orderBy("e.name", "ASC")
            ])
            ->add('process', ChoiceType::class, [
                'attr' => ['class' => 'm-wrap invoiceProcess select-custom'],
                'expanded' => false,
                'multiple' => false,
                'placeholder' => '---Choose process---',
                'choices' => ['In-progress' => 'In-progress', 'Closed' => 'Closed'],
            ]);

        if ($this->globalOption->getDpsConfig()->isInvestigation() == 1){

                $builder->add('investigations', EntityType::class, [
                    'required' => false,
                    'multiple' => true,
                    'attr' => ['class' => 'm-wrap span12 select2'],
                    'class' => DiagnosticReport::class,
                    'group_by' => 'category.name',
                    'choice_label' => 'name',
                    'choice_translation_domain' => true,
                    'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                        ->join("e.category", 'c')
                        ->orderBy("e.name", "ASC")

                ]);
            }
           $builder->add('customer', PatientForPrescriptionType::class);
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'globalOption' => null,
            'location' => null,
            'diagnostic' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => DpsInvoice::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_dpsinvoice';
    }

    /**
     * @return mixed
     */
    protected function categoryChoiceList()
    {
        return $categoryTree = $this->diagnostic->getInvestigationGroupList();
    }

}
