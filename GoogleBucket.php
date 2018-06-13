<?php

/**
 * I don't believe in license
 * You can do want you want with this program
 * - gwen -
 */

class GoogleBucket
{
	const BASE_URL = '__BUCKET-NAME__.storage.googleapis.com';
	const T_REGION = [ //todo
		'eu-west-1', 'eu-west-2', 'eu-central-1',
		'us-east-1', 'us-east-2', 'us-west-1', 'us-west-2',
		'ap-south-1', 'ap-northeast-2', 'ap-southeast-1', 'ap-southeast-2', 'ap-northeast-1',
		'ca-central-1', 'sa-east-1',
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
		//if( $this->region ) {
		//	$url = str_replace( '.digitaloceanspaces.com', '.'.$this->region.'.digitaloceanspaces.com', $url );
		//}
				
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
		if( !in_array($v,self::T_REGION) ) {
			return false;
		}
		$this->region = $r;
		return true;
	}
	
	
	public function detectRegion()
	{
		/*foreach( self::T_REGION as $r )
		{
			$this->setRegion( $r );
			
			if( $this->canList(true) != 2 ) {
				return $r;
			}
		}*/
		
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
			$cmd = "gsutil acl ch -u AllUsers:R gs://".$this->name." 2>&1";
			//echo $cmd."\n";
			exec( $cmd, $output );
			$output = strtolower( trim( implode("\n",$output) ) );
			//var_dump( $output );
			
			if( preg_match('#CommandException|AccessDeniedException#i',$output) ) {
				$this->canSetACL = BucketBruteForcer::TEST_FAILED;
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
			$cmd = "gsutil acl get gs://".$this->name." 2>&1";
			//echo $cmd."\n";
			exec( $cmd, $output );
			$output = strtolower( trim( implode("\n",$output) ) );
			//var_dump( $output );
			
			if( preg_match('#CommandException|AccessDeniedException#i',$output) ) {
				$this->canGetACL = BucketBruteForcer::TEST_FAILED;
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
			$cmd = "gsutil ls gs://".$this->name." 2>&1";
			//echo $cmd."\n";
			exec( $cmd, $output );
			$output = strtolower( trim( implode("\n",$output) ) );
			//var_dump( $output );
			
			if( preg_match('#CommandException|AccessDeniedException#i',$output) ) {
				$this->canList = BucketBruteForcer::TEST_FAILED;
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
		if( is_null($this->canWrite) || $redo )
		{
			$cmd = "gsutil cp ".__DIR__."/test gs://".$this->name." 2>&1";
			//echo $cmd."\n";
			exec( $cmd, $output );
			$output = strtolower( trim( implode("\n",$output) ) );
			//var_dump( $output );
			
			if( preg_match('#CommandException|AccessDeniedException#i',$output) ) {
				$this->canWrite = BucketBruteForcer::TEST_FAILED;
			}
			else {
				$this->canWrite = BucketBruteForcer::TEST_SUCCESS;
			}
		}
		
		return $this->canWrite;
	}
}
