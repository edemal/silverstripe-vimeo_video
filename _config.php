<?php

if(defined('VIMEO_CLIENT_ID')){
    
    VimeoVideoFile::set_vimeo_client_id(VIMEO_CLIENT_ID);
    VimeoVideoFile::set_vimeo_client_secret(VIMEO_CLIENT_SECRET);
    VimeoVideoFile::set_vimeo_access_token(VIMEO_ACCESS_TOKEN);
}else{
	die('Missing Vimeo Credentials on VimeoVideoFile');
}