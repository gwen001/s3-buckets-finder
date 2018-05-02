#!/usr/bin/php
<?php

/**
 * I don't believe in license
 * You can do want you want with this program
 * - gwen -
 */

function __autoload( $c ) {
	include( $c.'.php' );
}


set_time_limit( 0 );


// parse command line
{
	$options = [
		'bucket:',
		'detect-region',
		'force-recurse',
		'google',
		'glue:',
		'h',
		'help',
		'list',
		'max-depth:',
		'no-color',
		'perform:',
		'permut:',
		'prefix:',
		'provider:',
		'region:',
		'suffix:',
		'thread:',
		'verbosity:',
	];
	$t_options = getopt( '', $options );
	//var_dump( $t_options );

	$bruteforcer = new BucketBruteForcer();

	$argc = $_SERVER['argc'] - 1;

	foreach( $t_options as $k=>$v )
	{
		switch( $k )
		{
			case 'bucket':
				$bruteforcer->setBucket( $v );
				break;

			case 'detect-region':
				$bruteforcer->detectRegion();
				break;
			
			case 'force-recurse':
				$bruteforcer->forceRecurse();
				break;
				
			case 'glue':
				$bruteforcer->setGlue( $v );
				break;
				
			case '-h':
			case 'help':
				Utils::help();
				break;

			case 'list':
				$bruteforcer->disableTest();
				break;
				
			case 'max-depth':
				$bruteforcer->setMaxDepth( $v );
				break;
				
			case 'no-color':
				$bruteforcer->disableColor();
				break;
				
			case 'perform':
				$bruteforcer->setTests( $v );
				break;
				
			case 'permut':
				$bruteforcer->setPermutation( $v );
				break;
				
			case 'prefix':
				$bruteforcer->setPrefix( $v );
				break;
				
			case 'provider':
				$bruteforcer->setProvider( $v );
				break;
				
			case 'region':
				if( !$bruteforcer->setRegion($v) ) {
					Utils::help( 'Invalid region "'.$v.'" ' );
				}
				break;
				
			case 'suffix':
				$bruteforcer->setSuffix( $v );
				break;
				
			case 'thread':
				$bruteforcer->setMaxChild( $v );
				break;
				
			case '-v':
			case 'verbosity':
				$bruteforcer->setVerbosity( (int)$v );
				break;

			default:
				Utils::help( 'Unknown option: '.$k );
		}
	}

	if( !$bruteforcer->getBucket() ) {
		Utils::help( 'Bucket not found' );
	}
}
// ---


// main loop
{
	$bruteforcer->run();
}
// ---


exit();
