<?php

/**
 * I don't believe in license
 * You can do want you want with this program
 * - gwen -
 */

class BucketTester
{
	const WORD_SEPARATOR = '__SEP__';
	
	const TEMPFILE_DIR = '/tmp/';
	const TEMPFILE_PREFIX = 's3bf-';
	
	const TEST_SUCCESS = 0;
	const TEST_FAILED  = 1;
	const TEST_UNKNOW  = 2;
	
	const AWS_URL = 'https://s3.amazonaws.com/';
	const AWS_REGION = [
		'us-east-1', 'us-east-2', 'us-west-1', 'us-west-2',
		'ap-south-1', 'ap-northeast-2', 'ap-southeast-1', 'ap-southeast-2', 'ap-northeast-1',
		'eu-central-1', 'eu-west-1', 'eu-west-2',
		'ca-central-1', 'sa-east-1',
	];
	const AWS_VALID_HTTP_CODE = [200,301,403];
	
	private $t_bucket = [];
	private $t_prefix = [];
	private $t_suffix = [];
	
	private $t_glue = [ '', '.', '-', '_' ];
	
	private $tests = 'sglhw';
	
	private $region = '';
	
	private $permutation = '';
	
	private $disable_color = false;
	
	private $disable_test = false;
	
	private $recursivity = false;
	
	private $verbosity = 0;

	
	private $n_child = 0;
	private $max_child = 5;
	private $sleep = 50000;
	private $t_process = [];
	private $t_signal_queue = [];
	private $cnt_notice = 500;


	public function __construct() {
	}


	public function disableColor() {
		$this->disable_color = true;
	}
	
	
	public function disableTest() {
		$this->disable_test = true;
	}
	
	
	public function enableRecursivity() {
		$this->recursivity = true;
	}
	
	
	public function getPrefix() {
		return $this->t_prefix;
	}
	public function setPrefix( $v ) {
		$v = trim( $v );
		if( is_file($v) ) {
			$this->t_prefix = array_merge( $this->t_prefix, file($v,FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) );
		} else {
			$this->t_prefix[] = $v;
		}
		return true;
	}


	public function getSuffix() {
		return $this->t_suffix;
	}
	public function setSuffix( $v ) {
		$v = trim( $v );
		if( is_file($v) ) {
			$this->t_suffix = array_merge( $this->t_suffix, file($v,FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) );
		} else {
			$this->t_suffix[] = $v;
		}
		return true;
	}

	
	public function getBucket() {
		return $this->t_bucket;
	}
	public function setBucket( $v ) {
		$v = trim( $v );
		if( is_file($v) ) {
			$this->t_bucket = array_merge( $this->t_bucket, file($v,FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) );
		} else {
			$this->t_bucket[] = $v;
		}
		return true;
	}


	public function getRegion() {
		return $this->region;
	}
	public function setRegion( $v ) {
		$v = trim( $v );
		if( !in_array($v,self::AWS_REGION) ) {
			return false;
		}
		$this->region = $v;
		return true;
	}


	public function getMaxChild() {
		return $this->max_child;
	}
	public function setMaxChild( $v ) {
		$this->max_child = (int)$v;
		return true;
	}

	
	public function getTests() {
		return $this->tests;
	}
	public function setTests( $v ) {
		$v = strtolower( trim($v) );
		$v = preg_replace( '#[^a-z]#', '', $v );
		$v = preg_replace( '#[^sglw]#', '', $v );
		$this->tests = $v;
		return true;
	}

	
	public function getGlue() {
		return $this->t_glue;
	}
	public function setGlue( $v ) {
		$this->t_glue = str_split( trim($v), 1 );
		return true;
	}

	
	public function getPermutation() {
		return $this->permutation;
	}
	public function setPermutation( $v ) {
		$this->permutation = (int)$v;
		return true;
	}
	
	
	public function getVerbosity() {
		return $this->verbosity;
	}
	public function setVerbosity( $v ) {
		$this->verbosity = (int)$v;
		return true;
	}

	
	private function cleanString( $str )
	{
		$str = preg_replace( '#[^a-z0-9\.\-\_]#', '', strtolower($str) );
		//$str = preg_replace( '#[^a-z0-9]#', self::WORD_SEPARATOR, $str );
		return $str;
	}

	
	private function prepare4permutation( $str )
	{
		$str = preg_replace( '#[^a-z0-9]#', self::WORD_SEPARATOR, $str );
		return $str;
	}
	
	
	// http://stackoverflow.com/questions/16238510/pcntl-fork-results-in-defunct-parent-process
	// Thousand Thanks!
	private function signal_handler( $signal, $pid=null, $status=null )
	{
		// If no pid is provided, Let's wait to figure out which child process ended
		if( !$pid ){
			$pid = pcntl_waitpid( -1, $status, WNOHANG );
		}
		
		// Get all exited children
		while( $pid > 0 )
		{
			if( $pid && isset($this->t_process[$pid]) ) {
				// I don't care about exit status right now.
				//  $exitCode = pcntl_wexitstatus($status);
				//  if($exitCode != 0){
				//      echo "$pid exited with status ".$exitCode."\n";
				//  }
				// Process is finished, so remove it from the list.
				$this->n_child--;
				unset( $this->t_process[$pid] );
			}
			elseif( $pid ) {
				// Job finished before the parent process could record it as launched.
				// Store it to handle when the parent process is ready
				$this->t_signal_queue[$pid] = $status;
			}
			
			$pid = pcntl_waitpid( -1, $status, WNOHANG );
		}
		
		return true;
	}

	
	private function init()
	{
		$this->t_prefix = array_map( array($this,'cleanString'), $this->t_prefix );
		$this->t_suffix = array_map( array($this,'cleanString'), $this->t_suffix );
		$this->t_bucket = array_map( array($this,'cleanString'), $this->t_bucket );
		
		if( $this->permutation >= 1 )
		{
			$tmp = array_merge( $this->t_prefix, $this->t_suffix );
			$this->t_prefix = $tmp;
			$this->t_suffix = $tmp;
		
			if( $this->permutation >= 2 )
			{
				$this->t_bucket = $this->createElementPermutations( $this->t_bucket );
				
				if( $this->permutation >= 3 )
				{
					$this->t_prefix = $this->createElementPermutations( $this->t_prefix );
					$this->t_suffix = $this->createElementPermutations( $this->t_suffix );
				}
			}
		}
		
		$this->t_prefix = array_unique( $this->t_prefix );
		$this->t_prefix[] = '';
		//sort( $this->t_prefix );
		
		$this->t_suffix = array_unique( $this->t_suffix );
		$this->t_suffix[] = '';
		//sort( $this->t_suffix );

		$this->t_bucket = array_unique( $this->t_bucket );
		//sort( $this->t_bucket );
		
		//var_dump($this->t_prefix);
		//var_dump($this->t_suffix);
		//var_dump($this->t_bucket);
		//exit();
	}
	
	
	public function run()
	{
		$this->init();
		$this->createGobalPermutations();

		$n_bucket = count( $this->t_bucket );
		echo $n_bucket." buckets to test.\n\n";
	
		if( $this->disable_test ) {
			echo implode( "\n", $this->t_bucket )."\n";
			exit();
		}
		
		posix_setsid();
		declare( ticks=1 );
		pcntl_signal( SIGCHLD, array($this,'signal_handler') );

		for( $current=0 ; $current<$n_bucket ; )
		{
			if( $this->n_child < $this->max_child )
			{
				$pid = pcntl_fork();
				
				if( $pid == -1 ) {
					// fork error
				} elseif( $pid ) {
					// father
					$this->n_child++;
					$current++;
					$this->t_process[$pid] = uniqid();
			        if( isset($this->t_signal_queue[$pid]) ){
			        	$this->signal_handler( SIGCHLD, $pid, $this->t_signal_queue[$pid] );
			        	unset( $this->t_signal_queue[$pid] );
			        }
				} else {
					// child process
					$this->testBucket( $this->t_bucket[$current] );
					exit( 0 );
				}
			}

			usleep( $this->sleep );
		}
	}
	
	
	private function createGobalPermutations()
	{
		$t_variations = [];
		
		foreach( $this->t_bucket as $b )
		{
			foreach( $this->t_prefix as $p ) {
				foreach( $this->t_suffix as $s ) {
					$str = $b;
					if( $p != '' ) {
						$str = $p.self::WORD_SEPARATOR.$str;
					}
					if( $s != '' ) {
						$str = $str.self::WORD_SEPARATOR.$s;
					}
					foreach( $this->t_glue as $sep ) {
						$t_variations[] = str_replace( self::WORD_SEPARATOR, $sep, $str );
					}
				}
			}
		}
		
		$t_variations = array_unique( $t_variations );
		sort( $t_variations );
		$this->t_bucket = $t_variations;
	}
	
	
	private function createElementPermutations( $array )
	{
		$t_final_permut = [];
		$array = array_map( array($this,'prepare4permutation'), $array );

		foreach( $array as $i )
		{
			$tmp = explode( self::WORD_SEPARATOR, $i ); // ['www','domain','com']
			if( count($tmp) <= 1 ) {
				$t_final_permut[] = $i;
			} else {
				$t_permut = [];
				$this->getPermutations( $tmp, $t_permut );
				foreach( $t_permut as $p ) {
					$t_final_permut[] = implode( self::WORD_SEPARATOR, $p );
				}
				// add each part of the element ?
				//$t_final_permut = array_merge( $t_final_permut, $tmp ); 
			}
		}
		
		return $t_final_permut;
	}
	
	
	private function getPermutations( &$array, &$results, $start_i=0 )
	{
		if( $start_i == sizeof($array)-1 ) {
			array_push( $results, $array );
		}
		
		for( $i=$start_i; $i<sizeof($array); $i++ ) {
			//Swap array value at $i and $start_i
			$t = $array[$i];
			$array[$i] = $array[$start_i];
			$array[$start_i] = $t;
	
			//Recurse
			$this->getPermutations( $array, $results, $start_i+1 );
	
			//Restore old order
			$t = $array[$i];
			$array[$i] = $array[$start_i];
			$array[$start_i] = $t;
		}
	}
	
	
	private function testBucket( $bucket_name )
	{
		ob_start();
		
		$bucket = new Bucket();
		$bucket->setName( $bucket_name );

		$e = $bucket->exist( $http_code );
		if( $e ) {
			echo 'Testing: ';
			$this->output( $bucket->getName().", FOUND! (".$http_code.")", 'green' );
			echo "\n";
		} else {
			if( $this->verbosity == 0 ) {
				echo 'Testing: ';
				$this->output( $bucket->getName().", not found (".$http_code.")", 'light_grey' );
				echo "\n";
			}
		}
		
		if( !$e || !preg_match('#[sglw]#',$this->tests) ) {
			return ;
		}
		
		echo "Testing permissions: ";
		
		if( strstr($this->tests,'s') ) {
			$s = $bucket->canSetAcl();
			$this->printTestResult( 'put ACL',  $s, 'red' );
			if( $s == self::TEST_SUCCESS ) {
				return;
			}
			echo ', ';
		}
		
		if( strstr($this->tests,'g') ) {
			$g = $bucket->canGetAcl();
			$this->printTestResult( 'get ACL',  $g, 'orange' );
			echo ', ';
		}
		
		if( strstr($this->tests,'l') ) {
			$l = $bucket->canList();
			$this->printTestResult( 'list',  $l, 'orange' );
			echo ', ';

			$h = $bucket->canListHTTP();
			$this->printTestResult( 'HTTP list',  $h, 'orange' );
			echo ', ';
		}
		
		if( strstr($this->tests,'w') ) {
			$w = $bucket->canWrite();
			$this->printTestResult( 'write',  $w, 'orange' );
			echo ', ';
		}
				
		$result = ob_get_contents();
		ob_end_clean();
		
		echo $result."\n";
	}
	
	
	private function printTestResult( $test_name, $result, $color_if_success )
	{
		//var_dump( $test_name.'='.$result );
		
		if( $result == self::TEST_SUCCESS && (in_array($test_name,['put aCL','write']) || $this->verbosity <= 3) ) {
			$this->output( $test_name.' success', $color_if_success );
		} elseif( $this->verbosity <= 1 ) {
			if( $result == self::TEST_FAILED ) {
				$this->output( $test_name.' failed', 'light_grey' );
			} else {
				$this->output( $test_name.' an error occurred', 'light_cyan' );
			}
		}
	}
	
	
	private function output( $txt, $color )
	{
		if( $this->disable_color ) {
			echo $txt;
		} else {
			Utils::_print( $txt, $color );
		}
	}
}
