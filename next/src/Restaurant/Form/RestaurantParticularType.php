<?php

namespace App\Restaurant\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Restaurant\Entity\Particular;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Restaurant\Entity\InvoiceParticular;
use App\Restaurant\Entity\RestaurantConfig;
use App\Restaurant\Repository\ParticularRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class RestaurantParticularType extends AbstractType
{

    /** @var  RestaurantConfig */
    private $restaurantConfig;


    public function __construct(RestaurantConfig  $restaurantConfig ,  private readonly ParticularRepository $particular)
    {
        $this->restaurantConfig         = $restaurantConfig->getId();
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('particular', EntityType::class, [
                'class'     => Particular::class,
                'group_by'  => 'category.name',
                'property'  => 'codeName',
                'empty_value' => '-Choose product name -',
                'attr'=>['class'=>'span12 m-wrap select2 particular'],
                'choice_translation_domain' => true,
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->join("e.category",'c')
                    ->where("c.status=1")
                    ->andWhere("e.status=1")
                    ->andWhere("e.restaurantConfig ={$this->restaurantConfig}")
                    ->orderBy("c.sorting", "ASC")
                    ->addOrderBy("e.sorting", "ASC")
            ])
            /*  ->add('salesPrice', 'choice', array(
                'label' => 'Choose your car',
                'choices' => $this->ParticularChoiceList(),
            ))*/;

    }



    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InvoiceParticular::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'restaurant_item';
    }

    protected function ParticularChoiceList()
    {
        return $syndicateTree = $this->particular->getParticularOptionGroup($this->restaurantConfig);

    }





}
