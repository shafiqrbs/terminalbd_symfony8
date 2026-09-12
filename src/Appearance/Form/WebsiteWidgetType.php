<?php

namespace App\Appearance\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Appearance\Entity\Menu;
use Doctrine\ORM\EntityRepository;
use App\Content\Entity\ModuleCategory;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use App\Content\Entity\Page;
use App\Tool\Entity\Module;
use App\Appearance\Entity\JsFeature;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Appearance\Entity\FeatureWidget;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class WebsiteWidgetType extends AbstractType
{
    protected $globalOption;
    protected $globalId;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];
        $this->globalId = $this->globalOption->getId();

        $builder

            ->add('menu', EntityType::class, [
                'required'    => false,
                'class' => Menu::class,
                'placeholder' => '---Select Menu Page---',
                'choice_label' => 'menu',
                'attr'=>['class'=>'m-wrap span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.globalOption =".$this->globalOption->getId())
                    ->orderBy('e.menu','ASC'),
            ])
            ->add('moduleCategory', EntityType::class, [
                'required'    => false,
                'multiple'    => true,
                'class' => ModuleCategory::class,
                'placeholder' => '---Select page---',
                'choice_label' => 'name',
                'attr'=>['class'=>'m-wrap span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.globalOption =".$this->globalOption->getId())
                    ->orderBy('e.name','ASC'),
            ])
            ->add('position', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap span12 select2 '],
                'placeholder' => '---Select Position---',
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => ['Header' => 'header', 'Body Top' => 'body-top', 'Body Bottom' => 'body-bottom', 'Sidebar Top' => 'sidebar-top', 'Sidebar Bottom' => 'sidebar-bottom', 'Footer' => 'footer', 'Mobile' => 'mobile', 'Mobile Intro' => 'mobile-intro'],
            ])
            ->add('pageFeatureName',TextType::class, ['attr'=>['class'=>'span12 m-wrap']])
            ->add('moduleFeatureName',TextType::class, ['attr'=>['class'=>'span12 m-wrap']])
            ->add('moduleShowLimit',NumberType::class, ['attr'=>['class'=>'span12 m-wrap']])
            ->add('moduleShowType', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap span12'],
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => ['List' => 'list', 'Grid' => 'grid', 'Slider' => 'slider'],
            ])

            ->add('pageShowType', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap span12'],
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => ['List' => 'list', 'Grid' => 'grid', 'Slider' => 'slider'],
            ])

            ->add('page', EntityType::class, [
                'required'    => false,
                'multiple'    => true,
                'class' => Page::class,
                'placeholder' => '---Select Page---',
                'choice_label' => 'name',
                'attr'=>['class'=>'m-wrap span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.module = 12")
                    ->andWhere("e.globalOption = $this->globalId")
                    ->orderBy('e.name','ASC'),
            ])

            ->add('module', EntityType::class, [
                'required'    => false,
                'multiple'    => false,
                'class' => Module::class,
                'placeholder' => '---Select page module---',
                'choice_label' => 'name',
                'attr'=>['class'=>'m-wrap span12'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->join('e.nav' ,'n')
                    ->where("e.status = 1")
                    ->andWhere("e.isSingle != 1")
                    ->andWhere("n.module != 12")
                    ->andWhere("n.module is not NULL")
                    ->andWhere("n.globalOption = $this->globalId")
                    ->orderBy('e.name','ASC'),
            ])

            ->add('jsFeature', EntityType::class, [
                'required'    => false,
                'class' => JsFeature::class,
                'placeholder' => '---Select Any Feature---',
                'choice_label' => 'name',
                'attr'=>['class'=>'m-wrap span12 '],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->orderBy('e.name','ASC'),
            ])

            ->add('content',TextareaType::class, ['attr'=>['class'=>'span12 m-wrap ckeditor','rows' => 6]])
            ->add('sliderFeature', ChoiceType::class, [
                'attr'=>['class'=>'span12  m-wrap targetTo'],
                'placeholder' => '---Slider with Feature ---',
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => ['Category' => 'category', 'Brand' => 'brand', 'Promotion' => 'promotion', 'FeatureProduct' => 'featureProduct', 'Tag' => 'tag', 'Discount' => 'discount'],
            ])
            ->add('sliderFeaturePosition', ChoiceType::class, [
                'attr'=>['class'=>'span12  m-wrap'],
                'placeholder' => '---Feature Position ---',
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => ['Left' => 'left', 'Right' => 'right'],
            ])
            ;
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
            'data_class' => FeatureWidget::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_appearancebundle_featurewidget';
    }

    /**
     * @return mixed
     */
    protected function categoryChoiceList()
    {

        return $categoryTree = $this->category->getUseInventoryItemCategory($this->globalOption->getInventoryConfig());

    }

}
