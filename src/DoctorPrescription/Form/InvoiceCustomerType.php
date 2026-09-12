<?php

namespace App\DoctorPrescription\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\DoctorPrescription\Entity\DpsParticular;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\DoctorPrescription\Entity\DpsInvoice;
use App\DomainUser\Form\PatientForPrescriptionType;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class InvoiceCustomerType extends AbstractType
{
    protected $globalOption;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];

        $builder

            ->add('assignDoctor', EntityType::class, [
                'required'    => true,
                'class' => DpsParticular::class,
                'choice_label' => 'name',
                'multiple'    => false,
                'expanded' => false,
                'attr'=>['class'=>'m-wrap span12'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.mode = 'doctor'")
                    ->andWhere('e.dpsConfig =:dpsConfig')
                    ->setParameter('dpsConfig', $this->globalOption->getDpsConfig()->getId())
                    ->orderBy("e.name","ASC")
            ]);
           $builder->add('customer', PatientForPrescriptionType::class);
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'globalOption' => null,
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
        return 'dpsinvoice';
    }

}
