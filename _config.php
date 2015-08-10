<?php

if(defined('VIMEO_CLIENT_ID') && defined('VIMEO_CLIENT_SECRET') && defined('VIMEO_ACCESS_TOKEN')){
    
    Config::inst()->update('VimeoVideoFile', 'client_id', VIMEO_CLIENT_ID);
	Config::inst()->update('VimeoVideoFile', 'client_secret', VIMEO_CLIENT_SECRET);
	Config::inst()->update('VimeoVideoFile', 'access_token', VIMEO_ACCESS_TOKEN);
	
	if(defined('VIMEO_ALBUM_ID')) Config::inst()->update('VimeoVideoFile', 'album_id', VIMEO_ALBUM_ID);
	if(defined('VIMEO_PLAYER_PRESET_ID')) Config::inst()->update('VimeoVideoFile', 'preset_id', VIMEO_PLAYER_PRESET_ID);
}else{
	die('Missing Vimeo Credentials on VimeoVideoFile');
}