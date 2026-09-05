<?php

namespace App\Business\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Accounting\Entity\AccountVendor;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Business\Entity\BusinessPurchase;
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

            ->add('vendor', EntityType::class, [
                'required'    => true,
                'class' => AccountVendor::class,
                'placeholder' => '---Choose a vendor ---',
                'choice_label' => 'companyName',
                'attr'=>['class'=>'span12 m-wrap'],
                'constraints' =>[ new NotBlank(message: 'Please select your vendor name') ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.globalOption =".$this->option->getId()),
            ])
            ;
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
            'data_class' => BusinessPurchase::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'purchase';
    }
}
