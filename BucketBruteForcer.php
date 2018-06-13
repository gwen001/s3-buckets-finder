<?php

/**
 * I don't believe in license
 * You can do want you want with this program
 * - gwen -
 */

class BucketBruteForcer
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
	
	const T_PROVIDER = [ 'Amazon', 'Google', 'Digitalocean' ];
	const DEFAULT_PROVIDER = 'Amazon';
	
	const WORD_SEPARATOR = '__SEP__';
	
	const TEST_UNKNOW  = -1;
	const TEST_FAILED  = 0;
	const TEST_SUCCESS = 1;
	
	private $t_bucket = [];
	private $t_prefix = [];
	private $t_suffix = [];
	
	private $t_glue = [ '', '-', '.', '_' ];
	
	private $tests = 'sglhw';
	
	private $region = '';
	
	private $permutation = 0;
	
	private $disable_color = false;
	
	private $disable_test = false;
	
	private $max_depth = 1;
	private $current_depth = 1;
	
	private $force_recurse = false;
	
	private $verbosity = 0;
	
	private $detect_region = false;
	
	private $provider = self::DEFAULT_PROVIDER;
	private $bucket_class = '';
	
	private $n_child = 0;
	private $max_child = 5;
	private $loop_sleep = 100000;
	private $random_min_sleep = 1000;
	private $random_max_sleep = 50000;
	private $t_process = [];
	private $t_signal_queue = [];
	private $cnt_notice = 500;


	public function __construct() {
	}


	public function forceRecurse() {
		$this->force_recurse = true;
	}
	
	
	public function detectRegion() {
		return $this->detect_region = true;
	}
	
	
	public function disableColor() {
		$this->disable_color = true;
	}
	
	
	public function disableTest() {
		$this->disable_test = true;
	}
	
	
	public function getMaxDepth() {
		return $this->max_depth;
	}
	public function setMaxDepth( $v ) {
		$this->max_depth = (int)$v;
		return true;
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
		if( !in_array($v,AmazonBucket::T_REGION) && !in_array($v,DigitaloceanBucket::T_REGION) ) {
			return false;
		}
		$this->region = $v;
		return true;
	}


	public function getProvider() {
		return $this->provider;
	}
	public function setProvider( $v ) {
		$v = ucfirst( strtolower(trim($v)) );
		if( !in_array($v,self::T_PROVIDER) ) {
			Utils::help( 'Provider not supported' );
		}
		$this->provider = $v;
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
	public function signal_handler( $signal, $pid=null, $status=null )
	{
		$pid = (int)$pid;
		
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
		$this->bucket_class = $this->provider.'Bucket';
		
		if( !strcasecmp($this->provider,'digitalocean') ) {
			$this->detect_region = true;
		}
		
		$this->t_prefix = array_map( array($this,'cleanString'), $this->t_prefix );
		$this->t_suffix = array_map( array($this,'cleanString'), $this->t_suffix );
		$this->t_bucket = array_map( array($this,'cleanString'), $this->t_bucket );
		
		if( $this->permutation >= 1 )
		{
			if( count($this->prefix) && count($this->suffix) ) {
				$tmp = array_merge( $this->t_prefix, $this->t_suffix );
				$this->t_prefix = $tmp;
				$this->t_suffix = $tmp;
			}
			
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
	}
	
	
	public function run()
	{
		$this->init();
		$this->t_bucket = $this->createGobalPermutations( $this->t_bucket, $this->t_prefix, $this->t_suffix, $this->t_glue );

		if( $this->disable_test ) {
			echo implode( "\n", $this->t_bucket )."\n";
			exit();
		}

		$this->n_bucket = count( $this->t_bucket );
		echo $this->n_bucket." buckets to test.\n\n";
		//var_dump($this->t_bucket);
		//exit();
			
		posix_setsid();
		declare( ticks=1 );
		pcntl_signal( SIGCHLD, array($this,'signal_handler') );
		
		$this->loop( $this->t_bucket );
	}
	
	
	private function loop( $t_buckets )
	{
		$n_bucket = count( $t_buckets );
		//var_dump($n_bucket);
		//echo $n_bucket." buckets to test.\n\n";
		
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
					usleep( rand($this->random_min_sleep,$this->random_max_sleep) );
					$this->testBucket( $t_buckets[$current] );
					exit( 0 );
				}
			}

			usleep( $this->loop_sleep );
		}
		
		while( $this->n_child ) {
			// surely leave the loop please :)
			sleep( 1 );
		}
	}
	
	
	private function createGobalPermutations( $t_bucket, $t_prefix, $t_suffix, $t_glue )
	{
		$t_variations = [];
		
		foreach( $t_bucket as $b )
		{
			foreach( $t_prefix as $p ) {
				foreach( $t_suffix as $s ) {
					$str = $b;
					if( $p != '' ) {
						$str = $p.self::WORD_SEPARATOR.$str;
					}
					if( $s != '' ) {
						$str = $str.self::WORD_SEPARATOR.$s;
					}
					foreach( $t_glue as $sep ) {
						$t_variations[] = str_replace( self::WORD_SEPARATOR, $sep, $str );
					}
				}
			}
		}
		
		$t_variations = array_unique( $t_variations );
		sort( $t_variations );
		
		return $t_variations;
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
			// swap array value at $i and $start_i
			$t = $array[$i];
			$array[$i] = $array[$start_i];
			$array[$start_i] = $t;
	
			// recurse
			$this->getPermutations( $array, $results, $start_i+1 );
	
			// restore old order
			$t = $array[$i];
			$array[$i] = $array[$start_i];
			$array[$start_i] = $t;
		}
	}
	
	
	/**
	 * @todo only test the same separator character ??
	 */
	private function recurse( $bucket_name )
	{
		/*if( $this->verbosity <= 1 ) {
			$this->output( 'Recursion level '.$this->current_depth."\n", 'yellow' );
		}*/
		
		$m = preg_match( '#[^0-9a-z]#i', $bucket_name, $matches );
		//var_dump( $matches );
		if( $m ) {
			$t_glue = [ $matches[0] ];
		} else {
			$t_glue = $this->t_glue;
		}
		
		$this->t_bucket[] = $bucket_name; // we don't want to retest this current bucket
		$t_new_variations = $this->createGobalPermutations( [$bucket_name], $this->t_prefix, $this->t_suffix, $t_glue );
		$t_new_variations = array_diff( $t_new_variations, $this->t_bucket );
		sort( $t_new_variations ); // needed because the line above can lost some keys
		//var_dump( $t_new_variations );

		$this->n_child = 0;
		// subthreads that create x subthreads that create x subthreads that create x subthreads that create x subthreads...........
		// do you really want that ?? hell no!
		$this->max_child = 1; // so childs can only create 1 child, that's it mother fucker!
		$this->t_process = [];
		$this->t_signal_queue = [];
		
		$this->loop( $t_new_variations );
	}
	
	
	private function testBucket( $bucket_name )
	{
		ob_start();
		
		$bucket = new $this->bucket_class();
		$bucket->setName( $bucket_name );
		$bucket->setRegion( $this->region );
		
		$e = $bucket->exist( $http_code );
		if( $e ) {
			echo 'Testing: ';
			$this->output( $bucket->getName()." FOUND! (".$http_code.")", (($http_code == 200) ? 'light_green' : 'green') );
			echo "\n";
		} else {
			if( $this->verbosity == 0 ) {
				echo 'Testing: ';
				$this->output( $bucket->getName()." , not found (".$http_code.")", 'light_grey' );
				echo "\n";
			}
		}
		
		if( $e && $this->detect_region )
		{
			$region = $bucket->detectRegion();

			if( $region ) {
				echo 'Region detected: '.$region."\n";
				$bucket->setRegion( $region );
			} else {
				echo "Region detected: not found!\n";
				$bucket->setRegion( null );
			}
		}
		
		if( $e && preg_match('#[sglw]#',$this->tests) )
		{
			echo "Testing permissions: ";
			
			if( strstr($this->tests,'s') ) {
				$s = $bucket->canSetAcl();
				$this->printTestResult( 'put ACL',  $s, 'red' );
				if( $s == self::TEST_SUCCESS ) {
					echo "\n";
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
	
				$h = $bucket->canListHTTP(true); // force the request again, because it has already been used to detect the region or not
				$this->printTestResult( 'HTTP list',  $h, 'orange' );
				echo ', ';
			}
			
			if( strstr($this->tests,'w') ) {
				$w = $bucket->canWrite();
				$this->printTestResult( 'write',  $w, 'red' );
				echo ', ';
			}
			
			echo "\n";
		}
		
		$result = ob_get_contents();
		ob_end_clean();
		
		echo $result;
		
		if( ($e || $this->force_recurse) && $this->max_depth && $this->current_depth<$this->max_depth ) {
			$this->current_depth++;
			$this->recurse( $bucket->getName() );
		}
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
