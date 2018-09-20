<?php
namespace Puzzle\Admin\MediaBundle\Twig;

use Doctrine\ORM\EntityManager;

/**
 *
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 *
 */
class PuzzleAdminMediaExtension extends \Twig_Extension
{
    /**
     * @var EntityManager $em
     */
    protected $em;

    /**
     * @var string
     */
    protected $baseApisUri;
    
    public function __construct(string $baseApisUri) {
        $this->baseApisUri = $baseApisUri;
    }
    
    public function getFunctions() {
        return [
            new \Twig_SimpleFunction('render_size_convert', [$this, 'renderSizeConvert'], ['needs_environment' => false, 'is_safe' => ['html']]),
            new \Twig_SimpleFunction('render_date_convert', [$this, 'renderDateConvert'], ['needs_environment' => false, 'is_safe' => ['html']]),
            new \Twig_SimpleFunction('render_api_media_file', [$this, 'renderApiMediaFile'], ['needs_environment' => false, 'is_safe' => ['html']]),
        ];
    }
    
    
    /**
     * Converts bytes into human readable file size.
     *
     * @param string $bytes
     * @return string human readable file size (2,87 Мб)
     * @author Mogilev Arseny
     */
    public function renderSizeConvert(int $size)
    {
        $bytes = floatval($size);
        $arBytes = array(
            0 => array(
                "UNIT" => "TB",
                "VALUE" => pow(1024, 4)
            ),
            1 => array(
                "UNIT" => "GB",
                "VALUE" => pow(1024, 3)
            ),
            2 => array(
                "UNIT" => "MB",
                "VALUE" => pow(1024, 2)
            ),
            3 => array(
                "UNIT" => "KB",
                "VALUE" => 1024
            ),
            4 => array(
                "UNIT" => "B",
                "VALUE" => 1
            ),
        );
        
        foreach($arBytes as $arItem)
        {
            if($bytes >= $arItem["VALUE"])
            {
                $result = $bytes / $arItem["VALUE"];
                $result = str_replace(".", "," , strval(round($result, 2)))." ".$arItem["UNIT"];
                break;
            }
        }
        
        return $result;
    }
    
    public function renderApiMediaFile($path) {
        return $this->baseApisUri.$path;
    }
    
}
