# To Run:
#   ./docker-run.sh
# or
#   docker build -t s3-bucket-bruteforcer .
#   docker run -it --rm --name s3-bucket-bruteforcer -v ~/Desktop/buckets.txt:/buckets.txt s3-bucket-bruteforcer

FROM php:7.0-cli

RUN docker-php-ext-install pcntl

COPY . /usr/src/s3-bucket-finder

WORKDIR /usr/src/s3-bucket-finder

ENTRYPOINT [ "php", "./s3-buckets-bruteforcer.php" ]

CMD [ "-h" ]
