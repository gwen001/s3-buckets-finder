<?php

/**
 * I don't believe in license
 * You can do want you want with this program
 * - gwen -
 */

class BucketExtractor
{
	const AWS_URL = '.s3.amazonaws.com';
	const TECHNIK_WEB = 'WEB';
	const TECHNIK_CLI = 'CLI';
	const CLI_MAX_ITEM = 10;
	const IGNORE_SEP = ',';

	private $bucket = null;
	private $bucket_url = null;

	private $destination = '.';

	private $download = false;
	
	private $datas = null;
	
	private $technik = null;
	
	private $region = null;
	
	// 0=do no print anything, 1=only readable, 2=everything
	private $verbosity = 2;

	private $ignore = [
			'bmp','gif','ico','jpg','jpeg','png','svg','tif','tiff', // images
			'mp4','xvid','divx','m4v','mpg','mpeg','ogv','webm', // videos
			'mp3', // sounds
			'eot','ttf','woff','woff2', // fonts
			'css','flv','swf', // web
			'deb','md5sum', // others
		];


	public function __construct() {
		$this->destination = dirname(__FILE__);
	}


	public function getBucket() {
		return $this->bucket;
	}
	public function setBucket( $v ) {
		$this->bucket = trim( $v );
		$this->bucket_url = 'https://'.$this->bucket.self::AWS_URL;
		return true;
	}


	public function getDestination() {
		return $this->destination;
	}
	public function setDestination( $v ) {
		$destination = trim( $v );
		if( $destination ) {
			$this->destination = $destination;
		}
		return true;
	}


	public function getDownload() {
		return $this->download;
	}
	public function setDownload( $v ) {
		$this->download = (bool)$v;
		return true;
	}


	public function getIgnore() {
		return $this->ignore;
	}
	public function setIgnore( $v ) {
		$this->ignore = explode( self::IGNORE_SEP, $v );
		return true;
	}


	public function getRegion() {
		return $this->region;
	}
	public function setRegion( $v ) {
		$this->region = trim( $v );
		return true;
	}


	public function getVerbosity() {
		return $this->verbosity;
	}
	public function setVerbosity( $v ) {
		$this->verbosity = (int)$v;
		return true;
	}


	public function run()
	{
		echo 'Checking '.$this->bucket_url." via ".self::TECHNIK_WEB." (https)\n";
        $this->datas = @file_get_contents( $this->bucket_url );
        //$this->datas = null;

        if( $this->datas ) {
            $this->technik = self::TECHNIK_WEB;
        } else {
        	$this->bucket_url = str_replace( 'https', 'http', $this->bucket_url );
			echo 'Checking '.$this->bucket_url." via ".self::TECHNIK_WEB." (http)\n";
	        $this->datas = @file_get_contents( $this->bucket_url );
	        //$this->datas = null;
			
	        if( $this->datas ) {
	            $this->technik = self::TECHNIK_WEB;
	        } else {
	            echo 'Checking '.$this->bucket_url." via ".self::TECHNIK_CLI." (can be very long...)\n";
	            //exec( 'aws s3 ls s3://'.$this->bucket.' --recursive 2>/dev/null', $this->datas );
       			$cmd = "aws s3api list-objects --bucket ".$this->bucket." --max-item 5 ".(strlen($this->region)?'--region '.$this->region:'')." 2>/dev/null";
				//echo $cmd."\n";
				exec( $cmd, $this->datas );
				//var_dump($this->datas);
				
	            if( is_array($this->datas) && count($this->datas) ) {
	                $this->technik = self::TECHNIK_CLI;
	            } else {
					Utils::help( 'This bucket does not exist or you do not have enough permission to read it' );
	            }
        	}
        }
		
		if( $this->download ) {
			$this->destination = rtrim($this->destination,' /') . '/' . $this->bucket;
			$this->_mkdir( $this->destination );
		}

		echo "\n";
		
		switch( $this->technik ) {
			case self::TECHNIK_WEB:
				$cnt = $this->extractWEB(  $this->datas );
				break;
			case self::TECHNIK_CLI:
				$cnt = $this->extractCLI( $this->datas );
				break;
		}
		
		return $cnt;
	}
	
	
	private function extractCLI( $unused )
	{
		$cnt = 0;
		$total = 0;
		$s_token = null;

		do
		{
			$cmd = "aws s3api list-objects --bucket ".$this->bucket." --max-item ".self::CLI_MAX_ITEM." ".(strlen($this->region)?'--region '.$this->region:'')." ".(strlen($s_token)?'--starting-token '.$s_token:'')." 2>/dev/null";
			//echo $cmd."\n";
			exec( $cmd, $datas );
			$datas = implode( "\n", $datas );
			$t_datas = json_decode( $datas, true );
			
			if( isset($t_datas['NextToken']) ) {
				$s_token = $t_datas['NextToken'];
			} else {
				$s_token = null;
			}
			
			if( isset($t_datas['Contents']) && is_array($t_datas['Contents']) && count($t_datas['Contents']) )
			{
				foreach( $t_datas['Contents'] as $o )
				{
					$f_name = $o['Key'];
					if( substr($f_name,-1) == '/' || preg_match('#\$folder\$#',$f_name) ) {
						if( $this->download ) {
							$this->_mkdir( $this->destination.'/'.str_replace('_$folder$','',$f_name) );
						}
						continue;
					}
					
					$ext = $this->_extension( basename($f_name) );
					if( in_array($ext,$this->ignore) ) {
						continue;
					}
					
					$total++;
					$f_size = $o['Size'];
					$f_url = $this->bucket_url.'/'.$f_name;
					
					$tmpfile = tempnam( '/tmp/', 's3bf-' );
					$cmd = "aws s3api get-object --bucket ".$this->bucket." --key ".$f_name." ".(strlen($this->region)?'--region '.$this->region:'')." ".$tmpfile." 2>/dev/null";
					//echo $cmd."\n";
					exec( $cmd, $f_datas );
					$f_datas = trim( implode( "\n", $f_datas ) );
	
					if( $this->download ) {
						$dir = rtrim( dirname($f_name), ' /' );
						if( $dir!='' && $dir!='.' && $dir!='./' ) {
							$this->_mkdir( $this->destination.'/'.$dir );
						}
					}
		
					if( strlen($f_datas) )
					{
						if( $this->verbosity >= 1 ) {
							Utils::_print( $f_url." (".Utils::format_bytes((int)$f_size).")\n" );
						}
						if( $this->download ) {
							$dst = $this->destination.'/'.$f_name;
							rename( $tmpfile, $dst );
						}
						$cnt++;
					} elseif( $this->verbosity >= 2 ) {
						Utils::_print( $f_url."\n", 'dark_grey' );
					}
					
					@unlink( $tmpfile );
				}
			}
		}
		while( $s_token );
		
		return [$total,$cnt];
	}
	
	
	private function extractWEB( $datas )
	{
		$cnt = 0;
		$total = 0;
		$xml = new SimpleXmlElement( $datas );
		
		foreach( $xml->Contents as $content )
		{
			$f_name = (string)$content->Key;
			if( substr($f_name,-1) == '/' ) {
				if( $this->download ) {
					$this->_mkdir( $this->destination.'/'.$f_name );
				}
				continue;
			}
			
			$ext = $this->_extension( basename($f_name) );
			if( in_array($ext,$this->ignore) ) {
				continue;
			}

			$total++;
			$f_url = $this->bucket_url.'/'.$f_name;

			$c = curl_init();
			curl_setopt( $c, CURLOPT_URL, $f_url );
			if( $this->download ) {
				curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
			} else {
				curl_setopt( $c, CURLOPT_NOBODY, true );
			}
			$f_datas = curl_exec( $c );
			$t_info = curl_getinfo( $c );

			if( $this->download ) {
				$dir = rtrim( dirname($f_name), ' /' );
				if( $dir!='' && $dir!='.' && $dir!='./' ) {
					$this->_mkdir( $this->destination.'/'.$dir );
				}
			}

			if( $t_info['http_code'] == 200 )
			{
				if( $this->verbosity >= 1 ) {
					Utils::_print( $f_url." (".Utils::format_bytes((int)$content->Size).")\n" );
				}
				if( $this->download ) {
					$dst = $this->destination.'/'.$f_name;
					file_put_contents( $dst, $f_datas );
				}
				$cnt++;
			}
			elseif( $this->verbosity >= 2 ) {
				Utils::_print( $f_url."\n", 'dark_grey' );
			}
		}

		return [$total,$cnt];
	}


	private function _extension( $str )
	{
		if( ($p=strrpos($str,'.')) === false ) {
			return $str;
		}

		return strtolower( substr($str,$p+1) );
	}


	private function _mkdir( $dir )
	{
		$mkdir = true;
		if( !is_dir($dir) ) {
			$mkdir = @mkdir( $dir, 0755, true );
		}
		if( !$mkdir || !is_dir($dir) || !is_writable($this->destination) ) {
			Utils::help( 'Output directory not accessible' );
		}

		return true;
	}
}

?>