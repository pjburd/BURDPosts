<?php
/**
 * @package burdposts
 */
/*
Plugin Name: BURDPosts
Plugin URI: http://tba/
Description: Integrate all posts into Wordpress and be able to tag them.  Improve SEO of website.  speed of publishing archived posts
Version: 1.0.0b
Author: Paul Burden
Author URI: http://tba/
License: NO license
Text Domain: BURDPOSTS
*/

// Copyright 2016 Paul Burden


/*************
 * Resources *
 *************/
// Constants
define( 'BURDPOSTS__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BURDPOSTS__FB_REDIRECT', 'http://'. $_SERVER["HTTP_HOST"] . '/wp-admin/options-general.php?page=burdposts-setting-admin');

require_once( BURDPOSTS__PLUGIN_DIR . 'burdposts_media.php');
require_once( BURDPOSTS__PLUGIN_DIR . 'burdposts_fb.php');
require_once( BURDPOSTS__PLUGIN_DIR . 'options.php');

if( is_admin() )
    $burdposts_settings_page = new BURDPostsSettingsPage();
