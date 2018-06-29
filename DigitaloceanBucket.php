<?php

/**
 * I don't believe in license
 * You can do want you want with this program
 * - gwen -
 */

class DigitaloceanBucket
{
	const BASE_URL = '__BUCKET-NAME__.digitaloceanspaces.com';
	const T_REGION = [
		'nyc3', 'ams3', 'sgp1',
	];
	const VALID_HTTP_CODE = [200,403];
	//const VALID_HTTP_CODE = [200,301,307,403];
	
	public $name = '';
	public $url = '';
	public $region = null;
	public $ssl = null;
	
	private $exist = null;
	private $canSetACL = null;
	private $canGetACL = null;
	private $canList = null;
	private $canListHTTP = null;
	private $canWrite = null;
	
	
	public function getUrl( $https=true )
	{
		$this->ssl = $https;
		
		$url = ($https ? 'https' : 'http') . '://';
		$url .= str_replace( '__BUCKET-NAME__', $this->name, self::BASE_URL );
		if( $this->region ) {
			$url = str_replace( '.digitaloceanspaces.com', '.'.$this->region.'.digitaloceanspaces.com', $url );
		}
				
		return $url;
	}
	
	
	public function getName() {
		return $this->name;
	}
	public function setName( $v ) {
		$this->name = trim( $v );
		return true;
	}
	
	
	public function getRegion() {
		return $this->region;
	}
	public function setRegion( $v ) {
		$r = trim( $v );
		if( !in_array($v,self::T_REGION) ) {
			return false;
		}
		$this->region = $r;
		return true;
	}
	
	
	public function detectRegion()
	{
		return $this->region;
	}
	
	
	public function exist( &$http_code=0, $redo=false )
	{
		foreach( self::T_REGION as $r )
		{
			$this->setRegion( $r );
			
			$e = $this->_exist( $http_code, true );
			
			if( $e ) {
				return true;
			}
		}
		
		return false;
	}
	
	
	public function _exist( &$http_code=0, $redo=false )
	{
		if( is_null($this->exist) || $redo )
		{
			$c = curl_init();
			curl_setopt( $c, CURLOPT_URL, $this->getUrl() );
			curl_setopt( $c, CURLOPT_CONNECTTIMEOUT, BucketBruteForcer::REQUEST_TIMEOUT );
			curl_setopt( $c, CURLOPT_USERAGENT, BucketBruteForcer::T_USER_AGENT[rand(0,BucketBruteForcer::N_USER_AGENT)] );
			//curl_setopt( $c, CURLOPT_FOLLOWLOCATION, true );
			curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $c, CURLOPT_NOBODY, true );
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
				$c = curl_init();
				curl_setopt( $c, CURLOPT_URL, $this->getUrl(false) );
				curl_setopt( $c, CURLOPT_CONNECTTIMEOUT, BucketBruteForcer::REQUEST_TIMEOUT );
				curl_setopt( $c, CURLOPT_USERAGENT, BucketBruteForcer::T_USER_AGENT[rand(0,BucketBruteForcer::N_USER_AGENT)] );
				//curl_setopt( $c, CURLOPT_FOLLOWLOCATION, true );
				curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
				curl_setopt( $c, CURLOPT_NOBODY, true );
				//curl_setopt( $c, CURLOPT_SSL_VERIFYPEER, false );
				//curl_setopt( $c, CURLOPT_HEADER, true );
				$r = curl_exec( $c );
				//var_dump( $r );
				$t_info = curl_getinfo( $c );
				//var_dump( $t_info );
				curl_close( $c );
				
				$http_code = $t_info['http_code'];
			}
			
			$this->exist = in_array( $http_code, self::VALID_HTTP_CODE );
		}
		
		return $this->exist;
	}
	
	
	public function canSetAcl( $redo=false )
	{
		return false;
		
		if( is_null($this->canSetACL) || $redo )
		{
			$cmd = "aws s3api put-bucket-acl --grant-full-control 'uri=\"http://acs.amazonaws.com/groups/global/AllUsers\"' --bucket ".$this->name." ".(strlen($this->region)?'--region '.$this->region:'')." 2>&1";
			//echo $cmd."\n";
			exec( $cmd, $output );
			$output = strtolower( trim( implode("\n",$output) ) );
			//var_dump( $output );
			
			if( preg_match('#A client error|AllAccessDisabled|AllAccessDisabled|AccessDenied#i',$output) ) {
				$this->canSetACL = BucketBruteForcer::TEST_FAILED;
			}
			elseif( preg_match('#An error occurred|object has no attribute#i',$output) ) {
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
		return false;

		if( is_null($this->canGetACL) || $redo )
		{
			$cmd = "aws s3api get-bucket-acl --bucket ".$this->name." ".(strlen($this->region)?'--region '.$this->region:'')." 2>&1";
			//echo $cmd."\n";
			exec( $cmd, $output );
			$output = strtolower( trim( implode("\n",$output) ) );
			//var_dump( $output );
			
			if( preg_match('#A client error|AllAccessDisabled|AllAccessDisabled|AccessDenied#i',$output) ) {
				$this->canGetACL = BucketBruteForcer::TEST_FAILED;
			}
			elseif( preg_match('#An error occurred|object has no attribute#i',$output) ) {
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
		return false;

		if( is_null($this->canList) || $redo )
		{
			$cmd = "aws s3api list-objects --bucket ".$this->name." --max-item 5 ".(strlen($this->region)?'--region '.$this->region:'')." 2>&1";
			//echo $cmd."\n";
			exec( $cmd, $output );
			$output = strtolower( trim( implode("\n",$output) ) );
			//var_dump( $output );
			
			if( preg_match('#A client error|AllAccessDisabled|AllAccessDisabled|AccessDenied#i',$output) ) {
				$this->canList = BucketBruteForcer::TEST_FAILED;
			}
			elseif( preg_match('#An error occurred|object has no attribute#i',$output) ) {
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
			curl_setopt( $c, CURLOPT_URL, $this->getUrl($this->ssl) );
			curl_setopt( $c, CURLOPT_CONNECTTIMEOUT, BucketBruteForcer::REQUEST_TIMEOUT );
			//curl_setopt( $c, CURLOPT_FOLLOWLOCATION, true );
			curl_setopt( $c, CURLOPT_USERAGENT, BucketBruteForcer::T_USER_AGENT[rand(0,BucketBruteForcer::N_USER_AGENT)] );
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
			} elseif( in_array($http_code,self::VALID_HTTP_CODE) ) {
				$this->canListHTTP = BucketBruteForcer::TEST_FAILED;
			} else {
				$this->canListHTTP = BucketBruteForcer::TEST_UNKNOW;
			}
		}
		
		return $this->canListHTTP;
	}
	
	
	public function canWrite( $redo=false )
	{
		return false;

		if( is_null($this->canWrite) || $redo )
		{
			$cmd = "aws s3 cp ".__DIR__."/test s3://".$this->name." ".(strlen($this->region)?'--region '.$this->region:'')." 2>&1";
			//echo $cmd."\n";
			exec( $cmd, $output );
			$output = strtolower( trim( implode("\n",$output) ) );
			//var_dump( $output );
			
			if( preg_match('#A client error|upload failed|AllAccessDisabled|AllAccessDisabled|AccessDenied#i',$output) ) {
				$this->canWrite = BucketBruteForcer::TEST_FAILED;
			}
			elseif( preg_match('#An error occurred|object has no attribute#i',$output) ) {
				$this->canWrite = BucketBruteForcer::TEST_UNKNOW;
			}
			else {
				$this->canWrite = BucketBruteForcer::TEST_SUCCESS;
			}
		}
		
		return $this->canWrite;
	}
}
