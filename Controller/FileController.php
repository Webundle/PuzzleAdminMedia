<?php

namespace Puzzle\Admin\MediaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use GuzzleHttp\Exception\BadResponseException;
use Puzzle\ConnectBundle\ApiEvents;
use Puzzle\ConnectBundle\Event\ApiResponseEvent;

/**
 * 
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 * 
 */
class FileController extends Controller
{
	/***
	 * Show All Media
	 *
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @Security("has_role('ROLE_MEDIA') or has_role('ROLE_ADMIN')")
	 */
	public function listAction(Request $request){
		$criteria = [];
		
		// Format criteria
		if ($request->query->get('search')) {
		    $criteria['filter'] = 'name=^'.$request->query->get('search');
		}
		
		if ($type = $request->query->get('type')) {
		    $criteria['filter'] = isset($criteria['filter']) && $criteria['filter'] != '' ? 
		                          $criteria['filter'].'&type=='.$type : 'type=='.$type;
		}
		
		try {
    		/** @var Puzzle\ConectBundle\Service\PuzzleAPIClient $apiClient */
    		$apiClient = $this->get('puzzle_connect.api_client');
    		$files = $apiClient->pull('/media/files', $criteria);
		}catch (BadResponseException $e) {
		    /** @var EventDispatcher $dispatcher */
		    $dispatcher = $this->get('event_dispatcher');
		    $dispatcher->dispatch(ApiEvents::API_BAD_RESPONSE, new ApiResponseEvent($e, $request));
		    $files = [];
		}
		
		return $this->render("PuzzleAdminMediaBundle:File:list.html.twig",[
		    'files' => $files,
		    'type' => $request->query->get('type'),
		]);
	}
	
	/***
	 * Show All Media
	 *
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @Security("has_role('ROLE_MEDIA') or has_role('ROLE_ADMIN')")
	 */
	public function listForModalAction(Request $request){
	    $criteria = [];
	    
	    // Format criteria
	    if ($request->query->get('search')) {
	        $criteria['filter'] = 'name=^'.$request->query->get('search');
	    }
	    
	    if ($type = $request->query->get('type')) {
	        $criteria['filter'] .= isset($criteria['filter']) && $criteria['filter'] != '' ? '&type=='.$type : 'type=='.$type;
	    }
	    
	    $criteria['orderBy'] = 'type:DESC';
	    
	    try {
    	    /** @var Puzzle\ConectBundle\Service\PuzzleAPIClient $apiClient */
    	    $apiClient = $this->get('puzzle_connect.api_client');
    	    $files = $apiClient->pull('/media/files', $criteria);
	    }catch (BadResponseException $e) {
	        /** @var EventDispatcher $dispatcher */
	        $dispatcher = $this->get('event_dispatcher');
	        $dispatcher->dispatch(ApiEvents::API_BAD_RESPONSE, new ApiResponseEvent($e, $request));
	        $files = [];
	    }
	    
	    return $this->render("PuzzleAdminMediaBundle:File:list_for_modal.html.twig", array(
	        'type' => $type,
	        'filters' => $criteria,
	        'files' => $files,
	        'enableMultipleSelect' => $request->get('multiple_select') ? true: false,
	        'context' => $request->get('context')
	    ));
	}
	
	/**
	 * Upload Media From Another App
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @Security("has_role('ROLE_MEDIA') or has_role('ROLE_ADMIN')")
	 */
	public function createAction(Request $request) {
	    if ($request->isMethod('POST')) {
	        $data = $request->request->all();
	        $urls = [];
	        
	        if ($data['source'] === 'local') {
	            /** @var Puzzle\Admin\MediaBundle\Service\UploadManager $uploader */
	            $uploader = $this->get('admin.media.upload_manager');
	            $urls = $uploader->prepareUpload($_FILES, $request->getSchemeAndHttpHost());
	            
	        }else {
	            $urls = is_string($data['urls']) ? explode(',', $data['urls']) : $data['urls'];
	        }
	        
	        /** @var Puzzle\ConectBundle\Service\PuzzleAPIClient $apiClient */
	        $apiClient = $this->get('puzzle_connect.api_client');
	        
	        foreach ($urls as $url) {
	            try {
	                $postData = !$data['folder'] ? ['url' => $url] : ['url' => $url, 'folder' => $data['folder']];
    	            $apiClient->push('post', '/media/files', $postData);
    	            $uploader->unlink($url, $request->getSchemeAndHttpHost());
	            }catch (BadResponseException $e) {
	                /** @var EventDispatcher $dispatcher */
	                $dispatcher = $this->get('event_dispatcher');
	                $dispatcher->dispatch(ApiEvents::API_BAD_RESPONSE, new ApiResponseEvent($e, $request));
	            }
	        }
	        
	        if ($data['folder']) {
	            return $this->redirectToRoute('admin_media_folder_show', ['id' => $data['folder']]);
	        }
	        
	        return $this->redirectToRoute('admin_media_file_list');
	    }
		
	    return $this->render('PuzzleAdminMediaBundle:File:create.html.twig', [
	        'folder'   => $request->query->get('folder') ?? null,
	        'multiple' => $request->query->get('multiple') ?? false
	    ]);
	}
	
	/***
	 * Show File
	 *
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @Security("has_role('ROLE_MEDIA') or has_role('ROLE_ADMIN')")
	 */
	public function showAction(Request $request, $id) {
	    try {
	        /** @var Puzzle\ConectBundle\Service\PuzzleAPIClient $apiClient */
	        $apiClient = $this->get('puzzle_connect.api_client');
	        $file = $apiClient->pull('/media/files/'.$id);
	    }catch (BadResponseException $e) {
	        /** @var EventDispatcher $dispatcher */
	        $dispatcher = $this->get('event_dispatcher');
	        $dispatcher->dispatch(ApiEvents::API_BAD_RESPONSE, new ApiResponseEvent($e, $request));
	        $file = [];
	    }
	    
	    return $this->render("PuzzleAdminMediaBundle:File:show.html.twig", array('file' => $file));
	}

    /***
     * Remove File
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Security("has_role('ROLE_MEDIA') or has_role('ROLE_ADMIN')")
     */
    public function deleteAction(Request $request, $id) {
        try {
            /** @var Puzzle\ConectBundle\Service\PuzzleAPIClient $apiClient */
            $apiClient = $this->get('puzzle_connect.api_client');
            $response = $apiClient->push('delete', '/media/files/'.$id);
            
            if ($request->isXmlHttpRequest() === true) {
                return new JsonResponse($response);
            }
            
            return $this->redirectToRoute('admin_media_file_list');
        }catch (BadResponseException $e) {
            /** @var EventDispatcher $dispatcher */
            $dispatcher = $this->get('event_dispatcher');
            $event  = $dispatcher->dispatch(ApiEvents::API_BAD_RESPONSE, new ApiResponseEvent($e, $request));
            
            if ($request->isXmlHttpRequest() === true) {
                return $event->getResponse();
            }
            
            return $this->redirectToRoute('admin_media_file_list');
        }
    }
}
