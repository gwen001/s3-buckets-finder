#!/usr/bin/php
<?php

function computeParams( $t_params )
{
	$str = 'q='.$t_params['q'];
	$str .= '&start='.$t_params['start'];
	$str .= '&num='.$t_params['num'];
	$str .= '&meta='.$t_params['meta'];
	$str .= '&hl='.$t_params['hl'];
	
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

define( 'GG_NUM', 100 );
define( 'GG_STOP', 300 );
define( 'GG_SLEEP', 1000000 );
define( 'GG_URL', 'https://www.google.com/search' );

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
	'hl'    => 'en',
	'meta'  => '',
	'start' => 0,
	'num'   => GG_NUM,
	'q'     => '',
];
$gg_params['q'] = '%40"site:s3.amazonaws.com+inurl:'.$keyword.'"';
//$gg_params['q'] = 'site:s3.amazonaws.com inurl:'.$keyword;

$t_buckets = [];

for( $start=0 ; $start<GG_STOP ; $start+=GG_NUM )
{
	$gg_params['start'] = $start;
	$url = GG_URL.'?'.computeParams( $gg_params );
	var_dump( $url );

	$c = curl_init();
	curl_setopt( $c, CURLOPT_URL, $url );
	curl_setopt( $c, CURLOPT_HEADER, false );
	curl_setopt( $c, CURLOPT_FOLLOWLOCATION, true );
	curl_setopt( $c, CURLOPT_USERAGENT, $t_user_agent[rand(0,$cnt_user_agent)] );
	curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
	$data = curl_exec( $c );
	$code = curl_getinfo( $c, CURLINFO_HTTP_CODE );
	//file_put_contents( '/tmp/result.html', $data );
	//$code = 200;
	//var_dump( $code );
	//$data = file_get_contents( '/tmp/result.html' );
	//var_dump( $data );
	//exit();
	curl_close( $c );

	if( $code != 200 ) {
		continue;
	}

	$doc = new DOMDocument();
	$doc->preserveWhiteSpace = false;
	@$doc->loadHTML( $data );

	$xpath = new DOMXPath( $doc );
	$t_result = $xpath->query("//*[@class='r']/a");
	var_dump( count($t_result) );

	foreach( $t_result as $r )
	{
		$lnk = $r->ownerDocument->saveHTML( $r );
		preg_match_all( '#href="([^"]*)"#', $lnk, $tmp );
		$full_url = str_ireplace( '/url?q=', '', $tmp[1][0] );
		//var_dump( $full_url );
		$t_info = parse_url( $full_url );
		//var_dump( $t_info );

		$a = preg_match( '#(.*)\.s3.amazonaws\.com#', $t_info['host'], $m );

		if( $a ) {
			$t_buckets[] = $m[1];
		} else {
			$tmp = explode( '/', $t_info['path'] );
			$t_buckets[] = $tmp[1];
		}
	}

	usleep( GG_SLEEP );
}

$t_buckets = array_unique( $t_buckets );

foreach( $t_buckets as $b ) {
	echo $b."\n";
}

exit();

?>