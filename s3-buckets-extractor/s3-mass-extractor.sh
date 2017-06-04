#!/bin/bash


function usage() {
    echo "Usage: "$0" <buckets list>"
    if [ -n "$1" ] ; then
	echo "Error: "$1"!"
    fi
    exit
}

if [ ! $# -eq 1 ] ; then
    usage
fi

blist=$1

if [ ! -f $blist ] ; then
	usage "File not found!"
fi

for b in $(cat $blist) ; do
	output="__"$b".txt"
	echo "Extracting "$b", output to "$output
	php s3-buckets-extractor.php -v 1 -b $b >> $output
done
