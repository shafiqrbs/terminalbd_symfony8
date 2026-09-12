<?php

namespace App\DomainUser\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\DomainUser\Entity\NotificationConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class NotificationConfigType extends AbstractType
{


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 mobile','autocomplete'=>'off','placeholder'=>'System mobile notification ' ]])
            ->add('paymentNotification',TextType::class, ['attr'=>['class'=>'m-wrap span12 mobile','autocomplete'=>'off','placeholder'=>'Payment mobile notification']])
            ->add('smsActive')
            ->add('onlineOrder')
            ->add('customerBaseOrder');
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NotificationConfig::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'notification';
    }


}
