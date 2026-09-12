<?php

namespace App\Medicine\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Medicine\Entity\MedicineVendor;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Medicine\Entity\MedicinePurchase;
use App\Hospital\Entity\HospitalConfig;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PurchaseOpeningType extends AbstractType
{
    /** @var  HospitalConfig */

    public  $option;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->option = $options['option'];

        $builder

            ->add('medicineVendor', EntityType::class, [
                'required'    => true,
                'class' => MedicineVendor::class,
                'placeholder' => '---Choose a vendor/supplier ---',
                'choice_label' => 'companyName',
                'attr'=>['class'=>'m-wrap span12 select2 inputs'],
                'constraints' =>[ new NotBlank(message: 'Please select your vendor name') ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.medicineConfig =".$this->option->getMedicineConfig()->getId())
                    ->orderBy('e.companyName','ASC'),
            ]);

    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'option' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => MedicinePurchase::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'medicinepurchase';
    }
}
