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
	$finder = new BucketFinder();

	$argc = $_SERVER['argc'] - 1;

	for( $i=1; $i<=$argc; $i++ ) {
		switch( $_SERVER['argv'][$i] ) {
			case '--bucket':
				$finder->setBucket( $_SERVER['argv'][$i+1] );
				$i++;
				break;

			case '--glue':
				$finder->setGlue( $_SERVER['argv'][$i+1] );
				$i++;
				break;
				
			case '-h':
			case '--help':
				Utils::help();
				break;

			case '--list':
				$finder->disableTest();
				//$i++;
				break;
				
			case '--no-color':
				$finder->disableColor();
				//$i++;
				break;
				
			case '--perform':
				$finder->setTests( $_SERVER['argv'][$i+1] );
				$i++;
				break;
				
			case '--permut':
				$finder->setPermutation( $_SERVER['argv'][$i+1] );
				$i++;
				break;
				
			case '--prefix':
				$finder->setPrefix( $_SERVER['argv'][$i+1] );
				$i++;
				break;
				
			case '--recurs':
				$finder->enableRecursivity();
				//$i++;
				break;
				
			case '--region':
				if( !$finder->setRegion($_SERVER['argv'][$i+1]) ) {
					Utils::help( 'Invalid region: '.$_SERVER['argv'][$i+1] );
				}
				$i++;
				break;
				
			case '--suffix':
				$finder->setSuffix( $_SERVER['argv'][$i+1] );
				$i++;
				break;
				
			case '--thread':
				$finder->setMaxChild( $_SERVER['argv'][$i+1] );
				$i++;
				break;
				
			case '-v':
			case '--verbosity':
				$finder->setVerbosity( (int)$_SERVER['argv'][$i+1] );
				$i++;
				break;

			default:
				Utils::help( 'Unknown option: '.$_SERVER['argv'][$i] );
		}
	}

	if( !$finder->getBucket() ) {
		Utils::help( 'Bucket not found' );
	}
}
// ---


// main loop
{
	$cnt = $finder->run();

	//if( $cnt !== false ) {
	//	echo "\n".$cnt[0]." objects found, ".$cnt[1]." readable.\n";
	//}
}
// ---


exit();

?>
