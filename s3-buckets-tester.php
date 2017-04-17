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
	$tester = new BucketTester();

	$argc = $_SERVER['argc'] - 1;

	for( $i=1; $i<=$argc; $i++ ) {
		switch( $_SERVER['argv'][$i] ) {
			case '--bucket':
				$tester->setBucket( $_SERVER['argv'][$i+1] );
				$i++;
				break;

			case '--glue':
				$tester->setGlue( $_SERVER['argv'][$i+1] );
				$i++;
				break;
				
			case '-h':
			case '--help':
				Utils::help();
				break;

			case '--list':
				$tester->disableTest();
				//$i++;
				break;
				
			case '--no-color':
				$tester->disableColor();
				//$i++;
				break;
				
			case '--perform':
				$tester->setTests( $_SERVER['argv'][$i+1] );
				$i++;
				break;
				
			case '--permut':
				$tester->setPermutation( $_SERVER['argv'][$i+1] );
				$i++;
				break;
				
			case '--prefix':
				$tester->setPrefix( $_SERVER['argv'][$i+1] );
				$i++;
				break;
				
			case '--recurs':
				$tester->enableRecursivity();
				//$i++;
				break;
				
			case '--region':
				if( !$tester->setRegion($_SERVER['argv'][$i+1]) ) {
					Utils::help( 'Invalid region: '.$_SERVER['argv'][$i+1] );
				}
				$i++;
				break;
				
			case '--suffix':
				$tester->setSuffix( $_SERVER['argv'][$i+1] );
				$i++;
				break;
				
			case '--thread':
				$tester->setMaxChild( $_SERVER['argv'][$i+1] );
				$i++;
				break;
				
			case '-v':
			case '--verbosity':
				$tester->setVerbosity( (int)$_SERVER['argv'][$i+1] );
				$i++;
				break;

			default:
				Utils::help( 'Unknown option: '.$_SERVER['argv'][$i] );
		}
	}

	if( !$tester->getBucket() ) {
		Utils::help( 'Bucket not found' );
	}
}
// ---


// main loop
{
	$tester->run();
}
// ---


exit();

?>
