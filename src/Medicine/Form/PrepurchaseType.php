<?php

namespace App\Medicine\Form;

use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Medicine\Entity\MedicineVendor;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Medicine\Entity\MedicinePrepurchase;
use App\Hospital\Entity\HospitalConfig;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PrepurchaseType extends AbstractType
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
            ->add('discountCalculation',NumberType::class, ['attr'=>['class'=>'td-inline-input span12 salesInput','placeholder'=>'(%)','data-original-title'=>'(%)','autocomplete'=>'off']])
            ->add('medicineVendor', EntityType::class, [
                'required'    => true,
                'class' => MedicineVendor::class,
                'placeholder' => '---Choose a vendor/supplier ---',
                'choice_label' => 'companyName',
                'attr'=>['class'=>'m-wrap span12 inputs select2'],
                'constraints' =>[ new NotBlank(message: 'Please select your vendor name') ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('wt')
                    ->where("wt.status = 1")
                    ->andWhere("wt.medicineConfig =".$this->option->getMedicineConfig()->getId()),
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
            'data_class' => MedicinePrepurchase::class
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
