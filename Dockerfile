FROM debian:buster

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update && \
  apt-get -yqq install apt-transport-https ca-certificates \
  vim unzip \
  wget curl git ssh 

RUN \
    wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
    echo "deb https://packages.sury.org/php/ buster main" > /etc/apt/sources.list.d/php.list && \
    apt-get -qq update && apt-get -qqy upgrade

RUN apt-get install -y \
    php8.1-cgi php8.1-cli php8.1-gd php8.1-common php8.1-intl\
    php8.1-mbstring php8.1-mysql php8.1-opcache php8.1-readline php8.1-xml php8.1-xsl \
    php8.1-zip php8.1-mysql php8.1-sqlite3 php8.1-curl php8.1-mcrypt php8.1-dev php8.1-imagick \
    apache2 libapache2-mod-php8.1 default-mysql-client default-mysql-server php-pear imagemagick

# Install composer.
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer self-update --2

RUN apt-get -yqq install msmtp

#COPY msmtprc /etc/msmtprc
COPY php.ini /etc/php/8.1/cli/php.ini
COPY php.ini /etc/php/8.1/apache2/php.ini

COPY site.conf /etc/apache2/sites-enabled/000-default.conf

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

#RUN mkdir -p /var/www/project && a2enmod vhost_alias ssl rewrite headers 
RUN mkdir -p /var/www/html && mkdir -p /mnt/files

#aby apache log sel na stdout dockeru
RUN ln -sf /proc/self/fd/1 /var/log/apache2/access.log && \
    ln -sf /proc/self/fd/1 /var/log/apache2/error.log

RUN sed -i 's%  <policy domain="coder" rights="none" pattern="PDF" />%  <policy domain="coder" rights="read|write" pattern="PDF" />%g' /etc/ImageMagick-6/policy.xml
    
USER root

RUN groupadd -g 50011 apache && \
    useradd -u 50011 -m -s /bin/bash -g apache apache && \
    adduser apache root && \
    adduser apache www-data && \
    adduser www-data apache

RUN chown www-data:www-data -R /var/www
RUN chown www-data:www-data -R /etc/apache2/sites-enabled/000-default.conf
RUN chmod -R 777 /var/www
RUN chmod -R 777 /etc/apache2/sites-enabled/000-default.conf
RUN usermod -d /var/www www-data
RUN ls -laR /var/www

WORKDIR /var/www/html

COPY . /var/www/html

RUN /bin/su apache && \
    composer install && \
    rm -rf /var/www/html/web/sites/default/files && \
    ln -s /mnt/files/public /var/www/html/web/sites/default/files && \
    chown -R apache:apache /mnt/files && \
    chmod -R 777 /mnt && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 777 /var/www

EXPOSE 80 443

CMD /usr/sbin/apache2ctl -D FOREGROUND