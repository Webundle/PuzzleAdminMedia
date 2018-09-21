<?php

namespace Puzzle\Admin\MediaBundle\Form\Model;

use Puzzle\Admin\MediaBundle\Util\MediaUtil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * @author AGNES Gnagne Cédric <cecenho55@gmail.com>
 */
class AbstractFolderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options){
        $builder
            ->add('name', TextType::class, [
                'translation_domain' => 'admin',
                'label' => 'media.folder.name',
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('tag', TextType::class, [
                'translation_domain' => 'admin',
                'label' => 'media.folder.tag',
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-control'],
                'required' => false
            ])
            ->add('filter', ChoiceType::class, array(
                'translation_domain' => 'admin',
                'choices' => array(
                    "media.file.list" =>    "*",
                    "media.picture.list"    => MediaUtil::supportedPictureExtensions(),
                    "media.audio.list"      => MediaUtil::supportedAudioExtensions(),
                    "media.video.list"      => MediaUtil::supportedVideoExtensions(),
                    "media.document.list"   => MediaUtil::supportedDocumentExtensions(),
                    "Personaliser"          => "customize",
                ),
                'choices_as_values' => true,
                'attr' => ['class' => 'select'],
                'mapped' => false
            ))
            ->add('allowedExtensions', TextType::class, array(
                'translation_domain' => 'admin',
                'label' => 'media.property.folder.allowed_extensions',
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => "form-control tokenfield"],
                'required' => false
            ))
            ->add('save', SubmitType::class, array(
                'translation_domain' => 'admin',
                'label' => 'button.save',
                'attr' => ['class' => "btn btn-success"]
            ))
        ;
    }
}