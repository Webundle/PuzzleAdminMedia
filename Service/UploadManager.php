<?php

namespace Puzzle\Admin\MediaBundle\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * 
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 *
 */
class UploadManager
{
    /**
     * @var string
     */
    protected $uploadDir;
    
    /**
     * @var int
     */
    protected $maxSize;
    
    /**
     * @param string $uploadDir
     */
    public function __construct(string $uploadDir, int $maxSize) {
        $this->uploadDir = $uploadDir;
        $this->maxSize = $maxSize;
    }
    
    public static function getWebPath() {
        return '/uploads';
    }
    
    public function getAbsoluteUploadDir() {
        return $this->uploadDir.self::getWebPath();
    }
    
	/**
	 * Prepare Upload
	 *
	 * @param array $product
	 * @param string $action
	 */
	public function prepareUpload($globalFiles, $httpHost = null)
	{
		$results = [];
		if(count($globalFiles) > 0 ){
			foreach ($globalFiles as $globalFile){
				$originalNames = $globalFile['name'];
				$mimeTypes = $globalFile['type'];
				$path = $globalFile['tmp_name'];
				$errors = $globalFile['error'];
				$size = $globalFile['size'];
			}
			
			if(! is_array($originalNames)){
				$originalNames = [$originalNames];
				$mimeTypes = [$mimeTypes];
				$path = [$path];
				$errors = [$errors];
				$size = [$size];
			}
	
			$length = count($originalNames);
			for ($i = 0; $i < $length; $i++){
			    if($originalNames[$i] != null && $size[$i] < $this->maxSize){
					$file = new UploadedFile($path[$i], $originalNames[$i], $mimeTypes[$i], $size[$i]);
					$results[] = $this->upload($file, $httpHost);
				}
			}
		}
	
		return $results;
	}
	
	/**
	 * @param UploadedFile $file
	 * @return string
	 */
	public function upload(UploadedFile $file, $httpHost = null)
	{
	    $dir = $this->getAbsoluteUploadDir();
	    $name = str_replace(' ', '_', utf8_encode($file->getClientOriginalName()));
		$extension = $file->getClientOriginalExtension();
		$filename = $dir.'/'.$name;
		$count = 0;
		
		// Rename duplicate file
		while(file_exists($filename)){
		    $filename = $dir.'/'.basename($filename, '.'.$extension);
		    $basename = basename($filename, '('.$count.')');
			$count++;
			$name = $basename.'('.$count.').'.$extension;
			$filename = $dir.'/'.$name;
		}
	
		 // Upload File
		$file = $file->move($dir, $name);
		return $httpHost.self::getWebPath().'/'.$name;
	}
	
	public function unlink($path, $httpHost = null) {
	    $path = str_replace($httpHost, '', $path);
	    $filename = $this->uploadDir.$path;
	    
	    if (file_exists($filename)) {
	        return unlink($filename);
	    }
	    
	    return null;
	}
}
