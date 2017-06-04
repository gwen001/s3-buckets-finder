#!/usr/bin/php
<?php

function computeParams( $t_params )
{
	$str = 'q='.$t_params['q'];
	$str .= '&start='.$t_params['start'];
	$str .= '&num='.$t_params['num'];
	
	return $str;
}

function usage( $err=null ) {
  echo 'Usage: '.$_SERVER['argv'][0]." <keyword>\n";
  if( $err ) {
    echo 'Error: '.$err."\n";
  }
  exit();
}

if( $_SERVER['argc'] != 2 ) {
  usage();
}

$keyword = strtolower( $_SERVER['argv'][1] );

define( 'GG_NUM', 1000 );
define( 'GG_STOP', 5000 );
define( 'GG_SLEEP', 1000000 );
define( 'GG_URL', 'https://www.google.co.in/search' );

define( 'SITE_SEARCH', 's3.amazonaws.com' );

$t_user_agent = [
	'Mozilla/5.0 (Windows; U; Windows NT 6.0;en-US; rv:1.9.2) Gecko/20100115 Firefox/3.6',
	'Mozilla/5.0 (X11; Linux x86_64; rv:31.0) Gecko/20100101 Firefox/31.0 Iceweasel/31.7.0',
	'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)',
	'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36',
	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.75.14 (KHTML, like Gecko) Version/7.0.3 Safari/7046A194A',
	'Mozilla/5.0 (Windows NT 6.3; rv:36.0) Gecko/20100101 Firefox/36.0',
	'Opera/9.80 (Windows NT 6.0) Presto/2.12.388 Version/12.14',
	'Mozilla/5.0 (X11; Linux 3.5.4-1-ARCH i686; es) KHTML/4.9.1 (like Gecko) Konqueror/4.9',
];
$cnt_user_agent = count($t_user_agent) - 1;

$gg_params = [
	'start' => 0,
	'num'   => GG_NUM,
	'q'     => '',
];
$gg_params['q'] = 'site%3A'.SITE_SEARCH.'+inurl:'.$keyword;

$t_buckets = [];

for( $start=0 ; $start<GG_STOP ; $start+=GG_NUM )
{
	$gg_params['start'] = $start;
	$url = GG_URL.'?'.computeParams( $gg_params );
	//var_dump( $url );

	$cmd = 'lynx -useragent="'.$t_user_agent[rand(0,$cnt_user_agent)].'" -dump '.$url;
	echo $cmd."\n";
	exec( $cmd, $output );
	//$output = file( 'result.dat', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
	file_put_contents( 'result_'.$start.'.dat', $output );
	//var_dump( $output );
	
	foreach( $output as $l )
	{
		$l = trim( strtolower($l) );
		$m = preg_match_all( '#http[s]?://.*\.?'.SITE_SEARCH.'.*#', $l, $matches );
		
		if( $m )
		{
			//var_dump( $matches );
			foreach( $matches as $mm )
			{
				$parse_url = parse_url( $mm[0] );
				//var_dump( $parse_url );
				
				if( !strstr($parse_url['host'],SITE_SEARCH) ) {
					continue;
				}
				
				$tmp = explode( '.', $parse_url['host'] );
				if( count($tmp) > 3 ) {
					$bucket = implode( '.', array_slice($tmp,0,-3) );
					//echo '>>>>> '.$bucket." <<<<<<<<<\n";
				} else {
					$tmp = explode( '/', $parse_url['path'] );
					$bucket = $tmp[1];
				}
				//if( )
				
				$t_buckets[] = $bucket;
			}
		}
	}
	
	usleep( GG_SLEEP );
}

$t_buckets = array_unique( $t_buckets );

echo "\n".count($t_buckets)." found.\n\n";
foreach( $t_buckets as $b ) {
	echo $b."\n";
}

exit();
