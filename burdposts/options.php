<?php

class BURDPostsSettingsPage
{
    
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {   
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
        add_action( 'admin_footer', array( $this, 'render_javascript') ); // Write our JS below here

        add_action( 'wp_ajax_burdposts_action', array( $this, 'burdposts_action_callback' ) );
   
        $this->options = get_option( 'burdposts_option_name' );   

     
    }    

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin', 
            'BURDPosts', 
            'manage_options', 
            'burdposts-setting-admin', 
            array( $this, 'create_admin_page' )
        );
    }    


function read_header($ch, $string) {
    print "Received header: $string";
    return strlen($string);
}

    /**
     * Prepare and insert post into wordpress db
     */
    private function burdposts_insert_post($use_platform, $post, $handle)
    {                        
        // Prep post insert
        switch($use_platform)
        {
            case 'fb':
                // Save posts into wp database
                $my_post = array(
                'post_title'    => $post->created_time,
                'post_content'  => $post->message,
                'post_status'   => 'publish',
                'post_date' => $post->created_time
                
                );
                
                // Convert links to HTML anchor links
                $my_post['post_content'] = preg_replace('$(https?://[a-z0-9_./?=&#-]+)(?![^<>]*>)$i', ' <a href="$1" target="_blank">$1</a> ', $my_post['post_content']." ");
                $my_post['post_content'] = preg_replace('$(www\.[a-z0-9_./?=&#-]+)(?![^<>]*>)$i', '<a target="_blank" href="http://$1"  target="_blank">$1</a> ', $my_post['post_content']." ");

                // Add link to comment
                if (isset($post->link))
                {
                    $my_post['post_content'] .= '<p><a href="'. $post->link. '" target="_blank">Link to post</a></p>';
                }
                
                // Set type of post
                switch($post->type)
                {
/*
//Not sure if need to handle link posts like this - It is a valid wordpress post type.
                    case 'link':
                        $my_post['post_type'] = 'link';
                        break;
*/
                    default:
                        $my_post['post_type'] = 'post';
                        break;
                }
                break;
        }
        
        if (!empty(trim($my_post['post_content'])))
        { 
            $post_id = wp_insert_post( $my_post );
            
            /****************
             * Attach photo *             
             ****************/
            $upload_dir = wp_upload_dir();
            
            switch($use_platform)
            {
                case 'fb':
                    if (isset($post->object_id))
                    {                    
                        $curr_year = date("Y");
                        $curr_month = date("m");
                        
                        $use_year = date("Y", strtotime($post->created_time) );
                        $use_month = date("m", strtotime($post->created_time) );
    
                        $use_filename = date("pY-m-d_h-i-s", strtotime($post->created_time) );
    
                        
                        $upload_dir['url'] = preg_replace('/\/'.$curr_year.'\//', '/'.$use_year.'/',$upload_dir['url']);
                        $upload_dir['url'] = preg_replace('/\/'.$curr_month.'\//','/'. $use_month.'/',$upload_dir['url']);
                     
                        $upload_dir['path'] = preg_replace('/\/'.$curr_year.'\//', '/'.$use_year.'/',$upload_dir['path']);
                        $upload_dir['path'] = preg_replace('/\/'.$curr_month.'\//','/'. $use_month.'/',$upload_dir['path']);
                     
                        $picture_url = $handle->get_picture_url($post->object_id);
/*                      
                        $content_type = get_headers($picture_url, 1)["Content-Type"];
                         
                        switch($content_type)
                        {
                            case 'image/jpeg': $use_format = 'jpg'; break;
                            case 'image/gif': $use_format = 'gif'; break;
                            case 'image/png': $use_format = 'png'; break;
                            default:
                                $use_format = preg_replace("/\//", "-", $content_type);                                
                                break;
                        }
*/
                        $picture_filename = $upload_dir['path'] .'/'. $use_filename.'.jpg';      
                        
                        // Random sleep to prevent pause
                        //usleep(rand(3000,5000));
                        
                        // Copy image from remote location    
                        BURDPosts_MEDIA::grab_image($picture_url, $picture_filename);                        
                        
                                                
/*
                        $file_content = implode("",file($picture_url));
                        $fp = fopen($picture_filename,"w+");                        
                        fwrite($fp, $file_content);                        
                        fclose($fp);
*/                        
                        
                        
                        // Prepare attachment for post
                        $filetype = wp_check_filetype( basename( $picture_filename ), null );
                        
                        $attachment = array(
                        	'guid'           => $upload_dir['url'] . '/' . basename( $picture_filename ), 
                        	'post_mime_type' => $filetype['type'],
                        	'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $picture_filename ) ),
                        	'post_status'    => 'inherit'
                        );
                        
                        // Attach image to post                        
                        require_once( ABSPATH . 'wp-admin/includes/image.php' );
                        
                        
                        add_theme_support( 'post-thumbnails' );
                        set_post_thumbnail_size( 400, 400 );
                        
                        $attach_id = wp_insert_attachment( $attachment, $picture_filename, $post_id );
                        $attach_data = wp_generate_attachment_metadata( $attach_id, $picture_filename );
                        wp_update_attachment_metadata( $attach_id,  $attach_data );
                        set_post_thumbnail( $post_id, $attach_id );
                        
                        
                    }
                    break;
            }
        
        }
        
        if ($post_id > 0)
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }

    /**
     * Ajax action handle
     */
    function burdposts_action_callback() {
    	global $wpdb; // this is how you get access to the database
    
    	$action_cmd =  $_POST['burdpost_cmd'] ;
    	$action_page =  $_POST['burdpost_page'] ;
    	$use_platform = 'fb';   // default use this platform.
    	
        $result = "Attempting to run command '" . $action_cmd . "'";

        // Set up handle depending on platform to use
        if (preg_match('/\_fb/', $action_cmd))  // Facebook platform
        {        
            $use_platform = 'fb';
            
            // Primary session handle for pagination handling
            $handle = new BURDPosts_FB($this->options['burdposts_fb_app_id'],
    	                               $this->options['burdposts_fb_app_secret'],
    	                               $this->options['burdposts_fb_accesstoken'],
    	                               $this->options['burdposts_fb_page_id']);

            // For making individial calls
            $obj_handle = new BURDPosts_FB($this->options['burdposts_fb_app_id'],
    	                               $this->options['burdposts_fb_app_secret'],
    	                               $this->options['burdposts_fb_accesstoken'],
    	                               $this->options['burdposts_fb_page_id']);
        }
        
        // Process action
        $action_cmd = preg_replace('/\_'.$use_platform.'/', '', $action_cmd);

            /**********************
             * Prepare categories *             
             **********************/     
           $use_categories = array();
           $use_categories[] = term_exists('BURDPosts', 'posts');

             $categories = array( "BURDPosts", );

            
            switch($use_platform)
            {
                case 'fb':
                    $use_categories[] = term_exists('Facebook', 'posts');
                    break;
            }
            
                
        switch($action_cmd)
        {
            case 'fetchmonth':
            case 'fetch2month':
            case 'fetch3month':
                $post_inserted_count = 0;
                                
                // Fetch first set of posts
                $posts = $handle->fetch_posts( preg_replace("/fetch/","",$action_cmd) );
                $posts_count = count($posts['data']);

                if ($posts_count > 0)
                {
                    // Insert first batch
                    foreach($posts['data'] as $post)
                    {
                        if ($this->burdposts_insert_post($use_platform, $post, $obj_handle))
                        {                                
                            $post_inserted_count ++;
                        }
                    }                        
    
                    // For each posts process posts and fetch next page of posts                
                    while ($posts = $handle->fetch_next_page())
                    {                    
                        $posts_count = count($posts['data']);                     
                        
                        if ($posts_count > 0)
                        {
                            foreach($posts['data'] as $post)
                            {
                                if ($this->burdposts_insert_post($use_platform, $post, $obj_handle))
                                {                                
                                    $post_inserted_count ++;
                                }
                            }                                               
                        }
                        else
                        {
                            break;
                        }
                    }
                }

                if ($post_inserted_count == 0)
                {
                    $result = "No posts found.";   
                }
                else
                {
                    $result = "Inserted ".$post_inserted_count." posts.";                    
                }
                break;
            default:    // Standard callback option    
            	$result = $handle->process_callback($action_cmd, $action_page);
                break;
        }

        echo $result;
    	wp_die(); // this is required to terminate immediately and return a proper response
    }
        
    
    /**
     * Options page callback
     */
    function render_javascript() { 
    ?>
    	<script type="text/javascript">

    	jQuery(document).ready(function($) {
            var data = {};
            var currCmd = '';
            
            function burdposts_action(action, page) {
                data = {
                    'action': 'burdposts_action',
                    'burdpost_cmd': action,
                    'burdpost_page': page
                }
                
            	$.post(ajaxurl, data, function(response) {
                	$('#results').html(response);
        		});            
            }
            	
    		$('.burdposts_btn').live( "click", function() {
        		var cmd = $(this).attr('data-cmd');
        		var page = $(this).attr('data-page');
        		
        		currCmd = cmd;
        		if (currCmd != cmd)
        		{
            		$('#burdposts-pagevalue').val(1); // reset page to 1
        		}
        		var pageNum = $('#burdposts-pagevalue').val();
        		
            	$('#results').html('[please wait...]');
                burdposts_action(cmd, page, pageNum);
                return false;
    		});

    		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
    	
    	});
    	</script> <?php
    }
    
    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'burdposts_option_name' );
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>BURDPosts</h2>           
            <form method="post" id="burdposts-form" action="options.php" style="border-top:1px solid black;">
                <input type="hidden" id="burdposts-pagevalue" name="burdposts-pagevalue" value="1" />
                <div style="float: left; width: 50%">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'burdposts_option_group' );   
                do_settings_sections( 'burdposts-setting-admin' );
                submit_button(); 
            ?>
                </div>
                <div style=" overflow: auto; width: 50%;">
                    <h4>options</h4>
                    
<?php
	$fb_handle = new BURDPosts_FB( $this->options['burdposts_fb_app_id'],
	                               $this->options['burdposts_fb_app_secret'],
	                               $this->options['burdposts_fb_accesstoken'],
	                               $this->options['burdposts_fb_page_id'],
	                               BURDPOSTS__FB_REDIRECT
	                               );
    if ($fb_handle->test_session())
    {
?>
        Now click "Save changes".

    	<script type="text/javascript" >

        	jQuery(document).ready(function($) {
                $('#burdposts_fb_accesstoken').val("<?php echo $fb_handle->get_longlived_accesstoken() ?>");
                
                // TODO: Ideally register submit form with wordpress
            });
        </script>
<?php      
    }
    else
    {
        echo $fb_handle->get_login_link();        
    }  
?>
                <p><a href="" class="burdposts_btn" data-cmd="mydetails_fb">My details</a></p>
                <p><a href="" class="burdposts_btn" data-cmd="myaccounts_fb">My page accounts</a> (Fetch page id and long lived page access tokens)</p>
                <p><a href="" class="burdposts_btn" data-cmd="fetchpageidfeed_fb">Page feed</a> (Preview posts)</p>
                <p><a href="" class="burdposts_btn" data-cmd="fetchmonth_fb">Fetch 1 months worth of posts</a></p>
                <p><a href="" class="burdposts_btn" data-cmd="fetch2month_fb">Fetch 2 months worth of posts</a></p>
                <p><a href="" class="burdposts_btn" data-cmd="fetch3month_fb">Fetch 3 months worth of posts</a></p>

                <div id="results"></div>                

                </div>
                
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'burdposts_option_group', // Option group
            'burdposts_option_name', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'burdposts_fb_section', // ID
            'Facebook settings', // Title
            array( $this, 'print_section_info' ), // Callback
            'burdposts-setting-admin' // Page
        );  

        add_settings_field(
            'burdposts_fb_app_id', // ID
            'APP ID', // Title 
            array( $this, 'callback_burdposts_fb_app_id' ), // Callback
            'burdposts-setting-admin', // Page
            'burdposts_fb_section' // Section           
        );   
        add_settings_field(
            'burdposts_fb_app_secret', // ID
            'APP Secret', // Title 
            array( $this, 'callback_burdposts_fb_app_secret' ), // Callback
            'burdposts-setting-admin', // Page
            'burdposts_fb_section' // Section           
        );      

        add_settings_field(
            'burdposts_fb_accesstoken', 
            'Access token', 
            array( $this, 'callback_burdposts_fb_accesstoken' ), 
            'burdposts-setting-admin', 
            'burdposts_fb_section'
        );      

        add_settings_field(
            'burdposts_fb_page_id', // ID
            'Page ID', // Title 
            array( $this, 'callback_burdposts_fb_page_id' ), // Callback
            'burdposts-setting-admin', // Page
            'burdposts_fb_section' // Section           
        );           
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['burdposts_fb_app_id'] ) )
            $new_input['burdposts_fb_app_id'] = sanitize_text_field( $input['burdposts_fb_app_id'] );

        if( isset( $input['burdposts_fb_app_secret'] ) )
            $new_input['burdposts_fb_app_secret'] = sanitize_text_field( $input['burdposts_fb_app_secret'] );

        if( isset( $input['burdposts_fb_accesstoken'] ) )
            $new_input['burdposts_fb_accesstoken'] = sanitize_text_field( $input['burdposts_fb_accesstoken'] );

        if( isset( $input['burdposts_fb_page_id'] ) )
            $new_input['burdposts_fb_page_id'] = sanitize_text_field( $input['burdposts_fb_page_id'] );
            
        return $new_input;
    }  
    

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Enter your settings below:';
    }
    
    /** 
     * Get the settings option array and print one of its values
     */
    public function callback_burdposts_fb_app_id()
    {
        printf(
            '<input type="text" id="burdposts_fb_app_id" name="burdposts_option_name[burdposts_fb_app_id]" value="%s" />',
            isset( $this->options['burdposts_fb_app_id'] ) ? esc_attr( $this->options['burdposts_fb_app_id']) : ''
        );
    }     

    /** 
     * Get the settings option array and print one of its values
     */
    public function callback_burdposts_fb_app_secret()
    {
        printf(
            '<input type="text" id="burdposts_fb_app_secret" name="burdposts_option_name[burdposts_fb_app_secret]" value="%s" />',
            isset( $this->options['burdposts_fb_app_secret'] ) ? esc_attr( $this->options['burdposts_fb_app_secret']) : ''
        );
    } 
    
    /** 
     * Get the settings option array and print one of its values
     */
    public function callback_burdposts_fb_accesstoken()
    {
        printf(
            '<input type="text" id="burdposts_fb_accesstoken" name="burdposts_option_name[burdposts_fb_accesstoken]" value="%s" />',
            isset( $this->options['burdposts_fb_accesstoken'] ) ? esc_attr( $this->options['burdposts_fb_accesstoken']) : ''
        );
    } 

    /** 
     * Get the settings option array and print one of its values
     */
    public function callback_burdposts_fb_page_id()
    {
        printf(
            '<input type="text" id="burdposts_fb_page_id" name="burdposts_option_name[burdposts_fb_page_id]" value="%s" />',
            isset( $this->options['burdposts_fb_page_id'] ) ? esc_attr( $this->options['burdposts_fb_page_id']) : ''
        );
    }     

}