#!/usr/bin/php
<?php

function usage( $err=null ) {
	echo 'Usage: '.$_SERVER['argv'][0]." <company> <test the discovered buckets>\n";
	if( $err ) {
		echo 'Error: '.$err.!"\n";
	}
	exit();
}

if( $_SERVER['argc'] < 2 || $_SERVER['argc'] > 3 ) {
	usage();
}

$company = strtolower( $_SERVER['argv'][1] );

if( $_SERVER['argc'] == 3 ) {
	$b_test = true;
}

define( 'SITE_SEARCH', 's3.amazonaws.com' );

define( 'GH_STOP', 5000 );
define( 'GG_SLEEP', 1000000 );
define( 'GH_TOKEN', '50012909f8226190159baed46edec57819ae8031' );

$t_buckets = [];

echo "Searching on GitHub...\n\n";
$cmd = 'php /home/gwen/Sécurité/mytools/github-search/github-search.php -n -r 1000 -t '.GH_TOKEN.' -s '.SITE_SEARCH.' -o '.$company;
exec( $cmd, $output );
//file_put_contents( 'output.html', $output );
//$output = array( file_get_contents( 'output.html' ) );
//var_dump( $output );

foreach( $output as $line )
{
	$m = preg_match( '#link:[\s]*(.*)#', $line, $matches );
	if( !$m ) {
		continue;
	}
	
	$f_link = str_replace( 'github.com', 'raw.githubusercontent.com', $matches[1] );
	$f_link = str_replace( '/blob', '', $f_link );
	//$f_link = 'https://raw.githubusercontent.com/airbnb/smartstack-cookbook/82f73d1f13906889ca4085e69be00a40f002458a/Vagrantfile';
	echo "Found in: ".$f_link."\n";
	$f_content = @file_get_contents( $f_link );
	//$f_content = @file_get_contents( 'file.html' );
	//file_put_contents( 'file.html', $f_content );
	$m = preg_match_all( '#([a-zA-Z0-9\-_\.]*)'.SITE_SEARCH.'[/]?([a-zA-Z0-9\.\-_]*)#i', $f_content, $matches );
	
	if( $m ) {
		//var_dump( $matches );
		foreach( $matches[1] as $k=>$m )
		{
			$m = trim( $m, ' ./' );
			if( $m != '' ) {
				$t_buckets[] = $m;
			} else {
				$matches[2][$k] = trim( $matches[2][$k], ' ./' );
				if( $matches[2][$k] != '' ) {
					$tmp = explode( '/', $matches[2][$k] );
					$t_buckets[] = $tmp[0];
				}
			}
		}
	}
	
	//break;
}


$t_buckets = array_unique( $t_buckets );

echo "\n".count($t_buckets)." buckets found.\n\n";
echo implode( "\n", $t_buckets );
echo "\n";

if( isset($b_test) && $b_test ) {
	$b_file = tempnam( '/tmp', 's3-' );
	//var_dump( $b_file );
	file_put_contents( $b_file, implode("\n",$t_buckets) );
	echo "\nTesting the permissions...\n\n";
	$cmd = '/opt/bin/s3-buckets-bruteforce '.$b_file;
	exec( $cmd, $result );
	echo implode( "\n", $result );
}

echo "\n\n";
@unlink( $b_file );

exit();
