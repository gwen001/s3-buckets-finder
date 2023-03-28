<h1 align="center">s3-buckets-finder</h1>

<h4 align="center">PHP tool to brute force Amazon S3 bucket and test permissions.</h4>

<p align="center">
    <img src="https://img.shields.io/badge/php-%3E=5.5-blue" alt="php badge">
    <img src="https://img.shields.io/badge/license-MIT-green" alt="MIT license badge">
    <a href="https://twitter.com/intent/tweet?text=https%3a%2f%2fgithub.com%2fgwen001%2fs3-buckets-finder%2f" target="_blank"><img src="https://img.shields.io/twitter/url?style=social&url=https%3A%2F%2Fgithub.com%2Fgwen001%2Fs3-buckets-finder" alt="twitter badge"></a>
</p>

<!-- <p align="center">
    <img src="https://img.shields.io/github/stars/gwen001/s3-buckets-finder?style=social" alt="github stars badge">
    <img src="https://img.shields.io/github/watchers/gwen001/s3-buckets-finder?style=social" alt="github watchers badge">
    <img src="https://img.shields.io/github/forks/gwen001/s3-buckets-finder?style=social" alt="github forks badge">
</p> -->

---

## Description

This PHP tool searches for AWS S3 buckets using a given wordlist. When an existing bucket is found, the tool checks the permissions of the bucket:
get ACL, put ACL, list, HTTP list, write

## Requirements

**Amazon S3:**  
```
apt-get install awscli
aws configure
```
**Google Cloud:**  
https://cloud.google.com/storage/docs/gsutil_install

## Install

```
git clone https://github.com/gwen001/s3-buckets-finder
```

## Usage

```
Usage: php s3-buckets-bruteforcer.php [OPTIONS] --bucket <bucket>

Options:
	--bucket	single bucket name or listing file
	--detect-region	Amazon only, try to automatically detect the region of the bucket
	--force-recurse	even if the bucket doesn't exist, the max-depth option will be applied (use this option at your own risk)
	--glue		characters used as a separator when concatenate all elements, default are: none, dash, dot and underscore
	-h, --help	print this help
	--list		do no perform any test, simply list the generated permutations
	--max-depth	max depth of recursion, if a bucket is found, another level will be added (permutations are applied), default=1, ex:
				if <bucket> is found then test <bucket>-xxx
				if <bucket>-xxx is found then test <bucket>-xxx-yyy
	--no-color	disable colored output
	--perform	tests to perform, default=esglw
				e: test if exist (always performed)
				s: set ACL
				g: get ACL
				l: list (cli and http)
				w: write
	--permut	permutation can be tested, default=0
				0: no permutation
				1: if both provided prefix and suffix are permuted (prefix.<bucket>.suffix, suffix.<bucket>.prefix)
				2: permutation applied only on the bucket name (a.b.c, b.c.a, ...)
				3: each elements will be separately permuted, then glogal permutation
	--prefix	single prefix or listing file
	--provider	can be: amazon, google, digitalocean
	--region	Amazon only, set the region (overwrite the option detect-region), value can be:
				us-east-1 us-east-2 us-west-1 us-west-2
				ap-south-1 ap-southeast-1 ap-southeast-2 ap-northeast-1 ap-northeast-2
				eu-central-1 eu-west-1 eu-west-2
				ca-central-1 sa-east-1
	--suffix	single suffix or listing file
	--thread	max threads, default=5
	-v,--verbosity	set verbosity, default=0
				0: everything
				1: do not display not found
				2: display only permissions success
				3: display only set ACL and write permission success

Examples:
	php s3-buckets-bruteforcer.php --bucket gwen001-test002
	php s3-buckets-bruteforcer.php --bucket listing.txt --no-color --verbosity 1
	php s3-buckets-bruteforcer.php --bucket listing1.txt --bucket listing2.txt --bucket listing3.txt --perform e --thread 10
	php s3-buckets-bruteforcer.php --bucket listing.txt --prefix prefix.txt --suffix suffix1.txt --suffix2.txt --perform esw --thread 10
	php s3-buckets-bruteforcer.php --bucket listing.txt --region us-east-2 --rlevel 3
```

---

<img src="https://raw.githubusercontent.com/gwen001/s3-buckets-finder/master/preview.png" />

---

Feel free to [open an issue](/../../issues/) if you have any problem with the script.  

