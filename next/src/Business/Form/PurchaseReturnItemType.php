<?php

namespace App\Business\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Business\Entity\BusinessParticular;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Business\Entity\BusinessPurchaseReturnItem;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PurchaseReturnItemType extends AbstractType
{


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('businessParticular', EntityType::class, [
                'required'    => true,
                'class' => BusinessParticular::class,
                'empty_value' => '---Choose a vendor ---',
                'property' => 'companyName',
                'attr'=>['class'=>'m-wrap span12 inputs select2'],
                'constraints' =>[ new NotBlank(message: 'Please select your product name') ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('wt')
                    ->where("wt.status = 1")
                    ->andWhere("wt.businessConfig =".$this->option->getBusinessConfig()->getId()),
            ])
            ->add('quantity',NumberType::class, ['attr'=>['class'=>'m-wrap span12 form-control input-number input','placeholder'=>'quantity']]);
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BusinessPurchaseReturnItem::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'purchaseReturnItem';
    }
}
