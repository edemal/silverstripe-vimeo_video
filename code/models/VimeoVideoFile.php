<?php

class VimeoVideoFile extends VideoFile {

    
    private static $client_id = null;
    private static $client_secret = null;
    private static $access_token = null;
	private static $api_request_time = 900;
	
	private static $album_id = null;
    
	/**
	 * @config
	 * @var array List of allowed file extensions, enforced through {@link validate()}.
	 * 
	 * Note: if you modify this, you should also change a configuration file in the assets directory.
	 * Otherwise, the files will be able to be uploaded but they won't be able to be served by the
	 * webserver.
	 * 
	 *  - If you are running Apahce you will need to change assets/.htaccess
	 *  - If you are running IIS you will need to change assets/web.config 
	 *
	 * Instructions for the change you need to make are included in a comment in the config file.
	 */
	//private static $allowed_extensions = array(
	//	'avi','flv','m4v','mov','mp4','mpeg','mpg','ogv','webm','wmv'
	//);
	
	//const VIMEO_ERROR_
	
	private static $db = array(
		'VimeoProcessingStatus' => "Enum(array('unprocessed','uploading','processing','error','finished'))", // uploading, processing, finished
		'VimeoURI'   =>  'Varchar(255)',
        'VimeoLink'  =>  'Varchar(255)',
        'VimeoID' => 'Varchar(255)',
		'VimeoFullHDUrl' => 'Varchar(255)', // 1080p
		'VimeoHDUrl' => 'Varchar(255)', // 720p
		'VimeoSDUrl' => 'Varchar(255)', // 360p
		'VimeoMobileUrl' => 'Varchar(255)',
        'VimeoPicturesURI'  =>  'Varchar(255)'
	);
	
	private static $defaults = array(
		'VimeoProcessingStatus' => 'unprocessed'
    );
	
	protected function getLogFile(){
		return TEMP_FOLDER.'/VimeoVideoFileProcessing-ID-'.$this->ID.'-'.md5($this->getRelativePath()).'.log';
	}

    public function process($LogFile = false, $runAfterProcess = true) {
        
        if(!$LogFile) $LogFile = $this->getLogFile();
		
		switch($this->ProcessingStatus){
			case 'new':
				if(parent::process($LogFile, $runAfterProcess)){
					$this->vimeoProcess($LogFile, $runAfterProcess);
				}else{
					// Something went wrong
				}
			break;
			
			case 'finished':
				$this->vimeoProcess($LogFile, $runAfterProcess);
			break;
			
			case 'processing':
				// just do nothing
			break;
		
			case 'error':
				// just do nothing
			break;
		}
	}
		
	protected function vimeoProcess($LogFile, $runAfterProcess = true){
		
		$Message = "[LOGTIME: ".date("Y-m-d H:i:s")."]\nvimeoPress() started\n";
		file_put_contents($LogFile, $Message, FILE_APPEND | LOCK_EX);
		
		switch($this->VimeoProcessingStatus){
			case 'unprocessed':
				
				// upload the Video
				$this->vimeoUpload($LogFile);
				
				if($this->VimeoProcessingStatus == 'finished' && $runAfterProcess) $this->onAfterProcess();
				
			break;
			
			case 'uploading':
				// just do nothing
			break;
		
			case 'processing':
				// just do nothing
			break;
		
			case 'error':
				// just do nothing
			break;
		
			case 'finished':
				// just do nothing
			break;
		}
		
	}
	
	protected function vimeoUpload($LogFile){
		
		$this->VimeoProcessingStatus = 'uploading';
		$this->write();
		
		try {
			
			$Message = "[LOGTIME: ".date("Y-m-d H:i:s")."]\nvimeoUpload() started\n";
			file_put_contents($LogFile, $Message, FILE_APPEND | LOCK_EX);
			
			if($lib = new \Vimeo\Vimeo(Config::inst()->get('VimeoVideoFile', 'client_id'), Config::inst()->get('VimeoVideoFile', 'client_secret'), Config::inst()->get('VimeoVideoFile', 'access_token'))){
				// Send a request to vimeo for uploading the new video
				$uri = $lib->upload($this->getFullPath(), false);
				//$uri = "/videos/134840586";
				$video_data = $lib->request($uri);
				
				$Message = "[LOGTIME: ".date("Y-m-d H:i:s")."]\nVimeo Video Data returned\n".print_r($video_data, true)."\n\n";
				file_put_contents($LogFile, $Message, FILE_APPEND | LOCK_EX);
				
				if($video_data['status'] == 200){
					$this->VimeoURI = $video_data['body']['uri'];
					$this->VimeoLink = $video_data['body']['link'];
					$id = explode('/',$video_data['body']['uri']);
					$this->VimeoID = $id[count($id)-1];
					$this->write();
					
					$Message = "[LOGTIME: ".date("Y-m-d H:i:s")."]\nFile uploaded to Vimeo\n";
					file_put_contents($LogFile, $Message, FILE_APPEND | LOCK_EX);

					if($video_data['body']['status'] != 'available'){
						$this->VimeoProcessingStatus = 'processing';
						$this->write();
						return false;
					}else{
						return $this->extractUrls($video_data);
					}
					
				} else {
					$error = "Error in Upload";
					$Message = "[LOGTIME: ".date("Y-m-d H:i:s")."]\n".$error."\n";
					$Message.= print_r($video_data, true)."\n\n";
					
					file_put_contents($LogFile, $Message, FILE_APPEND | LOCK_EX);
					
					$this->VimeoProcessingStatus = 'unprocessed';
					$this->write();
					return false;
				}
			
            } else {
				$error = "Missing clientID or clientSecret";
                $Message = "[LOGTIME: ".date("Y-m-d H:i:s")."]\n".$error."\n";
				
				file_put_contents($LogFile, $Message, FILE_APPEND | LOCK_EX);
				
				$this->VimeoProcessingStatus = 'unprocessed';
				$this->write();
				return false; 
			}
		} catch(\Vimeo\Exceptions\VimeoRequestException $ex) {
			$Message = "[LOGTIME: ".date("Y-m-d H:i:s")."]\n".$ex."\n";
			file_put_contents($LogFile, $Message, FILE_APPEND | LOCK_EX);
			return false;
		} catch (\Vimeo\Exceptions\VimeoUploadException $ex) {
			$Message = "[LOGTIME: ".date("Y-m-d H:i:s")."]\n".$ex."\n";
			file_put_contents($LogFile, $Message, FILE_APPEND | LOCK_EX);
			return false;
		}
         
    }
	
	protected function extractUrls($data){
		// if status is "available", we need to check if allready all resolution files are really available
		if($data['body']['status'] == 'available'){
			// fetch source resolution
			$sourceMeasures = array();
			foreach($data['body']['download'] as $dl){
				if(isset($dl['type']) && $dl['type'] == 'source'){
					$sourceMeasures['width'] = $dl['width'];
					$sourceMeasures['height'] = $dl['height'];
				}
			}
			
			// fetch available resolution
			$availRes = array();
			foreach($data['body']['files'] as $f){
				if(isset($f['quality']) && isset($f['width']) && isset($f['height']) && isset($f['link_secure'])){
					if($f['quality'] == 'mobile') $availRes['mobile'] = $f['link_secure'];
					else if($f['quality'] == 'sd') $availRes['sd'] = $f['link_secure'];
					else if($f['quality'] == 'hd' && $f['height'] == 720) $availRes['hd'] = $f['link_secure'];
					else if($f['quality'] == 'hd' && $f['height'] == 1080) $availRes['fullhd'] = $f['link_secure'];
				}
			}
			
			if(isset($sourceMeasures['width']) && isset($sourceMeasures['height'])){
				// source file and measurements found
				// check for highest resolution
				if($sourceMeasures['width'] >= 1920 && $sourceMeasures['height'] >= 1080){
					// Video is full HD, so Full HD should be availalbe
					if(isset($availRes['fullhd']) && isset($availRes['hd']) && isset($availRes['sd']) && isset($availRes['mobile'])){
						$this->VimeoFullHDUrl = $availRes['fullhd'];
						$this->VimeoHDUrl = $availRes['hd'];
						$this->VimeoSDUrl = $availRes['sd'];
						$this->VimeoMobileUrl = $availRes['mobile'];
						$this->VimeoPicturesURI = $data['body']['pictures']['uri'];
						$this->VimeoProcessingStatus = 'finished';
						$this->write();
						return true;
					}else{
						return false;
					}
				}else if($sourceMeasures['width'] >= 1280 && $sourceMeasures['height'] >= 720){
					// Video is HD, so at least HD schould be available
					if(isset($availRes['hd']) && isset($availRes['sd']) && isset($availRes['mobile'])){
						$this->VimeoHDUrl = $availRes['hd'];
						$this->VimeoSDUrl = $availRes['sd'];
						$this->VimeoMobileUrl = $availRes['mobile'];
						$this->VimeoPicturesURI = $data['body']['pictures']['uri'];
						$this->VimeoProcessingStatus = 'finished';
						$this->write();
						return true;
					}else{
						return false;
					}
				}else{
					// Video is SD, so at least SD schould be available
					if(isset($availRes['sd']) && isset($availRes['mobile'])){
						$this->VimeoSDUrl = $availRes['sd'];
						$this->VimeoMobileUrl = $availRes['mobile'];
						$this->VimeoPicturesURI = $data['body']['pictures']['uri'];
						$this->VimeoProcessingStatus = 'finished';
						$this->write();
						return true;
					}else{
						return false;
					}
				}
			}
		}
	}
	
	public function IsProcessed(){
		if($this->VimeoProcessingStatus == 'finished'){
			return true;
		}else{
			$cache = SS_Cache::factory('VimeoVideoFile_ApiRequest');
			SS_Cache::set_cache_lifetime('VimeoVideoFile_ApiRequest', Config::inst()->get('VimeoVideoFile', 'api_request_time')); // set the waiting time
			if(!($result = $cache->load($this->ID))){
				
				switch($this->VimeoProcessingStatus){
					
					case 'unprocessed':
						$this->process();
					break;
				
					case 'processing':
						$lib = new \Vimeo\Vimeo(Config::inst()->get('VimeoVideoFile', 'client_id'), Config::inst()->get('VimeoVideoFile', 'client_secret'), Config::inst()->get('VimeoVideoFile', 'access_token'));
						// Send a request to vimeo for uploading the new video
						$video_data = $lib->request($this->VimeoURI);
						
						$this->extractUrls($video_data);
						
						// Set Title and Album
						$lib->request($this->VimeoURI, array('name' => $this->Name), 'PATCH');
						if(Config::inst()->get('VimeoVideoFile', 'album_id')){
							$res = $lib->request('/me/albums/'.Config::inst()->get('VimeoVideoFile', 'album_id').$this->VimeoURI, array(), 'PUT');
						}
					break;
				}
				
				$result = $this->VimeoProcessingStatus;
				$cache->save($result, $this->ID);
			}
			return ($result == 'finished');
		}
	}
	
	public function VimeoURI() {
		if(!($this->VimeoProcessingStatus == 'error' || $this->VimeoProcessingStatus == 'unprocessed')){
			return $this->VimeoURI;
		}else{
			return false;
		}
    }
    
    public function VimeoLink () {
		if(!($this->VimeoProcessingStatus == 'error' || $this->VimeoProcessingStatus == 'unprocessed')){
			return $this->VimeoLink;
		}else{
			return false;
		}
    }
    
    public function VimeoID () {
        if(!($this->VimeoProcessingStatus == 'error' || $this->VimeoProcessingStatus == 'unprocessed')){
			return $this->VimeoID;
		}else{
			return false;
		}
    }
    
    public function getFullHDUrl() {
        if($this->VimeoProcessingStatus == 'finished'){
			if($this->VimeoFullHDUrl) return $this->VimeoFullHDUrl;
			else return $this->getHDUrl();
		}else{
			return false;
		}
    }
    
    public function getHDUrl() {
        if($this->VimeoProcessingStatus == 'finished'){
			if($this->VimeoHDUrl) return $this->VimeoHDUrl;
			else return $this->getSDUrl();
		}else{
			return false;
		}
    }
    
    public function getSDUrl() {
        if($this->VimeoProcessingStatus == 'finished'){
			if($this->VimeoSDUrl) return $this->VimeoSDUrl;
			else return $this->getMobileUrl();
		}else{
			return false;
		}
    }
    
    public function getMobileUrl() {
        if($this->VimeoProcessingStatus == 'finished')
			if($this->VimeoMobileUrl)
				return $this->VimeoMobileUrl;
			
		return false;
    }
	
	
	public function setPreviewImage(SecureImage $Img){
		
		if(!($this->PreviewImage() instanceof VideoImage)){
			$this->PreviewImage()->delete();
		}
		$this->PreviewImageID = $Img->ID;
		
		$lib = new \Vimeo\Vimeo(Config::inst()->get('VimeoVideoFile', 'client_id'), Config::inst()->get('VimeoVideoFile', 'client_secret'), Config::inst()->get('VimeoVideoFile', 'access_token'));
        
        $video_data = $lib->uploadImage($this->VimeoURI.'/pictures', $Img->getFullPath(), true); // Upload the PreviewPicture as Default
		
		$this->write();
		
	}
     
    
    protected function onBeforeDelete() {
        parent::onBeforeDelete();
        
        $lib = new \Vimeo\Vimeo(Config::inst()->get('VimeoVideoFile', 'client_id'), Config::inst()->get('VimeoVideoFile', 'client_secret'), Config::inst()->get('VimeoVideoFile', 'access_token'));
        
        $video_data = $lib->request($this->VimeoURI, array(), 'DELETE');
    }
	
	protected function onAfterProcess() {
		parent::onAfterProcess();
	}

}
