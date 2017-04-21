#!/usr/bin/env bash

TAG="s3-bucket-bruteforcer"
NAME="s3-bucket-bruteforcer"

docker build --tag "$TAG" . \
&& exec docker run --name "$NAME" --rm -it "$TAG" -v ~/Desktop/buckets.txt:/buckets.txt $@
