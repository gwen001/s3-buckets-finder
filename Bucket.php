<?php

/**
 * I don't believe in license
 * You can do want you want with this program
 * - gwen -
 */

class Bucket
{
	const REQUEST_TIMEOUT = 5;
	const T_USER_AGENT = [
		'Mozilla/5.0 (X11; Linux x86_64; rv:31.0) Gecko/20100101 Firefox/31.0 Iceweasel/31.7.0',
		'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)',
		'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36',
		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.75.14 (KHTML, like Gecko) Version/7.0.3 Safari/7046A194A',
		'Mozilla/5.0 (Windows NT 6.3; rv:36.0) Gecko/20100101 Firefox/36.0',
		'Opera/9.80 (Windows NT 6.0) Presto/2.12.388 Version/12.14',
		'Mozilla/5.0 (X11; Linux 3.5.4-1-ARCH i686; es) KHTML/4.9.1 (like Gecko) Konqueror/4.9',
		'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:52.0) Gecko/20100101 Firefox/52.0',
	];
	const N_USER_AGENT = 7;
	
	public $name = '';
	public $url = '';
	
	public $region = null;
	
	private $exist = null;
	
	private $canSetACL = null;
	private $canGetACL = null;
	private $canList = null;
	private $canListHTTP = null;
	private $canWrite = null;
	
	
	public function __construct() {
		$this->url = BucketBruteForcer::AWS_URL;
	}
	
	
	public function getName() {
		return $this->name;
	}
	public function setName( $v ) {
		$this->name = trim( $v );
		//$this->url = BucketBruteForcer::AWS_URL.$this->name;
		$this->url = str_replace( '//s3', '//'.$this->name.'.s3', $this->url );
		return true;
	}
	
	
	public function getRegion() {
		return $this->region;
	}
	public function setRegion( $v ) {
		$this->region = trim( $v );
		$url = str_replace( 's3.', 's3-'.$this->region.'.', $this->url );
		return true;
	}
	
	
	public function exist( &$http_code=0, $redo=false )
	{
		if( is_null($this->exist) || $redo )
		{
			$c = curl_init();
			curl_setopt( $c, CURLOPT_URL, $this->url );
			curl_setopt( $c, CURLOPT_CONNECTTIMEOUT, self::REQUEST_TIMEOUT );
			curl_setopt( $c, CURLOPT_USERAGENT, self::T_USER_AGENT[rand(0,self::N_USER_AGENT)] );
			//curl_setopt( $c, CURLOPT_FOLLOWLOCATION, true );
			curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
			//curl_setopt( $c, CURLOPT_SSL_VERIFYPEER, false );
			//curl_setopt( $c, CURLOPT_HEADER, true );
			$r = curl_exec( $c );
			//var_dump( $r );
			$t_info = curl_getinfo( $c );
			//var_dump( $t_info );
			curl_close( $c );
			
			$http_code = $t_info['http_code'];
			
			if( $http_code == 0 )
			{
				$this->url = str_replace( 'https://', 'http://', $this->url );
				
				$c = curl_init();
				curl_setopt( $c, CURLOPT_URL, $this->url );
				curl_setopt( $c, CURLOPT_CONNECTTIMEOUT, self::REQUEST_TIMEOUT );
				curl_setopt( $c, CURLOPT_USERAGENT, self::T_USER_AGENT[rand(0,self::N_USER_AGENT)] );
				//curl_setopt( $c, CURLOPT_FOLLOWLOCATION, true );
				curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
				//curl_setopt( $c, CURLOPT_SSL_VERIFYPEER, false );
				//curl_setopt( $c, CURLOPT_HEADER, true );
				$r = curl_exec( $c );
				//var_dump( $r );
				$t_info = curl_getinfo( $c );
				//var_dump( $t_info );
				curl_close( $c );
				
				$http_code = $t_info['http_code'];
			}
			
			$this->exist = in_array( $http_code, BucketBruteForcer::AWS_VALID_HTTP_CODE );
		}
		
		return $this->exist;
	}
	
	
	// 0: success
	// 1: failed
	// 2: unknown
	public function canSetAcl( $redo=false )
	{
		if( is_null($this->canSetACL) || $redo )
		{
			$cmd = "aws s3api put-bucket-acl --grant-full-control 'uri=\"http://acs.amazonaws.com/groups/global/AllUsers\"' --bucket ".$this->name." ".(strlen($this->region)?'--region '.$this->region:'')." 2>&1";
			//echo $cmd;
			exec( $cmd, $output );
			$output = strtolower( trim( implode("\n",$output) ) );
			//var_dump( $output );
			
			if( preg_match('#A client error|AllAccessDisabled|AllAccessDisabled|AccessDenied#i',$output) ) {
				$this->canSetACL = BucketBruteForcer::TEST_FAILED;
			}
			elseif( preg_match('#An error occurred#i',$output) ) {
				$this->canSetACL = BucketBruteForcer::TEST_UNKNOW;
			}
			else {
				$this->canSetACL = BucketBruteForcer::TEST_SUCCESS;
			}
		}
		
		return $this->canSetACL;
	}
	
	
	public function canGetAcl( $redo=false )
	{
		if( is_null($this->canGetACL) || $redo )
		{
			$cmd = "aws s3api get-bucket-acl --bucket ".$this->name." ".(strlen($this->region)?'--region '.$this->region:'')." 2>&1";
			//echo $cmd;
			exec( $cmd, $output );
			$output = strtolower( trim( implode("\n",$output) ) );
			//var_dump( $output );
			
			if( preg_match('#A client error|AllAccessDisabled|AllAccessDisabled|AccessDenied#i',$output) ) {
				$this->canGetACL = BucketBruteForcer::TEST_FAILED;
			}
			elseif( preg_match('#An error occurred#i',$output) ) {
				$this->canGetACL = BucketBruteForcer::TEST_UNKNOW;
			}
			else {
				$this->canGetACL = BucketBruteForcer::TEST_SUCCESS;
			}
		}
		
		return $this->canGetACL;
	}
	
	
	public function canList( $redo=false )
	{
		if( is_null($this->canList) || $redo )
		{
			$cmd = "aws s3api list-objects --bucket ".$this->name." --max-item 5 ".(strlen($this->region)?'--region '.$this->region:'')." 2>&1";
			//echo $cmd;
			exec( $cmd, $output );
			$output = strtolower( trim( implode("\n",$output) ) );
			//var_dump( $output );
			
			if( preg_match('#A client error|AllAccessDisabled|AllAccessDisabled|AccessDenied#i',$output) ) {
				$this->canList = BucketBruteForcer::TEST_FAILED;
			}
			elseif( preg_match('#An error occurred#i',$output) ) {
				$this->canList = BucketBruteForcer::TEST_UNKNOW;
			}
			else {
				$this->canList = BucketBruteForcer::TEST_SUCCESS;
			}
		}
		
		return $this->canList;
	}
	
	
	public function canListHTTP( $redo=false )
	{
		if( is_null($this->canListHTTP) || $redo )
		{
			$c = curl_init();
			curl_setopt( $c, CURLOPT_URL, $this->url );
			curl_setopt( $c, CURLOPT_CONNECTTIMEOUT, self::REQUEST_TIMEOUT );
			//curl_setopt( $c, CURLOPT_FOLLOWLOCATION, true );
			curl_setopt( $c, CURLOPT_USERAGENT, self::T_USER_AGENT[rand(0,self::N_USER_AGENT)] );
			curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $c, CURLOPT_SSL_VERIFYPEER, false );
			//curl_setopt( $c, CURLOPT_HEADER, true );
			$r = curl_exec( $c );
			//var_dump( $r );
			$t_info = curl_getinfo( $c );
			//var_dump( $t_info );
			curl_close( $c );
			
			$http_code = $t_info['http_code'];
			
			if( $http_code == 200 ) {
				$this->canListHTTP = BucketBruteForcer::TEST_SUCCESS;
			} elseif( in_array($http_code,BucketBruteForcer::AWS_VALID_HTTP_CODE) ) {
				$this->canListHTTP = BucketBruteForcer::TEST_FAILED;
			} else {
				$this->canListHTTP = BucketBruteForcer::TEST_UNKNOW;
			}
		}
		
		return $this->canListHTTP;
	}
	
	
	public function canWrite( $redo=false )
	{
		if( is_null($this->canWrite) || $redo )
		{
			$tmpfile = tempnam( BucketBruteForcer::TEMPFILE_DIR, BucketBruteForcer::TEMPFILE_PREFIX );
			$cmd = "aws s3 cp ".$tmpfile." s3://".$this->name." ".(strlen($this->region)?'--region '.$this->region:'')." 2>&1";
			//echo $cmd;
			exec( $cmd, $output );
			$output = strtolower( trim( implode("\n",$output) ) );
			//var_dump( $output );
			
			if( preg_match('#A client error|upload failed|AllAccessDisabled|AllAccessDisabled|AccessDenied#i',$output) ) {
				$this->canWrite = BucketBruteForcer::TEST_FAILED;
			}
			elseif( preg_match('#An error occurred#i',$output) ) {
				$this->canWrite = BucketBruteForcer::TEST_UNKNOW;
			}
			else {
				$this->canWrite = BucketBruteForcer::TEST_SUCCESS;
			}
			
			@unlink( $tmpfile );
		}
		
		return $this->canWrite;
	}
}
