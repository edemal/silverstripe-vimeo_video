<?php

class VimeoVideoFile extends VideoFile {

    
    private static $clientID = null;
    private static $clientSecret = null;
    private static $accessToken = null;
        
    public static function set_vimeo_client_id($id){
		self::$clientID = $id;
    }
        
    public static function get_vimeo_client_id(){
		return self::$clientID;
    }
        
    public static function set_vimeo_client_secret($secret){
		self::$clientSecret = $secret;
    }
        
    public static function get_vimeo_client_secret(){
		return self::$clientSecret;
    }
        
    public static function set_vimeo_access_token($token){
		self::$accessToken = $token;
    }
        
    public static function get_vimeo_access_token(){
		return self::$accessToken;
    }
    

    private static $db = array(
        
        'VimeoURI'   =>  'Varchar',
        'VimeoLink'  =>  'Varchar',
        'VimeoEmbed' => 'HTMLText',
        'SDLink' => 'Varchar'
    );

    public function process($LogFile = false) {
        
        if(!$LogFile) $LogFile = TEMP_FOLDER.'/VimeoVideoFileProcessing-ID-'.$this->ID.'-'.md5($this->getRelativePath()).'.log';
            
        if($this->ProcessingStatus == 'new'){
                    $this->ProcessingStatus = 'processing';
                    $this->write();

                    $lib = false;
                    $uploaded = [];
                    $error = false;
                    
                    if (self::get_vimeo_client_id() && self::get_vimeo_client_secret()) {
                        if (!self::get_vimeo_access_token()) {
                            $error = "Missing access token";
                            // TODO Generate Access Token
                        }
                        else {
                            $lib = new \Vimeo\Vimeo(self::get_vimeo_client_id(), self::get_vimeo_client_secret(), self::get_vimeo_access_token());
                            
                            // upload via URL
                            //$response = $lib->request('/me/videos',array("type" => "pull", "link" => $this->getAbsoluteURL()),"PUT");
                            
                            try {
                                // Send a request to vimeo for uploading the new video
                                $uri = $lib->upload($this->getFullPath(), false);
                                
                                //$uri = "/videos/134840586";

                                $video_data = $lib->request($uri);

                                if($video_data['status'] == 200) {
                                    // Example: /videos/134844631
                                    $this->VimeoURI = $video_data['body']['uri'];

                                    // Example: https://vimeo.com/134844631
                                    $this->VimeoLink = $video_data['body']['link'];

                                    // Example: <iframe src="https://player.vimeo.com/video/134844631?title=0&byline=0&portrait=0&badge=0&autopause=0&player_id=0" width="400" height="300" frameborder="0" title="Untitled" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>
                                    $this->VimeoEmbed = $video_data['body']['embed']['html'];

                                    //$this->SDLink = $video_data['body']['files'][0]['link'];



                                    // TODO add HD if available
                                    /*
                                    if (count($video_data['body']['files']) > 1) {
                                        $this->HDLink = $video_data['body']['files'][1]['link'];
                                    }
                                     * */
                                    
                                    $this->write();

                            }

                            } catch (\Vimeo\Exceptions\VimeoRequestException $ex) {
                                
                            } catch (\Vimeo\Exceptions\VimeoUploadException $ex) {
                                
                            }
                            
                            
                            $uploaded = array('file' => $this->getFullPath(), 
                                'api_video_uri' => $uri, 
                                'link' => $this->VimeoLink, 
                                'embed' => $this->VimeoEmbed, 
                                //'sd' => $this->SDLink,
                                'body' => $video_data['body']);
                                 
                        }
                    } else {
                        $error = "Missing clientID or clientSecret";
                    }
                    
                    if ($error) {
                        $Message = "[LOGTIME: ".date("Y-m-d H:i:s")."]\n".$error."\n";
                    } else {
                        $Message = "[LOGTIME: ".date("Y-m-d H:i:s")."]\nFile uploaded to Vimeo\n";
                        $Message.= "\n Upload info: \n";
                        $Message.= "\n uri: ".$uploaded['api_video_uri']."\n";
                        $Message.= "\n link: ".$uploaded['link']."\n";
                        $Message.= "\n embed: ".$uploaded['embed']."\n";
                        //$Message.= "\n sd: ".$uploaded['sd']."\n";
                        //$Message.= "\n body: ".print_r($uploaded['body'],true)."\n";

                    }
                    
                    file_put_contents($LogFile, $Message, FILE_APPEND | LOCK_EX);
                    
                    $this->ProcessingStatus = 'new';
                    $this->write();

                    parent::process($LogFile);
                    
        }else{
            $Message = "[LOGTIME: ".date("Y-m-d H:i:s")."]\nFile allready processed\n";
            file_put_contents($LogFile, $Message, FILE_APPEND | LOCK_EX);
        }
         
    }


    public function getVimeoURI () {
        return $this->getField('VimeoURI');
    }
    
    public function getVimeoLink () {
        return $this->getField('VimeoLink');
    }
    
    public function getVideoLinkInSDFormat () {
        
        // TODO SD link as property
        $lib = new \Vimeo\Vimeo(self::get_vimeo_client_id(), self::get_vimeo_client_secret(), self::get_vimeo_access_token());
        
        $video_data = $lib->request($this->VimeoURI);
        
        $this->SDLink = $video_data['body']['files'][0]['link'];
        
        return $video_data['body']['files'][0]['link'];
        
        //return $this->getField('SDLink');
    }
    
    public function getVideoLinkInHDFormat () {
        if ($this->getField('HDLink')) {
            return $this->getField('HDLink');
        } else {
            return $this->getVideoLinkInSDFormat();
        }
        
    }
    
    public function getClicks () {
        $lib = new \Vimeo\Vimeo(self::get_vimeo_client_id(), self::get_vimeo_client_secret(), self::get_vimeo_access_token());
        
        $video_data = $lib->request($this->VimeoURI);
        
        
        
        return $video_data['body']['stats']['plays'];
    }
    
    // Simple src code for iframe tag
    // Result looks like "//player.vimeo.com/video/VIDEO_ID"
    // TODO add vimeo parameter
    public function createEmbedSRC () {
        $base = "http://player.vimeo.com/video/";
        
        $tempArr = explode('/', $this->getField('VimeoURI'));
        
        $id = $tempArr[2];
        
        return $base.$id;
    }
     
    
    protected function onBeforeDelete() {
        parent::onBeforeDelete();
        
        $lib = new \Vimeo\Vimeo(self::get_vimeo_client_id(), self::get_vimeo_client_secret(), self::get_vimeo_access_token());
        
        $video_data = $lib->request($this->VimeoURI, 'DELETE');
    }

}
