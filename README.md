BURDPosts
=========

Wordpress plugin to rip Facebook page posts and save them into your Wordpress posts.

Current release v1.0.0b (BETA)

IMPORTANT!!! Use carefully still beta!

Known Issues
============
Clicking "Fetch..." link again will insert duplicate posts and images.

Requirements
============
PHP 5.4+ (For facebook sdk)

Installation
============
# Move 'burdposts' folder into wp-content/plugins/
# Activate plugin via http://yourwebsite/wp-admin/plugins.php
# Click "Settings->BURDPosts"
# Populate the following
    APP ID
    APP Secret
# Click "Login" link
# Click "Save changes" button (This will store your access_token
# Click "My Page accounts" (This will list all your known managed Facebook pages pageids and access_tokens
# Copy the desired pageid into "Page ID" field
# Click "Save changes" button

Plugin is now ready for previewing and ripping posts.

How to use
==========
1) Click "Page feed" link to preview posts (You can scroll down and page through the posts
2) Click "Fetch 1 months" link which will do the following

    - Save posts
    - Save images related to posts

