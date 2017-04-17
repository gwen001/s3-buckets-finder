<?php

/**
 * I don't believe in license
 * You can do want you want with this program
 * - gwen -
 */

class Bucket
{
	public $name = '';
	public $url = '';
	
	private $exist = null;
	
	private $canSetACL = null;
	private $canGetACL = null;
	private $canList = null;
	private $canListHTTP = null;
	private $canWrite = null;
	
	
	public function getName() {
		return $this->name;
	}
	public function setName( $v ) {
		$this->name = trim( $v );
		$this->url = BucketFinder::AWS_URL.$this->name;
		return true;
	}
	
	
	public function exist( &$http_code=0, $redo=false )
	{
		if( is_null($this->exist) || $redo )
		{
			$c = curl_init();
			curl_setopt( $c, CURLOPT_URL, $this->url );
			curl_setopt( $c, CURLOPT_CONNECTTIMEOUT, 3 );
			//curl_setopt( $c, CURLOPT_FOLLOWLOCATION, true );
			curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $c, CURLOPT_SSL_VERIFYPEER, false );
			//curl_setopt( $c, CURLOPT_HEADER, true );
			$r = curl_exec( $c );
			//var_dump( $r );
			$t_info = curl_getinfo( $c );
			//var_dump( $t_info );
			curl_close( $c );
			
			$http_code = $t_info['http_code'];
			$this->exist = in_array( $http_code, BucketFinder::AWS_VALID_HTTP_CODE );
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
			$cmd = "aws s3api put-bucket-acl --grant-full-control 'uri=\"http://acs.amazonaws.com/groups/global/AllUsers\"' --bucket ".$this->name." 2>&1";
			//echo $cmd;
			exec( $cmd, $output );
			$output = strtolower( trim( implode("\n",$output) ) );
			//var_dump( $output );
			
			if( preg_match('#A client error#i',$output) ) {
				$this->canSetACL = BucketFinder::TEST_FAILED;
			}
			elseif( preg_match('#An error occurred#i',$output) ) {
				$this->canSetACL = BucketFinder::TEST_UNKNOW;
			}
			else {
				$this->canSetACL = BucketFinder::TEST_SUCCESS;
			}
		}
		
		return $this->canSetACL;
	}
	
	
	public function canGetAcl( $redo=false )
	{
		if( is_null($this->canGetACL) || $redo )
		{
			$cmd = "aws s3api get-bucket-acl --bucket ".$this->name." 2>&1";
			//echo $cmd;
			exec( $cmd, $output );
			$output = strtolower( trim( implode("\n",$output) ) );
			//var_dump( $output );
			
			if( preg_match('#A client error#i',$output) ) {
				$this->canGetACL = BucketFinder::TEST_FAILED;
			}
			elseif( preg_match('#An error occurred#i',$output) ) {
				$this->canGetACL = BucketFinder::TEST_UNKNOW;
			}
			else {
				$this->canGetACL = BucketFinder::TEST_SUCCESS;
			}
		}
		
		return $this->canGetACL;
	}
	
	
	public function canList( $redo=false )
	{
		if( is_null($this->canList) || $redo )
		{
			$cmd = "aws s3 ls s3://".$this->name." 2>&1";
			//echo $cmd;
			exec( $cmd, $output );
			$output = strtolower( trim( implode("\n",$output) ) );
			//var_dump( $output );
			
			if( preg_match('#A client error#i',$output) ) {
				$this->canList = BucketFinder::TEST_FAILED;
			}
			elseif( preg_match('#An error occurred#i',$output) ) {
				$this->canList = BucketFinder::TEST_UNKNOW;
			}
			else {
				$this->canList = BucketFinder::TEST_SUCCESS;
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
			curl_setopt( $c, CURLOPT_CONNECTTIMEOUT, 3 );
			//curl_setopt( $c, CURLOPT_FOLLOWLOCATION, true );
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
				$this->canListHTTP = BucketFinder::TEST_SUCCESS;
			} elseif( in_array($http_code,BucketFinder::AWS_VALID_HTTP_CODE) ) {
				$this->canListHTTP = BucketFinder::TEST_FAILED;
			} else {
				$this->canListHTTP = BucketFinder::TEST_UNKNOW;
			}
		}
		
		return $this->canListHTTP;
	}
	
	
	public function canWrite( $redo=false )
	{
		if( is_null($this->canWrite) || $redo )
		{
			$tmpfile = tempnam( BucketFinder::TEMPFILE_DIR, BucketFinder::TEMPFILE_PREFIX );
			$cmd = "aws s3 cp ".$tmpfile." s3://".$this->name." 2>&1";
			//echo $cmd;
			exec( $cmd, $output );
			$output = strtolower( trim( implode("\n",$output) ) );
			//var_dump( $output );
			
			if( preg_match('#A client error|upload failed#i',$output) ) {
				$this->canWrite = BucketFinder::TEST_FAILED;
			}
			elseif( preg_match('#An error occurred#i',$output) ) {
				$this->canWrite = BucketFinder::TEST_UNKNOW;
			}
			else {
				$this->canWrite = BucketFinder::TEST_SUCCESS;
			}
			
			@unlink( $tmpfile );
		}
		
		return $this->canWrite;
	}
}
