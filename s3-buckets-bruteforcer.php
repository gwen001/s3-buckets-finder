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
	$bruteforcer = new BucketBruteForcer();

	$argc = $_SERVER['argc'] - 1;

	for( $i=1; $i<=$argc; $i++ ) {
		switch( $_SERVER['argv'][$i] ) {
			case '--bucket':
				$bruteforcer->setBucket( $_SERVER['argv'][$i+1] );
				$i++;
				break;

			case '--force-recurse':
				$bruteforcer->forceRecurse();
				break;
				
			case '--glue':
				$bruteforcer->setGlue( $_SERVER['argv'][$i+1] );
				$i++;
				break;
				
			case '-h':
			case '--help':
				Utils::help();
				break;

			case '--list':
				$bruteforcer->disableTest();
				break;
				
			case '--max-depth':
				$bruteforcer->setMaxDepth( $_SERVER['argv'][$i+1] );
				$i++;
				break;
				
			case '--no-color':
				$bruteforcer->disableColor();
				//$i++;
				break;
				
			case '--perform':
				$bruteforcer->setTests( $_SERVER['argv'][$i+1] );
				$i++;
				break;
				
			case '--permut':
				$bruteforcer->setPermutation( $_SERVER['argv'][$i+1] );
				$i++;
				break;
				
			case '--prefix':
				$bruteforcer->setPrefix( $_SERVER['argv'][$i+1] );
				$i++;
				break;
				
			case '--region':
				if( !$bruteforcer->setRegion($_SERVER['argv'][$i+1]) ) {
					Utils::help( 'Invalid region "'.$_SERVER['argv'][$i+1].'" ' );
				}
				$i++;
				break;
				
			case '--suffix':
				$bruteforcer->setSuffix( $_SERVER['argv'][$i+1] );
				$i++;
				break;
				
			case '--thread':
				$bruteforcer->setMaxChild( $_SERVER['argv'][$i+1] );
				$i++;
				break;
				
			case '-v':
			case '--verbosity':
				$bruteforcer->setVerbosity( (int)$_SERVER['argv'][$i+1] );
				$i++;
				break;

			default:
				Utils::help( 'Unknown option: '.$_SERVER['argv'][$i] );
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

?>