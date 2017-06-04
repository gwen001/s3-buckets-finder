<?php

// http://stackoverflow.com/questions/16238510/pcntl-fork-results-in-defunct-parent-process
// Thousand Thanks!
function signal_handler( $signal, $pid=null, $status=null )
{
	global $t_process, $n_child, $t_signal_queue;
	// If no pid is provided, Let's wait to figure out which child process ended
	if( !$pid ){
		$pid = pcntl_waitpid( -1, $status, WNOHANG );
	}
	
	// Get all exited children
	while( $pid > 0 )
	{
		if( $pid && isset($t_process[$pid]) ) {
			// I don't care about exit status right now.
			//  $exitCode = pcntl_wexitstatus($status);
			//  if($exitCode != 0){
			//      echo "$pid exited with status ".$exitCode."\n";
			//  }
			// Process is finished, so remove it from the list.
			$n_child--;
			unset( $t_process[$pid] );
		}
		elseif( $pid ) {
			// Job finished before the parent process could record it as launched.
			// Store it to handle when the parent process is ready
			$t_signal_queue[$pid] = $status;
		}
		
		$pid = pcntl_waitpid( -1, $status, WNOHANG );
	}
	
	return true;
}



$t_exclude = [
	['167772160','184549375'],
	['2886729728','2887778303'],
	['3232235520','3232301055'],
];


$start = ip2long( $_SERVER['argv'][1] );
$end = ip2long( $_SERVER['argv'][2] );
if( $start > $end ) {
	$tmp = $end;
	$end = $start;
	$start = $tmp;
}
$very_start = 0;
$very_end = 4294967295;
$jump = 255;

$n_child = 0;
$max_child = 10;
$t_process = [];
$t_signal_queue = [];
$cnt_notice = 500;

$dst_dir = dirname( __FILE__).'/threatcrowd';
if( !is_dir($dst_dir) ) {
	mkdir( $dst_dir, 0777, true );
}
if( !is_dir($dst_dir) ) {
	exit( "Error: output directory not found!\n" );
}

posix_setsid();
declare( ticks=1 );
//pcntl_signal( SIGHUP,  'signal_handler' );
//pcntl_signal( SIGINT,  'signal_handler' );
//pcntl_signal( SIGQUIT, 'signal_handler' );
//pcntl_signal( SIGABRT, 'signal_handler' );
//pcntl_signal( SIGKILL, 'signal_handler' );
//pcntl_signal( SIGIOT,  'signal_handler' );
pcntl_signal( SIGCHLD, 'signal_handler' );
//pcntl_signal( SIGTERM, 'signal_handler' );
//pcntl_signal( SIGTSTP, 'signal_handler' );


for( $ip=$start ; $ip<=$end && $ip<=$very_end ; )
//for( $current_pointer=0 ; $current_pointer<$n_daemon ; )
{
	if( $n_child < $max_child )
	{
		$pid = pcntl_fork();

		if( $pid == -1 ) {
			// fork error
		} elseif( $pid ) {
			// father
			$n_child++;
			$ip += $jump;
			$t_process[$pid] = uniqid();
	        if( isset($t_signal_queue[$pid]) ){
	        	$signal_handler( SIGCHLD, $pid, $t_signal_queue[$pid] );
	        	unset( $t_signal_queue[$pid] );
	        }
		} else {
			// child process
			leech( $ip );
			exit( 0 );
		}
	}

	usleep( 10000 );
}


function leech( $start )
{
	global $jump, $dst_dir;
	
	$end = $start + $jump;

	$ip = long2ip( $start );
	$tmp = explode( '.', $ip );
	$digit = $tmp[0];
	$thedir = $dst_dir.'/'.$tmp[0].'/'.$tmp[1].'/'.$tmp[2];
	
	if( !is_dir($thedir) ) {
		mkdir( $thedir, 0777, true );
	}

	for( $ip=$start ; $ip<=$end ; $ip++ )
	{
		if( isExcluded($ip) )
		{
			echo long2ip($ip)." is excluded\n";
		}
		else
		{
			$theip = long2ip( $ip );
			$src = 'https://www.threatcrowd.org/searchApi/v1/api.php?type=ip&query='.$theip;
			//$src = 'https://www.threatcrowd.org/searchApi/v1/api.php?type=ip&query=54.231.80.232';
			echo $src."\n";
			$dst = $thedir.'/'.$theip.'.dat';
			//var_dump( $dst );
			$content = file_get_contents( $src );
			if( trim($content) != '' ) {
				file_put_contents( $dst, $content );
			}
			
			usleep( rand(10000,50000) );
		}
	}
}


function isExcluded( $ip )
{
	global $t_exclude;
	
	foreach( $t_exclude as $range ) {
		if( $ip>=$range[0] && $ip<=$range[1] ) {
			return true;
		}
	}
	
	return false;
}




exit();

