<?php

namespace Puzzle\Admin\MediaBundle\Form\Type;

use Puzzle\Admin\MediaBundle\Form\Model\AbstractFolderType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * 
 * @author AGNES Gnagne Cédric <cecenho55@gmail.com>
 * 
 */
class FolderCreateType extends AbstractFolderType
{
    public function configureOptions(OptionsResolver $resolver) {
        parent::configureOptions($resolver);
        
        $resolver->setDefault('csrf_token_id', 'folder_create');
        $resolver->setDefault('validation_groups', ['Create']);
    }
    
    public function getBlockPrefix() {
        return 'admin_media_folder_create';
    }
}