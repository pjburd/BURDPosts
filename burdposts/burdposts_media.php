<?php
/**
 * @package burdposts
 */


// Copyright 2016 Paul Burden

// Libraries for fetching files and saving
class BURDPosts_MEDIA
{

	/*
	 * Construct
	 *
	 *@return   void
	*/	
	public function __construct()
	{
	}

	/*
	 * Copy an image via URL to a file
	 *
	 * NOTE:  PHP5+
	 *
	 *@param	string  $url
	 *@param	string  $save_to
	 *@return   boolean
	*/	
    public function copy_image($url, $save_to)
    {
        return copy($url, $save_to);
    }

    public function unique_id($l = 8) {
        return substr(md5(uniqid(mt_rand(), true)), 0, $l);
    }

    public function write_file($url, $save_to)
    {
        return file_put_contents($save_to, file_get_contents($url));
    }

	/*
	 * Copy a file via URL to a file
	 *
	 * NOTE:  PHP5+
	 *
	 *@param	string  $url
	 *@param	string  $save_to
	 *@return   integer     File size of file
	*/	
    public function copy_file($url, $save_to)
    {
        $file_size = 0;
        //Get the file
        $content = file_get_contents($url);
        //Store in the filesystem.
        $fp = fopen($save_to, "w");
        $file_size = fwrite($fp, $content);
        fclose($fp);
        
        return $file_size;
    }

	/*
	 * Grab an image via URL and save as file 
	 *
	 * NOTE:  Ensure that in php.ini allow_url_fopen is enable
	 *
	 *@param	string  $url
	 *@param	string  $save_to
	 *@return   integer     File size of file
	*/
    public function download_image($url, $save_to)
    {
        $file_content = implode("",file($url));
        $fp = fopen($save_to,"w+");                        
        fwrite($fp, $file_content);                        
        fclose($fp);
    }

	/*
	 * Grab an image via URL and save as file 
	 *
	 * NOTE:  Ensure that in php.ini allow_url_fopen is enable
	 *
	 *@param	string  $url
	 *@param	string  $save_to
	 *@return   integer     File size of file
	*/	
    public function grab_image($url, $save_to) 
    {
        $file_size = 0;
echo "<br />".$url;
        $ch = curl_init ($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12');
        $raw=curl_exec($ch);
        curl_close ($ch);
        if(file_exists($save_to)){
            unlink($save_to);
        }
        $fp = fopen($save_to,'x');
        $file_size = fwrite($fp, $raw);
        fclose($fp);
        
        return TRUE;
    }
}