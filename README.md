BURDPosts
=========

Wordpress plugin to rip Facebook page posts and save them into your Wordpress posts.

Current release v1.0.0b (BETA.

IMPORTANT!!! Use carefully still beta!

Known Issues
============
Clicking "Fetch..." link again will insert duplicate posts and images.

Requirements
============
PHP 5.4+ (For facebook sdk.

Installation
============
1. Move 'burdposts' folder into wp-content/plugins/
2. Activate plugin via http://yourwebsite/wp-admin/plugins.php
3. Click "Settings->BURDPosts"
4. Populate the following
    APP ID
    APP Secret
5. Click "Login" link
6. Click "Save changes" button (This will store your access_token
6. Click "My Page accounts" (This will list all your known managed Facebook pages pageids and access_tokens
7. Copy the desired pageid into "Page ID" field
8. Click "Save changes" button

Plugin is now ready for previewing and ripping posts.

How to use
==========
1. Click "Page feed" link to preview posts (You can scroll down and page through the posts
2. Click "Fetch 1 months" link which will do the following
 1. Save posts
 2. Save images related to posts

