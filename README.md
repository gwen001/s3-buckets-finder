# s3-buckets-finder
PHP tool to find Amazon S3 bucket  
Note that this is an automated tool, manual check is still required.  

```
Usage: php s3-buckets-extractor.php [OPTIONS] --bucket <bucket>

Options:
	--bucket	single bucket name or listing file
	--glue	characters used as a separator when concatenate all elements
	-h, --help	print this help
	--list	do no perform any test, simply list the generated permutations
	--no-color	disable colored output
	--prefix	single prefix or listing file
	--suffix	single suffix or listing file
	--perform	tests to perform, default=esglw
				e: test if exist (always performed)
				s: set ACL
				g: get ACL
				l: list
				w: write
	--permut	permutation can be tested, default=0
				0: no permutation
				1: if provided prefix and suffix are permuted (prefix.<bucket>.suffix, suffix.<bucket>.prefix)
				2: permutation applied only on the bucket name (a.b.c, b.c.a, ...)
				3: permutation applied on the bucket name and prefix and suffix if provided
	--recurs	(not implement yet) if a bucket is found, another level will be added (permutations are not applied), ex:
				if <bucket> is found then test <bucket>-xxx
				if <bucket>-xxx is found then test <bucket>-xxx-yyy
	--region	set region (not implement yet)
	--thread	max threads, default=5
	-v,--verbosity	set verbosity, default=0
				0: everything
				1: do not display not found
				2: display only permissions success
				3: display only set ACL and write permission success

Examples:
	php s3-buckets-finder.php --bucket gwen001-test002
	php s3-buckets-finder.php --bucket listing.txt --no-color --verbosity 1
	php s3-buckets-finder.php --bucket listing1.txt --bucket listing2.txt --bucket listing3.txt --perform e --thread 10
	php s3-buckets-finder.php --bucket listing.txt --prefix prefix.txt --suffix suffix1.txt --suffix2.txt --perform e --thread 10
```

I don't believe in license.  
You can do want you want with this program.  
