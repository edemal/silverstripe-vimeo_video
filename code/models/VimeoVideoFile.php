<?php

class VimeoVideoFile extends VideoFile {

    
    private static $clientID = VIMEO_CLIENT_ID;
    private static $clientSecret = VIMEO_SECRET_ID;
    private static $accessToken = VIMEO_ACCESS_TOKEN;
    

    private static $db = array(
        
        'VimeoURI'   =>  'Varchar',
        'VimeoLink'  =>  'Varchar',
        'VimeoEmbed' => 'HTMLText'
        
    );

    public function process($LogFile = false) {
        
        if(!$LogFile) $LogFile = TEMP_FOLDER.'/VimeoVideoFileProcessing-ID-'.$this->ID.'-'.md5($this->getRelativePath()).'.log';
            
        if($this->ProcessingStatus == 'new'){
                    $this->ProcessingStatus = 'processing';
                    $this->write();

                    $lib = false;
                    $uploaded = [];
                    
                    if (self::$clientID && self::$clientSecret) {
                        if (!self::$accessToken) {
                            print 'Error: missing accessToken';
                            // TODO Generate Access Token
                        }
                        else {
                            $lib = new \Vimeo\Vimeo(VIMEO_CLIENT_ID, VIMEO_SECRET_ID, VIMEO_ACCESS_TOKEN);
                            
                            // upload via URL
                            //$response = $lib->request('/me/videos',array("type" => "pull", "link" => $this->getAbsoluteURL()),"PUT");
                            
                            try {
                                // Send a request to vimeo for uploading the new video
                                $uri = $lib->upload($this->getFullPath(), false);
                                
                                $video_data = $lib->request($uri);
                                
                                if($video_data['status'] == 200) {
                                    $this->VimeoURI = $video_data['body']['uri'];
                                    $this->VimeoLink = $video_data['body']['link'];
                                    $this->VimeoEmbed = $video_data['body']['embed']['html'];
                                }
                                
                                $uploaded = array('file' => $this->getFullPath(), 'api_video_uri' => $uri, 'link' => $this->VimeoLink , 'embed' => $this->VimeoEmbed);
                                 
                            } catch (\Vimeo\Exceptions\VimeoUploadException $ex) {
                                $error = $ex->getMessage();
                            } catch (\Vimeo\Exceptions\VimeoRequestException $ex) {
                                $error = $ex->getMessage();
                            }
                        }
                    } else {
                        //todo log missing Client_ID or Secret_ID
                    }
                    
                    
                    
                             
                    $Message = "[LOGTIME: ".date("Y-m-d H:i:s")."]\nFile uploaded to Vimeo\n";
                    $Message.= "\n Upload info: \n";
                    $Message.= "\n uri: ".$uploaded['api_video_uri']."\n";
                    $Message.= "\n link: ".$uploaded['link']."\n";
                    $Message.= "\n embed: ".$uploaded['embed']."\n";
                    
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
    
    public function getEmbed () {
        return $this->getField('VimeoEmbed');
    }
    
    public function createEmbed ($width, $height) {
        $width = (!$width) ? $width : $this->Width;
        $height = (!$height) ? $height : $this->Height;
        
        
    }

    public function setAccessToken($access_token) {
        self::$accessToken = $access_token;
    }




}
