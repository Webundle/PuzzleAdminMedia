<?php

namespace Puzzle\Admin\MediaBundle\Util;

/**
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 */
class MediaUtil
{
    /**
     * Supported audio extensions
     *
     * @return string
     */
    public static function supportedAudioExtensions() {
        return 'mp3|wav|m4a|m4r|ogg';
    }
    
    /**
     * Supported picture extensions
     *
     * @return string
     */
    public static function supportedPictureExtensions() {
        return 'jpg|jpeg|png|ico|bmp';
    }
    
    /**
     * Supported video extensions
     *
     * @return string
     */
    public static function supportedVideoExtensions() {
        return 'avi|mp4|webm|flv';
    }
    
    /**
     * Supported document extensions
     *
     * @return string
     */
    public static function supportedDocumentExtensions() {
        return 'doc|docx|ppt|pptx|xls|txt|pdf|html|twig';
    }
}