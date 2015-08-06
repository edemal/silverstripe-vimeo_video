# Silverstripe Vimeo Video File - (c) Eduard Malyj (and others) 2015

## Silverstripe Version 3.1.13

## Maintainer Contact 
 * Eduard Malyj
   <eduard.malyj (at) gmail (dot) com>

 * Andre Lohmann (Nickname: andrelohmann)
  <lohmann dot andre at googlemail dot com>
 

## Overview
this module offes an extended VideoFile Object with automatically upload functionality to your vimeo pro account.
the module extends andrelohmann-silverstripe/extendedobjects.

## Usage

Add Vimeo Credentials to your _ss_environment.php

 * define('VIMEO_CLIENT_ID', 'YOUR_CLIENT_ID');
 * define('VIMEO_CLIENT_SECRET', 'YOUR_CLIENT_SECRET');
 * define('VIMEO_ACCESS_TOKEN', 'YOUR_ACCESS_TOKEN');
 * define('VIMEO_ALBUM_ID', 'YOUR_ALBUM_ID'); // optional, put every uploaded video in to a defined album
 * define('VIMEO_PLAYER_PRESET_ID', 'YOUR_PLAYER_PRESET_ID'); // optional, set a embedded preset to every uploaded video

Use the following method to get the ids of your albums and presets

´´´
curl -X GET -H "Authorization: bearer VIMEO_ACCESS_TOKEN" https://api.vimeo.com/me/albums
curl -X GET -H "Authorization: bearer VIMEO_ACCESS_TOKEN" https://api.vimeo.com/me/presets
´´´
