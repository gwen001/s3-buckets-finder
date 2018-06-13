<?php

/**
 * I don't believe in license
 * You can do want you want with this program
 * - gwen -
 */

class AmazonBucket
{
	const BASE_URL = '__BUCKET-NAME__.s3.amazonaws.com';
	const T_REGION = [
		'eu-west-1', 'eu-west-2', 'eu-west-3', 'eu-central-1',
		'us-east-1', 'us-east-2', 'us-west-1', 'us-west-2',
		'ap-south-1', 'ap-southeast-1', 'ap-southeast-2', 'ap-northeast-1', 'ap-northeast-2', 'ap-northeast-3',
		'ca-central-1', 'sa-east-1',
	];
	const VALID_HTTP_CODE = [200,301,307,403];
	
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
		if( strlen($this->region) ) {
			$url = str_replace( 's3.amazonaws.com', 's3-'.$this->region.'.amazonaws.com', $url );
		}
		//var_dump($url);
		
		return $url;
	}
	
	
	public function getName() {
		return $this->name;
	}
	public function setName( $v ) {
		$this->name = trim( $v );
		$this->url = str_replace( '__BUCKET-NAME__', $this->name, $this->url );
		$this->_url = $this->url;
		return true;
	}
	
	
	public function getRegion() {
		return $this->region;
	}
	public function setRegion( $v ) {
		$r = trim( $v );
		if( strlen($r) && !in_array($r,self::T_REGION) ) {
			return false;
		}
		$this->region = $r;
		return true;
	}
	
	
	public function detectRegion()
	{
		foreach( self::T_REGION as $region )
		{
			$this->setRegion( $region );
			$this->canListHTTP( true, $r );
			
			if( stristr($r,'<Code>PermanentRedirect</Code>') && stristr($r,'The bucket you are attempting to access must be addressed using the specified endpoint') ) {
				$m = preg_match( '#<Endpoint>.*s3(.*).amazonaws.com</Endpoint>#', $r, $matches );
				//var_dump( $matches );
				$region = trim( $matches[1], '-.' );
			}
			
			//var_dump( $region );
			return $region;
		}
		
		return false;
	}
	
	
	public function exist( &$http_code=0, $redo=false )
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
	
	
	public function canListHTTP( $redo=false, &$r=null )
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
			//var_dump($http_code);
			
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
