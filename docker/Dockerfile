FROM phpmentors/php-app:php72

RUN echo 'debconf debconf/frontend select Noninteractive' | debconf-set-selections
RUN apt-get update -y
RUN apt-get upgrade -y

# Other tools
RUN apt-get install -y less unzip
