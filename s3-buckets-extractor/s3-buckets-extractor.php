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
	$extractor = new BucketExtractor();

	$argc = $_SERVER['argc'] - 1;

	for( $i=1; $i<=$argc; $i++ ) {
		switch( $_SERVER['argv'][$i] ) {
			case '-b':
				@$extractor->setBucket( $_SERVER['argv'][$i+1] );
				$i++;
				break;

			case '-d':
				@$extractor->setDestination( $_SERVER['argv'][$i+1] );
				$i++;
				break;

			case '-g':
				$extractor->setDownload( true );
				break;

			case '-h':
				Utils::help();
				break;

			case '-i':
				@$extractor->setIgnore( $_SERVER['argv'][$i+1] );
				$i++;
				break;

			case '-r':
				$extractor->setRegion( $_SERVER['argv'][$i+1] );
				$i++;
				break;

			case '-v':
				@$extractor->setVerbosity( (int)$_SERVER['argv'][$i+1] );
				$i++;
				break;

			default:
				Utils::help( 'Unknown option: '.$_SERVER['argv'][$i] );
		}
	}

	if( !$extractor->getBucket() ) {
		Utils::help( 'Bucket not found' );
	}
}
// ---


// main loop
{
	$cnt = $extractor->run();

	if( $cnt !== false ) {
		echo "\n".$cnt[0]." objects found, ".$cnt[1]." readable.\n";
	}
}
// ---


exit();

?>
