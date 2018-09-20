<?php
namespace Puzzle\Admin\MediaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Puzzle\Admin\MediaBundle\Form\Type\FolderUpdateType;
use Puzzle\Admin\MediaBundle\Form\Type\FolderCreateType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use GuzzleHttp\Exception\BadResponseException;
use Puzzle\ConnectBundle\ApiEvents;
use Puzzle\ConnectBundle\Event\ApiResponseEvent;
use Puzzle\ConnectBundle\Service\PuzzleApiObjectManager;

/**
 * 
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 *
 */
class FolderController extends Controller
{
    /**
     * @var array $fields
     */
    private $fields;
    
    public function __construct() {
        $this->fields = ['name', 'parent', 'tag', 'filter', 'allowedExtensions'];
    }
    
	/***
	 * Show All Media
	 *
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @Security("has_role('ROLE_MEDIA') or has_role('ROLE_ADMIN')")
	 */
    public function listAction(Request $request, $current = "NULL") {
        /** @var Puzzle\ConectBundle\Service\PuzzleAPIClient $apiClient */
        $apiClient = $this->get('puzzle_connect.api_client');
        
        $user = $this->getUser();
        $criteria = [];
        
        try {
            if ($current === 'NULL') {
                $currentFolder = null;
                $current = $apiClient->pull('/media/folders', ['filter' => 'name=='.$user->getEmail()]);
                $current = $current ? $current[0]['id'] : 'NULL';
            }else {
                $currentFolder = $apiClient->pull('/media/folders/'.$current);
            }
            
            $criteria['filter'] = 'parent=='.$current;
            $folders = $apiClient->pull('/media/folders', $criteria);
            
            if ($currentFolder && isset($currentFolder['_embedded']) && isset($currentFolder['_embedded']['parent'])) {
                $parent = $currentFolder['_embedded']['parent'];
            }else {
                $parent = null;
            }
        }catch (BadResponseException $e) {
            /** @var EventDispatcher $dispatcher */
            $dispatcher = $this->get('event_dispatcher');
            $dispatcher->dispatch(ApiEvents::API_BAD_RESPONSE, new ApiResponseEvent($e, $request));
            $folders = $currentFolder = $parent = [];
        }
       
		return $this->render("PuzzleAdminMediaBundle:Folder:list.html.twig",[
		    'folders'         => $folders,
		    'currentFolder'   => $currentFolder,
		    'parent'          => $parent
		]);
	}
	
	
	/***
	 * Browse in folders
	 *
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @Security("has_role('ROLE_MEDIA') or has_role('ROLE_ADMIN')")
	 */
	public function listForModalAction(Request $request){
	    $current = $request->get('folder') ?? 'NULL';
	    $criteria = [];
	    $criteria['filter'] = 'parent=='.$current;
	    
	    try {
	        //** @var Puzzle\ConectBundle\Service\PuzzleAPIClient $apiClient */
    	    $apiClient = $this->get('puzzle_connect.api_client');
    	    $folders = $apiClient->pull('/media/folders', $criteria);
    	    $currentFolder = $current != "NULL" ? $apiClient->pull('/media/folders/'.$current) : null;
    	    
    	    if ($currentFolder && isset($currentFolder['_embedded']) && isset($currentFolder['_embedded']['parent'])) {
    	        $parent = $currentFolder['_embedded']['parent'];
    	    }else {
    	        $parent = null;
    	    }
	    }catch (BadResponseException $e) {
	        /** @var EventDispatcher $dispatcher */
	        $dispatcher = $this->get('event_dispatcher');
	        $dispatcher->dispatch(ApiEvents::API_BAD_RESPONSE, new ApiResponseEvent($e, $request));
	        $folders = $currentFolder = $parent = [];
	    }
	    
	    return $this->render("PuzzleAdminMediaBundle:Folder:browse.html.twig", array(
	        'currentFolder'    => $currentFolder,
	        'preserveFiles'    => $request->query->get('preserve_files') ?? 1,
	        'parent'           => $parent,
	        'folders'          => $folders
	    ));
	}
	
    /***
     * Create Folder Form
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Security("has_role('ROLE_MEDIA') or has_role('ROLE_ADMIN')")
     */
    public function createAction(Request $request) {
        $parentId = $request->query->get('parent');
        $data = PuzzleApiObjectManager::hydratate($this->fields, ['parent' => $parentId]);
        $path = !$parentId ? $this->generateUrl('admin_media_folder_create') : $this->generateUrl('admin_media_folder_create', ['parent' => $parentId]);
        $form = $this->createForm(FolderCreateType::class, $data, [
            'method' => 'POST',
            'action' => $path
        ]);
        $form->add('parent', HiddenType::class);
        $form->handleRequest($request);
            
        if ($form->isSubmitted() === true && $form->isValid() === true) {
            try {
                $postData = $form->getData();
                $postData = PuzzleApiObjectManager::sanitize($postData);
                
                /** @var Puzzle\ConectBundle\Service\PuzzleAPIClient $apiClient */
                $apiClient = $this->get('puzzle_connect.api_client');
                $apiClient->push('post', '/media/folders', $postData);
                
                if ($parentId !== null) {
                    return $this->redirectToRoute('admin_media_folder_show', array('id' => $parentId));
                }
            }catch (BadResponseException $e) {
                /** @var EventDispatcher $dispatcher */
                $dispatcher = $this->get('event_dispatcher');
                $dispatcher->dispatch(ApiEvents::API_BAD_RESPONSE, new ApiResponseEvent($e, $request));
            }
            
            return $this->redirectToRoute('admin_media_folder_list');
        }
        
        return $this->render("PuzzleAdminMediaBundle:Folder:create.html.twig", [
            'form' => $form->createView()
        ]);
    }
    
    /***
     * Show Folder
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Security("has_role('ROLE_MEDIA') or has_role('ROLE_ADMIN')")
     */
    public function showAction(Request $request, $id) {
        try {
            /** @var Puzzle\ConectBundle\Service\PuzzleAPIClient $apiClient */
            $apiClient = $this->get('puzzle_connect.api_client');
            $folder = $apiClient->pull('/media/folders/'.$id);
            
            if (isset($folder['files']) && count($folder['files']) > 0){
                $criteria = [];
                $criteria['filter'] = 'id=:'.implode(';', $folder['files']);
                
                $files = $apiClient->pull('/media/files', $criteria);
            }else {
                $files = null;
            }
            
            $parent = null;
            if (isset($folder['_embedded'])) {
                $parent = $folder['_embedded']['parent'] ?? null;
            }
        }catch (BadResponseException $e) {
            /** @var EventDispatcher $dispatcher */
            $dispatcher = $this->get('event_dispatcher');
            $dispatcher->dispatch(ApiEvents::API_BAD_RESPONSE, new ApiResponseEvent($e, $request));
            $folder = $files = $parent = [];
        }
        
        return $this->render("PuzzleAdminMediaBundle:Folder:show.html.twig", array(
            'folder' => $folder,
            'files' => $files,
            'parent' => $parent
        ));
    }
    
    /***
     * Update folder
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Security("has_role('ROLE_MEDIA') or has_role('ROLE_ADMIN')")
     */
    public function updateAction(Request $request, $id) {
        /** @var Puzzle\ConectBundle\Service\PuzzleAPIClient $apiClient */
        $apiClient = $this->get('puzzle_connect.api_client');
        $data = [];
        
        try {
            $folder = $apiClient->pull('/media/folders/'.$id);
            $parentId = $folder['_embedded']['parent']['id'] ?? null;
            $data = PuzzleApiObjectManager::hydratate($this->fields, [
                'name'              => $folder['name'],
                'parent'            => $parentId,
                'tag'               => $folder['tag'] ?? null,
                'filter'            => '',
                'allowedExtensions' => isset($folder['allowed_extensions']) ? implode('|', $folder['allowed_extensions']) : null
            ]);
        }catch (BadResponseException $e) {
            /** @var EventDispatcher $dispatcher */
            $dispatcher = $this->get('event_dispatcher');
            $dispatcher->dispatch(ApiEvents::API_BAD_RESPONSE, new ApiResponseEvent($e, $request));
            $folder = [];
        }
        
        $form = $this->createForm(FolderUpdateType::class, $data, [
            'method' => 'POST',
            'action' => $this->generateUrl('admin_media_folder_update', ['id' => $id])
        ]);
        $form->add('parent', HiddenType::class);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() === true && $form->isValid() === true) {
            try {
                $postData = $form->getData();
                $postData = PuzzleApiObjectManager::sanitize($postData);
                
                /** @var Puzzle\ConectBundle\Service\PuzzleAPIClient $apiClient */
                $apiClient = $this->get('puzzle_connect.api_client');
                $apiClient->push('put', '/media/folders/'.$folder['id'], $postData);
                
                if ($parentId !== null) {
                    return $this->redirectToRoute('admin_media_folder_show', array('id' => $parentId));
                }
                
            }catch (BadResponseException $e) {
                /** @var EventDispatcher $dispatcher */
                $dispatcher = $this->get('event_dispatcher');
                $dispatcher->dispatch(ApiEvents::API_BAD_RESPONSE, new ApiResponseEvent($e, $request));
            }
            
            return $this->redirectToRoute('admin_media_folder_list');
        }
        
        return $this->render("PuzzleAdminMediaBundle:Folder:update.html.twig", [
            'folder' => $folder,
            'form' => $form->createView()
        ]);
    }
    
    /**
     * Update Folder by adding file
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @Security("has_role('ROLE_MEDIA') or has_role('ROLE_ADMIN')")
     */
    public function addFilesAction(Request $request, $id) {
        $data = $request->request->all();
        $data['preserve_files'] = $request->query->get('preserve_files') ?? 1;
        
        $filesToAdd = $data['files_to_add'];
        $filesToAdd = is_string($filesToAdd) ? explode(',', $filesToAdd) : $filesToAdd;
        
        try {
             /** @var Puzzle\ConectBundle\Service\PuzzleAPIClient $apiClient */
            $apiClient = $this->get('puzzle_connect.api_client');
            $response = $apiClient->push('put', '/media/folders/'.$id, $data);
            
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse($response, $response['code']);
            }
        }catch (BadResponseException $e) {
            /** @var EventDispatcher $dispatcher */
            $dispatcher = $this->get('event_dispatcher');
            $dispatcher->dispatch(ApiEvents::API_BAD_RESPONSE, new ApiResponseEvent($e, $request));
        }
        
        return $this->redirectToRoute('admin_media_folder_show', array('id' => $id));
    }
    
    /**
     * Update Folder by adding file
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @Security("has_role('ROLE_MEDIA') or has_role('ROLE_ADMIN')")
     */
    public function removeFilesAction(Request $request, $id){
        $data = $request->request->all();
        
        try {
            if (isset($data['files_to_add'])) {
                $data['files_to_remove'] = is_string($data['files_to_remove']) ? explode(',', $data['files_to_remove']) : $data['files_to_remove'];
                /** @var Puzzle\ConectBundle\Service\PuzzleAPIClient $apiClient */
                $apiClient = $this->get('puzzle_connect.api_client');
                $apiClient->push('put', '/media/folders/'.$id, $data);
            }
            
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['status' => true]);
            }
        }catch (BadResponseException $e) {
            /** @var EventDispatcher $dispatcher */
            $dispatcher = $this->get('event_dispatcher');
            $dispatcher->dispatch(ApiEvents::API_BAD_RESPONSE, new ApiResponseEvent($e, $request));
        }
        
        return $this->redirectToRoute('admin_media_folder_show', array('id' => $id));
    }
    
    /***
     * Remove Folder
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Security("has_role('ROLE_MEDIA') or has_role('ROLE_ADMIN')")
     */
    public function deleteAction(Request $request, $id) {
        try {
            /** @var Puzzle\ConectBundle\Service\PuzzleAPIClient $apiClient */
            $apiClient = $this->get('puzzle_connect.api_client');
            $folder = $apiClient->pull('/media/folders/'.$id);
            $parentId = $folder['_embedded']['parent']['id'] ?? null;
            
            if ($parentId){
                $route = $this->redirectToRoute('admin_media_folder_show', array('id' => $parentId));
            }else{
                $route = $this->redirectToRoute('admin_media_folder_list');
            }
            
            $response = $apiClient->push('delete', '/media/folders/'.$id);
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse($response);
            }
            
            $this->addFlash('success', $this->get('translator')->trans('message.delete', [], 'success'));
            
            return $route;
        }catch (BadResponseException $e) {
            /** @var EventDispatcher $dispatcher */
            $dispatcher = $this->get('event_dispatcher');
            $event = $dispatcher->dispatch(ApiEvents::API_BAD_RESPONSE, new ApiResponseEvent($e, $request));
            
            if ($request->isXmlHttpRequest() === true) {
                return $event->getResponse();
            }
            
            return $this->redirect($this->generateUrl('admin_media_folder_list'));
        }
    }
    
    /**
     * @param Request $request
     * @param string $id
     * @throws BadRequestHttpException
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @Security("has_role('ROLE_MEDIA') or has_role('ROLE_ADMIN')")
     */
    public function compressAction(Request $request, $id) {
        try {
            /** @var Puzzle\ConectBundle\Service\PuzzleAPIClient $apiClient */
            $apiClient = $this->get('puzzle_connect.api_client');
            $folder = $apiClient->pull('/media/folders/'.$id);
            
            $source = $folder['_embedded']['absolutePath'];
            $zip = $folder['_embedded']['absolutePath'].'.zip';
            exec('zip '. $zip ." ". $source);
    //         $dest = $this->get('media.file_manager')->zipDir($folder->getAbsolutePath());
    //         if ($dest === false) {
    //             return new JsonResponse(['status' => false]);
    //         }
            
            return new JsonResponse(['status' => true, 'target' => $folder['path'].'.zip']);
        }catch (BadResponseException $e) {
            /** @var EventDispatcher $dispatcher */
            $dispatcher = $this->get('event_dispatcher');
            $dispatcher->dispatch(ApiEvents::API_BAD_RESPONSE, new ApiResponseEvent($e, $request));
        }
        
        return $this->redirectToRoute('admin_media_folder_show', array('id' => $parentId));
    }
}
