# s3-buckets-finder
PHP tool to find Amazon S3 bucket  
Note that this is an automated tool, manual check is still required.  

```
Usage: php s3-buckets-extractor.php [OPTIONS] -b <bucket name>

Options:
	--bucket	single bucket name or listing file
	--region	set region (not implement yet)
	--thread	max threads, default=5
	--perform	tests to perform, default=esglw
				e: test if exist (always performed)
				s: set ACL
				g: get ACL
				l: list
				w: write
	--no-color	disable colored output
	-h, --help	print this help
	-v,--verbosity	set verbosity, default=0
				0: everything
				1: do not display not found
				2: display only permissions success
				3: display only set ACL and write permission success

Examples:
	php s3-buckets-finder.php --bucket test-test
	php s3-buckets-finder.php --bucket listing.txt --perform e --thread 10
	php s3-buckets-finder.php --bucket listing.txt --no-color --verbosity 3
```

I don't believe in license.  
You can do want you want with this program.  
