<?php
session_start();

// Load Facebook SDK (Requires PHP 5.4+)
define('FACEBOOK_SDK_V4_SRC_DIR', BURDPOSTS__PLUGIN_DIR.'src/Facebook/');
require BURDPOSTS__PLUGIN_DIR . 'autoload.php';
    	
use Facebook\FacebookSession;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequest;
use Facebook\FacebookResponse;
use Facebook\FacebookSDKException;
use Facebook\FacebookRequestException;
use Facebook\FacebookAuthorizationException;
use Facebook\GraphObject;
use Facebook\GraphUser;

// Requires FB PHP SDK v4
class BURDPosts_FB
{
	private $app_id = '';
	private $app_secret = '';
	private $access_token = '';
	private $page_id = '';
	private $redirect_url = '';
	private $scope = 'manage_pages';
	
    private $helper = NULL;
    private $session = NULL;
    
    private $request = NULL;
    private $response = NULL;
    private $last_page = FALSE;
    private $last_page_post_id = "";

	/*
	 * Construct
	 *
	 *@return   void
	*/	
	public function __construct($app_id, $app_secret, $access_token, $page_id, $redirect_url="" )
	{
    	$this->app_id = $app_id;
    	$this->app_secret = $app_secret;
    	$this->access_token = $access_token;
    	$this->page_id = $page_id;
    	$this->redirect_url = $redirect_url;

		FacebookSession::setDefaultApplication($this->app_id, $this->app_secret);
    	
    	if ($redirect_url)
    	{
        	try
        	{
            	
                $this->helper = new FacebookRedirectLoginHelper($this->redirect_url);    	
            
        	}  
        	catch(\Exception $e) 
        	{
                echo $this->handle_error_code($e);
                die;
            }

        }
        if ($this->access_token)
        {
            
            $this->session = new FacebookSession($this->access_token);
            
        }

        if ($redirect_url == "" && $this->session == NULL)
        {
            echo "You must login to fetch an access token";
            die;
        }
	}

	/*
	 * Test Facebook session
	 *
	 *@return	boolean
	*/	
	public function test_session()
	{  
        // Attempt to retrieve session        
        try {
          $this->session = $this->helper->getSessionFromRedirect();
          // var_dump($session);
        } catch(FacebookRequestException $e) {
            $this->handle_error_code($e);
        } catch(\Exception $e) {
            $this->handle_error_code($e);
        }
        
        if ($this->session) 
        {        
            return TRUE;
        }
        else
        {
            return FALSE;
        }

	}



    public function get_current_request()
    {
        return $this->request;
    }
    
    public function get_current_response()
    {
        return $this->response;
    }

	/*
	 * Return posts
	 *
	 *@param	string  $duration   Either 'month', '2month', 'year', 'alltime'
	 *@return	array
	*/		    
    public function fetch_posts($period="month")
    {
        // Fetch first month
        $start_date = date("Y-m-d H:i:s");
        $until = strtotime($start_date); 
        $this->until = $until;

        $current_date = date("Y-m-d");        
        switch($period)
        {
            case 'month':
                $sub_month = strtotime('-1 month', strtotime($start_date));
                break;
            case '2month':
                $sub_month = strtotime('-2 month', strtotime($start_date));
                break;
            case '3month':
                $sub_month = strtotime('-3 month', strtotime($start_date));
                break;
                
        }

        $sub_month = date('Y-m-d', $sub_month);
        $since = strtotime($sub_month);

//!TODO: Need to increment since for each pagination!!!!!?!?!?!?!?!?!??!?! TEST THIS!!!!
//!TODO: Need to increment since for each pagination!!!!!?!?!?!?!?!?!??!?! TEST THIS!!!!
//!TODO: Need to increment since for each pagination!!!!!?!?!?!?!?!?!??!?! TEST THIS!!!!
//!TODO: Need to increment since for each pagination!!!!!?!?!?!?!?!?!??!?! TEST THIS!!!!
        $call = '/'.$this->page_id.'/feed?date_format=Y-m-d H:i:s'
                                       . '&limit=30'
                                       . '&since='.$since
                                       . '&until='.$until;
        try
        {
//echo "'nCALL:".$call;
            $this->response = $this->_api_call($call);  
                           
            $graphObject = $this->response->getGraphObject();
            $items = $graphObject->asArray();
            
        } catch(\FacebookSDKException $e) {
            echo $this->handle_error_code($e);
        } catch(FacebookRequestException $e) {
            echo $this->handle_error_code($e);
        } catch(\Exception $e) {
            echo $this->handle_error_code($e);
        }
        
        return $items;

    }

	/*
	 * Return next page of posts from current reponse session
	 *
	 * NOTE: Must have $this->response via $this->fetch_posts() command
	 *
	 *@return	array
	*/		    
    public function fetch_next_page()
    {
        if (!empty($this->response) && $this->last_page == FALSE)
        {
            // Get the next page of data from $this->response
            $next_request = $this->response->getRequestForPreviousPage();
/*            
echo "//NEXT\n";
$params = $next_request->getParameters();            
print_r($params);
*/
            $this->response = $next_request->execute();
            $graphObject = $this->response->getGraphObject();
            $items = $graphObject->asArray();
//print_r($items['data']);
//die;
            if (is_array($items))
            {
                $last_key = key( array_slice( $items['data'], -1, 1, TRUE ) );
    
                if ($this->last_page_post_id == $items['data'][$last_key]->id) // If id is repeated twice then no more pages
                {
                    $this->last_page = TRUE;            
                }
                else
                {
                    $this->last_page_post_id = $items['data'][$last_key]->id;
                }
                
    
                return $items;
            }
            else
            {
                return FALSE;
            }
            
        }
        else
        {
            return FALSE; // Empty array means no posts
        }
    }
    
	/*
	 * Return a processed callback
	 *
	 *@param	string  $method
	 *@param	string  $page
	 *@return	string
	*/		    
    public function process_callback($method, $page="")
    {
        $out = "";
        switch($method)
        {
            // API CALL
            case 'fetchpageidfeed':
            case 'myaccounts':
            case 'mydetails':
                $response = $this->_api_call($method, $page);

                $out .= $this->_render_response_output($method, $response, NULL);                 

                break;
        }
        return $out;
    }

	/*
	 * Return long lived access token
	 *
	 *@return	string
	*/	
    public function get_longlived_accesstoken()
    {
        $out = "";
        
    	try
		{
		    $accessToken = $this->session->getAccessToken();
		    $out = $accessToken->extend();
		}
		catch (\Exception $e) 
		{
			$out = $this->handle_error_code($e);
		}
		
        return $out;
    }
	
	/*
	 * Return a login anchor link
	 *
	 *@return	string
	*/		
    public function get_login_link()
    {
        $scope = array($this->scope);
        return "<a href=\"".$this->helper->getLoginUrl($scope)."\">Login</a> (Fetch user access token)";
    }
    

	/*
	 * Return an api call
	 *
	 *@param	string	$method
	 *@param	string	$page
	 *@return	mixed  Either object (response) or string (errors)
	*/	
    private function _api_call($method, $page="")
    {
        $out = NULL;
        $http_method = 'GET';
        $call = '';
        $result = NULL;
        $call_params = array();
        $page_item_limit = '10';
$page_item_limit = '';
        
        // Prepare call
        switch($method)
        {
            case 'mydetails':
                    $call = '/me';
                break;
            case 'myaccounts':
                    $call = '/me/accounts?fields=name,access_token,perms&limit='.$page_item_limit;
                break;
            case 'fetchpageidfeed':
                    $call = '/'.$this->page_id.'/feed?limit='.$page_item_limit."&date_format=Y-m-d H:i:s";
                break;
            default:
                $call = $method;    // Just set the call as is
                break;
        }
        
        $call .= $page_limit;   // attach pagination params
        
        try 
        {

            if (!empty($page))  // parse the url
            {
                
                $query = parse_url($page, PHP_URL_QUERY);
                parse_str($query, $url_parts);    
 
                $call .= "&until=".$url_parts['until'];
                $call .= "&access_token=".$url_parts['access_token'];
                $call .= "&__paging_token=".$url_parts['__paging_token'];
 
            }    
            
            // Make call
            $request = new FacebookRequest($this->session, $http_method, $call);
            $response = $request->execute();
   
            // Render result
            return $response;
        }
        catch(\FacebookSDKException $e)
        {
			$out = $this->handle_error_code($e);                    
        }
        catch(\FacebookRequestException $e)
        {
			$out = $this->handle_error_code($e);                    
        }
        catch(\Exception $e) 
        {
			$out = $this->handle_error_code($e);        
        }       
        
        // If we made it here then there is an error to report
        return $out;
    }

	/*
	 * Fetch picture url based on object id
	 *
	 *@param	string  $object_id
	 *@return	string
	*/
    public function get_picture_url($object_id)
    {
        $response = $this->_api_call("/". $object_id); 

        if (!empty($response))
        {
            $graphObject = $response->getGraphObject();
       
            $items = $graphObject->asArray();
            if (isset($items['images'][0]->source))
            {
                return $items['images'][0]->source;     
            }
        }
        
        return "";
    }

	/*
	 * Render picture item that links to larger image
	 *
	 *@param	object  $post
	 *@return	string
	*/
    private function _render_post($post)
    {
        $out = "";
        
        $out .= '<h3>'. ucfirst($post->type) .' post created on '.date('D jS M Y, H:i:s', strtotime($post->created_time) ).'</h3>';
        
        switch($post->type)
        {
            case 'video':            
            case 'link':            
            case 'status':  
                $out .= '<a href="'.$post->link.'" target="_blank"><img src="'.$post->picture.'" /></a>'; 
                break;          
            case 'photo':                    
                if (!empty($post->object_id))
                {
                    $picture_url = $this->get_picture_url($post->object_id);
                    
                    $out .= '<a href="'.$picture_url.'" target="_blank"><img src="'.$post->picture.'" /></a>';
                    
                }
                break;
        }

        if (isset($post->message))
        {
            $out .= '<br />'.$post->message;
        }
        else
        {
            $out .= 'No message found';
        }

                
        return $out;
    }

	/*
	 * Return a rendered representation of response
	 *
	 *@param	string  $method
	 *@param	object  $response
	 *@param	mixed   $params   These may include pagination settings
	 *@return	string
	*/	
    private function _render_response_output($method, $response)
    {

        $out = "";
        $render_pagination = FALSE;
        
        $graphObject = $response->getGraphObject();
                        
        switch($method)
        {
            case 'mydetails':

                $items = $graphObject->asArray();
                if (!empty($items))
                {
                    foreach($items as $key_name => $value)
                    {
                        $out .= "<p><strong>".$key_name."</strong><br />".$value."</p>";
                    }
                }
                break;
            case 'myaccounts':

                $items = $graphObject->asArray();

                $render_pagination = TRUE;
                if (!empty($items))
                {
                    foreach($items['data'] as $item)
                    {
                        $out .= '<h3>'. $item->name .'</h3>' 
                              . '<strong>Page ID:</strong> <input type="text" value="'.$item->id.'" disabled="disabled" /><br />' 
                              . '<strong>Access token:</strong> <input type="text" value="'.$item->access_token.'" disabled="disabled" />';
                    }
                }
                break;
            case 'fetchpageidfeed':
            
                $items = $graphObject->asArray();
                $render_pagination = TRUE;

                if (!empty($items))
                {
                    foreach($items['data'] as $post)
                    {
                       
                        $out .= $this->_render_post($post).'<br />';
                        

                    }
                }                
                break;

        }
        
        // Render pagination links if required      
        if ($render_pagination == TRUE)
        {             
            
            
            if (!empty($items['paging']->previous))
            {                
                $out .= '<a href="" class="burdposts_btn" data-cmd="'. $method. '_fb" data-page="'. $items['paging']->previous .'">Prev</a>';
            }
            $out .= " ";
            if (!empty($items['paging']->next))
            {                
                $out .= '<a href="" class="burdposts_btn" data-cmd="'. $method. '_fb" data-page="'. $items['paging']->next .'">Next</a>' ;
            }
        }

        return $out;
    }
    
    
    
    private function _batch_request()
    {
        
    }

	/*
	 * Return error message
	 *
	 *@param	object	$e              Exception object
	 *@return	string
	*/	
	private function handle_error_code($e)
	{
//!!!!!! Add in email technical aka me if this happens
        $out = "";
        switch($e->getCode())
        {
            case 190:
                $out = "Invalid access token provided";
                break;
            case 700:
                $out = "You must provide a app id";
                break;
            case 701:
                $out = "You must provide a valid app secret";
                break;
            default:
                $out = "FB ERR:(".$e->getCode() . ") " . $e->getMessage();
                break;
        }
		return $out;
	}

}
