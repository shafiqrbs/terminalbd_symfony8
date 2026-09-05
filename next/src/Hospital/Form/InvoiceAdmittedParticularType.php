<?php

namespace App\Hospital\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Hospital\Entity\Particular;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Hospital\Entity\AdmissionPatientParticular;
use App\Hospital\Entity\HospitalConfig;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class InvoiceAdmittedParticularType extends AbstractType
{
    protected $hospitalConfig;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->hospitalConfig = $options['hospitalConfig'];


        $servicies = [];
        $servies = $this->hospitalConfig->getServices();
        if($servies){
            foreach ($servies as $service){
                $servicies[] = $service->getId();
            }
        }
        $builder
            ->add('particular', EntityType::class,[
                'class'     => Particular::class,
                'group_by'  => 'service.name',
                'choice_label'  => 'name',
                'constraints' =>[
                    new NotBlank(message: 'Please choose particular')
                ],
                'placeholder' => '---Test Name, Accessories, Surgery, Cabin etc.---',
                'attr'=>['class'=>'span12 m-wrap particular select2'],
                'choice_translation_domain' => true,
                'query_builder' => function(EntityRepository $er) use($servicies) {
                    $qb =  $er->createQueryBuilder('e');
                    $qb->join("e.service",'s');
                    $qb->where('e.hospitalConfig ='.$this->hospitalConfig->getId());
                    $qb->andWhere('s.id IN(:service)')->setParameter('service',$servicies);
                    $qb->andWhere("e.status = 1");
                    $qb->orderBy("e.name","ASC");
                    return $qb;
                }
            ])
            ->add('quantity',TextType::class, ['attr'=>['class'=>'m-wrap span5 numeric input-number text-center','placeholder'=>'Quantity','autocomplete'=>'off']])
            ->add('salesPrice',TextType::class, ['attr'=>['class'=>'m-wrap span8 price','placeholder'=>'Price','autocomplete'=>'off']])
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'hospitalConfig' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => AdmissionPatientParticular::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'admitted_particular';
    }

}
