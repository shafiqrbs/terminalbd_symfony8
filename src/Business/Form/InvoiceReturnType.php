<?php

namespace App\Business\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\DomainUser\Entity\Customer;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Business\Entity\BusinessInvoiceReturn;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class InvoiceReturnType extends AbstractType
{
    /** @var  GlobalOption */

    public  $option;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->option = $options['option'];

        $builder

            ->add('customer', EntityType::class, [
                'required'    => true,
                'class' => Customer::class,
                'placeholder' => '---Choose a customer ---',
                'choice_label' => 'nameMobile',
                'attr'=>['class'=>'m-wrap span12 inputs select2'],
                'constraints' =>[ new NotBlank(message: 'Please select customer name') ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('wt')
                    ->where("wt.status = 1")
                    ->andWhere("wt.globalOption =".$this->option->getId()),
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
            'data_class' => BusinessInvoiceReturn::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'invoiceReturn';
    }
}
