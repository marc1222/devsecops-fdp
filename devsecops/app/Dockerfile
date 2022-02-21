FROM php:8-apache

ENV TZ          "Europe/Brussels"
ENV MANTAINER   "Marc Guinovart"
ENV BASE_DIR    "/var/www"
ENV USER        "www-data"

WORKDIR ${BASE_DIR}
# Install necessary packages
RUN apt-get update && apt-get install -y wget
#export CAS_VERSION=$(wget -qO - "https://api.github.com/repos/apereo/phpCAS//releases/latest" | grep '"tag_name":'| sed -E 's/.*"([^"]+)".*/\1/') && \
RUN export CAS_VERSION=1.3.8 && wget --no-verbose  https://github.com/apereo/phpCAS/releases/download/${CAS_VERSION}/CAS-${CAS_VERSION}.tgz -O - | tar -zxvf - && mv CAS-${CAS_VERSION} CAS
# Add application code
COPY . /
RUN chown -R ${USER}:${USER} ${BASE_DIR}/html
# Expose port
EXPOSE 80/tcp
