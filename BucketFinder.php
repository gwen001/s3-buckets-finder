<?php

/**
 * I don't believe in license
 * You can do want you want with this program
 * - gwen -
 */

class BucketFinder
{
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
	
	private $tests = 'sglhw';
	
	private $region = '';
	
	private $disable_color = false;
	
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
	
	
	public function getBucket() {
		return $this->t_bucket;
	}
	public function setBucket( $v ) {
		$v = trim( $v );
		if( is_file($v) ) {
			$this->t_bucket = file( $v, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		} else {
			$this->t_bucket = [$v];
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

	
	public function getVerbosity() {
		return $this->verbosity;
	}
	public function setVerbosity( $v ) {
		$this->verbosity = (int)$v;
		return true;
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


	public function run()
	{
		posix_setsid();
		declare( ticks=1 );
		pcntl_signal( SIGCHLD, array($this,'signal_handler') );

		$n_bucket = count( $this->t_bucket );
	
		for( $current=0 ; $current<$n_bucket ; )
		{
			/*if( ($current_pointer%$this->cnt_notice) == 0 && !in_array($current_pointer,$already_noticed) ) {
				echo "Current ".$current_pointer."...\n";
				$already_noticed[] = $current_pointer;
			}*/
			
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
