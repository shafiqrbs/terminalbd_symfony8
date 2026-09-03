<?php

namespace App\Appearance\Form;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Appearance\Entity\TemplateCustomize;
use App\Tool\Form\SocialIconType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class TemplateCustomizeType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('showSocialIcon')
            ->add('mobileFooter', ChoiceType::class, [
                'attr'=>['class'=>'span12 m-wrap'],
                'choices' => ['' => '- Mobile Footer -','none' => 'None','footer' => 'Simple Footer','contact' => 'Contact','cart' => 'Cart'],
            ])
            ->add('mobileLogin')
            ->add('contactForm')
            ->add('registrationForm')
            ->add('mobileShowLogo')
            ->add('mobileHomeShowLogo')
            ->add('topBar')
            ->add('contactButton')
            ->add('footerBlock')
            ->add('showCalendar')
            ->add('showNewsLetter')
            ->add('showEmail')
            ->add('showMobile')
            ->add('showSearch')
            ->add('showLogin')
            ->add('showSidebar')
            ->add('sidebarTooltip')
            ->add('menuBold')
            ->add('otpLogin')
            ->add('contactNo',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter contact no']])
            ->add('contactEmail',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter contact email']])
             ->add('googleAnalytic',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter Google Analytic ID']])
            ->add('facebookPixel',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter Google Analytic ID']])
            ->add('fbMessenger',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter Facebook Messenger ID']])
            ->add('tawk',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter Tawk ID']])
            ->add('liveChat',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter Live Chat ID']])
            ->add('powr',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter POWR ID']])
            ->add('sidebarTitle',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter sidebar title']])
            ->add('topBarContent',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=>2,'placeholder'=>'Enter top bar text or button']])
            ->add('siteCssStyle',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=> 12,'placeholder'=>'Enter customize site css style']])
            ->add('cssStyleMobile',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=> 12,'placeholder'=>'Enter customize site css style for mobile']])
            ->add('sidebarPosition', ChoiceType::class, [
                'attr'=>['class'=>'span12'],
                'choices' => ['' => '---Select One---','left' => 'Left',  'right' => 'Right'],
            ])

              ->add('contentAlign', ChoiceType::class, [
                'attr'=>['class'=>'span8 m-wrap'],
                'choices' => ['' => '- Content Align -','justify' => 'Justify','left' => 'Left','center' => 'Center','right' => 'Right'],
            ])

             ->add('siteLanguage', ChoiceType::class, [
                'attr'=>['class'=>'span12'],
                'choices' => ['english' => 'English',  'bangoli' => 'Bangoli'],
            ])

            ->add('socialIconType', ChoiceType::class, [
                'attr'=>['class'=>'span12'],
                'choices' => [
                	'' => '---Select Icon Type ---',
	                'icon-fadein' => 'Icon Fadein',
	                'icon-loop' => 'Icon Loop',
	                'icon-square' => 'Icon Square',
	                'icon-circle' => 'Icon Circle',
                ],
            ])

            ->add('socialIconPosition', ChoiceType::class, [
                'attr'=>['class'=>'span12'],
                'choices' => ['' => '---Select Icon Position---','menu-right' => 'Menu-Right','top-left' => 'Top-Left','top-center' => 'Top-Center','top-right' => 'Top-Right'],
            ])

            ->add('topIconPosition', ChoiceType::class, [
                'attr'=>['class'=>'span12'],
                'choices' => ['' => '---Contact Position---','text-left' => 'Left','text-center' => 'Center','text-right' => 'Right'],
            ])

            ->add('topTextPosition', ChoiceType::class, [
                'attr'=>['class'=>'span12'],
                'choices' => ['' => '---Top Text Position---','menu-right' => 'Menu-Right','text-left' => 'Left','text-center' => 'Center','text-right' => 'Right'],
            ])

            ->add('breadcrumb')
	        ->add('breadcrumbHeight',NumberType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Breadcrumb height']])

	        ->add('breadcrumbPosition', ChoiceType::class, [
                'attr'=>['class'=>' span12'],
                'choices' => ['' => 'Select Breadcrumb Position', 'flat-left' => 'Flat-Left','flat-right' => 'Flat-Right',  'left' => 'Left','center' => 'Center'],
            ])
            ->add('breadcrumbFontSize', ChoiceType::class, [
                'attr'=>['class'=>' span12'],
                'choices' => ['' => 'Font Size', '10px' => '10px',  '12px' => '12px','14px' => '14px', '16px' => '16px','18px' => '18px',  '20px' => '20px'],
            ])
            ->add('stickyBgColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])
            ->add('breadcrumbBg',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])
            ->add('breadcrumbActiveBg',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])
            ->add('breadcrumbHome',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])
            ->add('breadcrumbColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])
           /* ->add('breadcrumbBorderColor','text', array('attr'=>array(
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'')
            ))*/
            
            ->add('siteBgColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])

            ->add('sidebarColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])

            ->add('websiteTitle',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Website title text']])
            ->add('siteTitle',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Site page welcome text']])
            ->add('siteTitleSize', ChoiceType::class, [
                'attr'=>['class'=>'span12'],
                'choices' => ['H1' => 'H1','H2' => 'H2','H3' => 'H3','H4' => 'H4'],
            ])
            ->add('siteTitleColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])
            ->add('siteSlogan',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','row'=> 4,'placeholder'=>'Site slogan']])
            ->add('homeBgColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])

            ->add('homeAnchorColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])

            ->add('homeAnchorColorHover',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])


            ->add('siteFontSize', ChoiceType::class, [
                'attr'=>['class'=>' span12'],
                'choices' => ['' => 'Font Size', '10px' => '10px',  '11px' => '11px','12px' => '12px','13px' => '13px','14px' => '14px','15px' => '15px', '16px' => '16px', '17px' => '17px','18px' => '18px',  '20px' => '20px', '22px' => '22px','24px' => '24px'],
            ])

            ->add('contentFontSize', ChoiceType::class, [
                'attr'=>['class'=>' span8'],
                'choices' => ['' => 'Font Size', '10px' => '10px',  '11px' => '11px','12px' => '12px','13px' => '13px','14px' => '14px','15px' => '15px', '16px' => '16px', '17px' => '17px','18px' => '18px',  '20px' => '20px', '22px' => '22px','24px' => '24px','28px' => '28px','30px' => '30px'],
            ])

            ->add('contentLineHeight', ChoiceType::class, [
                'attr'=>['class'=>' span8'],
                'choices' => ['' => 'Font Size','10px' => '10px',  '11px' => '11px','12px' => '12px','13px' => '13px','14px' => '14px', '15px' => '15px', '16px' => '16px', '17px' => '17px','18px' => '18px',  '20px' => '20px', '22px' => '22px','24px' => '24px', '26px' => '26px',  '28px' => '28px','30px' => '39px','32px' => '32px','34px' => '34px','36px' => '36px', '38px' => '38px', '40px' => '40px','42px' => '42px',  '44px' => '44px', '46px' => '46px','48px' => '48px'],
            ])

            ->add('pagination', ChoiceType::class, [
                'attr'=>['class'=>'span12'],
                'choices' => ['bootstrap' => 'Bootstrap',  'nextPrev' => 'Next Previous', 'nextPrevDropDown' => 'Next Previous with drop down'],
            ])
            ->add('carouselHeight', ChoiceType::class, [
                'attr'=>['class'=>'span12'],
                'choices' => [
                    '720px'     => '720px',
                    '628px'     => '628px',
                    '444px'     => '444px',
                    '360px'     => '360px',
                ],
            ])
            ->add('mobileCarouselHeight', ChoiceType::class, [
                'attr'=>['class'=>'span12'],
                'choices' => [
                    '220px'     => '220px',
                    '280px'     => '280px',
                    '320px'     => '320px',
                    '360px'     => '360px',
                ],
            ])
            ->add('sliderPosition', ChoiceType::class, [
                'attr'=>['class'=>'span12'],
                'choices' => [
                    'top-left'      => 'Top-Left',
                    'top-center'    => 'Top-Center',
                    'top-right'     => 'Top-Right',
                    'vertical'      => 'Vertical',
                    'bottom-left'   => 'Bottom-Left',
                    'bottom-center' => 'Bottom-Center',
                    'bottom-right'  => 'Bottom-Right'
                ],
            ])
            ->add('sliderLeftRightPosition',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric','placeholder'=>'Slider Top/Bottom']])
            ->add('sliderTopBottomPosition',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric','placeholder'=>'Slider Left/Right']])
            ->add('siteFontFamily', ChoiceType::class, [
                'attr'=>['class'=>'span12'],
                'choices' => [
                    '' => '---Select font family---',
                    'Open Sans, sans-serif' => 'Open Sans, sans-serif',
                    'Helvetica, sans-serif' => 'Helvetica, sans-serif',
                    'Verdana' => 'Verdana',
                    'Gill Sans' => 'Gill Sans',
                    'Avantgarde' => 'Avantgarde',
                    'Helvetica Narrow' => 'Helvetica Narrow',
                    'Times' => 'Times',
                    'Times New Roman' => 'Times New Roman',
                    'Palatino' => 'Palatino',
                    ]
            ])

            ->add('anchorColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])
            ->add('anchorHoverColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])

            ->add('buttonBgColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])

            ->add('buttonBgColorHover',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])
            ->add('dividerBorderWidth',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric','placeholder'=>'Divider border width']])
            ->add('dividerBorder',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric','placeholder'=>'Border size']])

            ->add('dividerAfterColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])

            ->add('dividerBeforeColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])

            ->add('dividerFontColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])
            ->add('dividerFontSize', ChoiceType::class, [
                'attr'=>['class'=>' span12'],
                'choices' => ['' => 'Font Size', '10px' => '10px',  '12px' => '12px','14px' => '14px', '16px' => '16px','18px' => '18px',  '20px' => '20px','22px' => '22px', '24px' => '24px', ],
            ])

            ->add('siteH1TextSize', ChoiceType::class, [
                'attr'=>['class'=>' span12'],
                'choices' => ['' => '---Select One---','30px' => '30px', '32px' => '32px','34px' => '34px', '36px' => '36px','38px' => '38px', '40px' => '40px','42px' => '42px', '44px' => '44px','46px' => '46px','48px' => '48px', '50px' => '50px'],
            ])

            ->add('siteH2TextSize', ChoiceType::class, [
                'attr'=>['class'=>' span12'],
                'choices' => ['' => '---Select One---','24px' => '24px',  '26px' => '26px','28px' => '28px', '30px' => '30px','32px' => '32px',  '34px' => '34px','36px' => '36px'],
            ])
            ->add('siteH3TextSize', ChoiceType::class, [
                'attr'=>['class'=>' span12'],
                'choices' => ['' => '---Select One---','18px' => '18px',  '20px' => '20px','22px' => '22px', '24px' => '24px', '26px' => '26px','28px' => '28px', '30px' => '30px'],
            ])
            ->add('siteH4TextSize', ChoiceType::class, [
                'attr'=>['class'=>' span12'],
                'choices' => ['' => '---Select One---','10px' => '10px',  '12px' => '12px','14px' => '14px', '16px' => '16px','18px' => '18px',  '20px' => '20px','22px' => '22px', '24px' => '24px'],
            ])

            ->add('siteTitleBgColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])

            ->add('subPageBgColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])
            ->add('topBgColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])
            ->add('headerBgColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])

            ->add('headerBorderColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])

            ->add('headerBorderHeight', ChoiceType::class, [
                'attr'=>['class'=>'span6 m-wrap'],
                'choices' => ['1px' => '1px',  '2px' => '2px', '3px' => '3px', '4px' => '4px',  '5px' => '5px'],
            ])

            ->add('menuBgColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])

            ->add('menuBgColorHover',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])
            ->add('subMenuWidth',TextType::class, ['attr'=>['class'=>'m-wrap span6','placeholder'=>'Enter sub menu with']])
            ->add('menuLiAColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])

            ->add('menuLiAHoverColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])

            ->add('subMenuBgColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default','placeholder'=>'']
            ])

            ->add('subMenuBgColorHover',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default','placeholder'=>'']
            ])

            ->add('menuFontSize', ChoiceType::class, [
                'attr'=>['class'=>'span12 m-wrap'],
                'choices' => ['' => '---Select One---','10px' => '10px',  '11px' => '11px',  '12px' => '12px', '13px' => '13px', '14px' => '14px',  '15px' => '15px', '16px' => '16px', '17px' => '17px',  '18px' => '18px', '19px' => '19px', '20px' => '20px'],
            ])

            ->add('menuLetter', ChoiceType::class, [
                'attr'=>['class'=>'span10 m-wrap'],
                'choices' => ['uppercase' => 'Uppercase',  'capitalize' => 'Capitalize', 'lowercase' => 'Lowercase'],
            ])
            ->add('boxShadow', ChoiceType::class, [
                'attr'=>['class'=>'span10 m-wrap'],
                'choices' => ['' => '-Select Box Shadow-','shadow-box-1' => 'Shadow-Box-1','shadow-box-2' => 'Shadow-Box-2','shadow-box-3' => 'Shadow-Box-3','shadow-box-4' => 'Shadow-Box-4','shadow-box-5' => 'Shadow-Box-5','shadow-box-6' => 'Shadow-Box-6'],
            ])
            ->add('boxShadowColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])

            ->add('menuPosition', ChoiceType::class, [
                'attr'=>['class'=>'span10 m-wrap'],
                'choices' => ['' => 'Navbar-Default','navbar-right' => 'Navbar-Right','navbar-center' => 'Navbar-Center',  'brand-center' => 'Logo-Center','navbar-brand-top' => 'Logo-Center-Top',  'brand-center center-side' => 'Logo-Center Menu-Side'],
            ])

            ->add('menuTopMargin',TextType::class, ['attr'=>[
                'class'=>'m-wrap span12 numeric',
                'placeholder'=>'']
            ])
            ->add('stickyMenuTopMargin',TextType::class, ['attr'=>[
                'class'=>'m-wrap span12 numeric',
                'placeholder'=>'']
            ])

            ->add('bodyColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])

            ->add('borderColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])

            ->add('borderColorHover',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])

            ->add('footerBgColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])

            ->add('footerTextColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])
            ->add('footerAnchorColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])
            ->add('footerAnchorColorHover',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])
            ->add('logoHeight',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 numeric',
                'placeholder'=>'']
            ])
            ->add('logoWidth',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 numeric',
                'placeholder'=>'']
            ])
            ->add('playStore',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Play Store url']])
            ->add('appleStore',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Apple store url']])
           /* ->add('messengerScript','textarea', array('attr'=>array('class'=>'m-wrap span12','rows'=> 6,'placeholder'=>'Enter messenger script')))*/
            ->add('metaDescription',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=>4,'placeholder'=>'Enter meta description']])
            ->add('metaKeyword',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=>5,'placeholder'=>'Enter meta keywords']])
            ->add('placeholderColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])
            ->add('mobileHeaderBgColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])
            ->add('mobileFooterBgColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])
            ->add('mobileFooterAnchorBg',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])
            ->add('mobileFooterAnchorColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])
            ->add('mobileFooterAnchorColorHover',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])

            ->add('mobileMenuBgColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])
            ->add('mobileMenuBgColorHover',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])
            ->add('mobileMenuLiAColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])
            ->add('mobileMenuLiAHoverColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span10 colorpicker-default',
                'placeholder'=>'']
            ])
        ;
	    $builder->add('globalOption', SocialIconType::class);

    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TemplateCustomize::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'templatecustomize';
    }
}
