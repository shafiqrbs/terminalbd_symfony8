<?php

namespace App\Inventory\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\DomainUser\Form\SalesCustomerType;
use App\Inventory\Entity\SalesItem;
use Doctrine\ORM\EntityRepository;
use App\Location\Repository\LocationRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class SalesGeneralType extends AbstractType
{
    protected $location;


    /** @var GlobalOption */
    public  $globalOption;

    /** @var  LocationRepository */

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];
        $this->location = $options['location'];

        $builder

            ->add('payment', TextType::class, array('attr'=>array('class'=>'m-wrap span12 inputs','placeholder'=>'Enter payment amount','data-original-title'=>'Enter payment amount','autocomplete'=>'off')))
            ->add('discount', HiddenType::class)
	        ->add('deliveryCharge', TextType::class, array('attr'=>array('class'=>'m-wrap amount span12 inputs','placeholder'=>'Delivery charge','data-original-title'=>'Enter delivery charge','autocomplete'=>'off')))
	        ->add('discountCalculation', TextType::class, array('attr'=>array('class'=>'m-wrap span12 inputs','placeholder'=>'Enter discount amount','data-original-title'=>'Enter discount amount','autocomplete'=>'off')))
            ->add('cardNo', TextType::class, array('attr'=>array('class'=>'m-wrap span12 inputs','placeholder'=>'Add payment card/cheque no','data-original-title'=>'Add payment card/cheque no','autocomplete'=>'off')))
            ->add('transactionId', TextType::class, array('attr'=>array('class'=>'m-wrap span12 inputs','placeholder'=>'Add payment transaction id','data-original-title'=>'Add payment transaction id','autocomplete'=>'off')))
            ->add('paymentMobile', TextType::class, array('attr'=>array('class'=>'m-wrap span12 mobile inputs','placeholder'=>'Add payment mobile no','data-original-title'=>'Add payment mobile no','autocomplete'=>'off')))
            ->add('transactionMethod', EntityType::class, array(
                'required'    => true,
                'class' => 'App\Tool\Entity\TransactionMethod',
                'choice_label' => 'name',
                'attr'=>array('class'=>'span12 m-wrap transactionMethod inputs'),
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('e')
                        ->where("e.status = 1")
                        ->orderBy("e.id","ASC");
                }
            ))
            ->add('paymentCard', EntityType::class, array(
                'required'    => false,
                'choice_label' => 'name',
                'class' => 'App\Tool\Entity\PaymentCard',
                'attr'=>array('class'=>'span12 m-wrap inputs'),
                'placeholder' => '---Choose payment method---',
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('e')
                        ->where("e.status = 1")
                        ->orderBy("e.id","ASC");
                }
            ))
            ->add('salesBy', EntityType::class, array(
                'required'    => true,
                'class' => 'App\User\Entity\User',
                'choice_label' => 'userFullName',
                'attr'=>array('class'=>'span12 m-wrap inputs'),
                'placeholder' => '---Sales By---',
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('u')
                        ->where("u.isDelete != 1")
	                    ->andWhere("u.enabled = 1")
	                    ->andWhere("u.domainOwner = 2")
	                    ->andWhere("u.globalOption =".$this->globalOption->getId())
                        ->orderBy("u.username", "ASC");
                }
            ))

            ->add('accountBank', EntityType::class, array(
                'required'    => false,
                'class' => 'App\Accounting\Entity\AccountBank',
                'choice_label' => 'name',
                'attr'=>array('class'=>'span12 m-wrap inputs'),
                'placeholder' => '---Choose receive bank account---',
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('b')
                        ->where("b.status = 1")
                        ->andWhere("b.globalOption =".$this->globalOption->getId())
                        ->orderBy("b.name", "ASC");
                }
            ))
            ->add('process', ChoiceType::class, array(
                'required'    => false,
                'attr'=>array('class'=>'span8 m-wrap inputs'),
                'placeholder' => '---Choose current process---',
                'constraints' =>array(
                    new NotBlank(message: 'Select current process')
                ),
                'choices' => array('In-progress' => 'In-progress', 'Courier' => 'Courier', 'Delivered' => 'Delivered', 'Cancel' => 'Cancel', 'Returned' => 'Returned', 'Done' => 'Done'),
            ))
	        ->add('discountType', ChoiceType::class, array(
		        'required'    => true,
		        'attr'=>array('class'=>'span12 m-wrap inputs'),
		        'choices' => array('Flat' => 'Flat', 'Percentage' => 'Percentage'),
	        ))

            ->add('accountMobileBank', EntityType::class, array(
                'required'    => false,
                'class' => 'App\Accounting\Entity\AccountMobileBank',
                'choice_label' => 'name',
                'attr'=>array('class'=>'span12 m-wrap inputs'),
                'placeholder' => '---Choose receive mobile account---',
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('b')
                        ->where("b.status = 1")
                        ->andWhere("b.globalOption =".$this->globalOption->getId())
                        ->orderBy("b.name", "ASC");
                }
            ));
           // $builder->add('customer', SalesCustomerType::class, ['location' => $this->location]);

    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'globalOption' => null,
            'location' => null,
        ]);

        $resolver->setDefaults(array(
            'data_class' => 'App\Inventory\Entity\Sales'
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'sales';
    }

}
