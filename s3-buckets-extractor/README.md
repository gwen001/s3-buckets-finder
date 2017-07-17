# s3-buckets-extractor
PHP tool to extract datas from Amazon S3 bucket  
Note that this is an automated tool, manual check is still required.  

```
Usage: php s3-buckets-extractor.php [OPTIONS] -b <bucket name>

Options:
	-b	set bucket name (required)
	-d	set destination directory
	-g	grab the directories/files
	-h	print this help
	-i	extensions to ignore (default=bmp;gif;jpg;jpeg;svg)
	-r	set the region
	-v	set verbosity: 0=none, 1=only readable, 2=all (default=2)

Examples:
	php s3-buckets-extractor.php -b test-test -g -d /tmp
	php s3-buckets-extractor.php -b test-test -v 1 -i txt;ttf;woff
```

# Requirements
```
apt-get install awscli
aws configure
```

<img src="https://raw.githubusercontent.com/gwen001/s3-buckets-finder/master/s3-buckets-extractor/example-ex.png" alt="s3 buckets extractor example">
<br><br>

I don't believe in license.  
You can do want you want with this program.  
