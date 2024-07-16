FROM webdevops/php-nginx:8.3

WORKDIR /var/www/html/amigor

ENV WEB_DOCUMENT_ROOT /var/www/html/amigor/public

COPY . /var/www/html

RUN composer install --no-interaction --no-dev

RUN chmod +x /var/www/html/amigor/docker/entryfile.sh
RUN chmod 755 -R /var/www/html/amigor/storage
RUN chown -R application:application /var/www/html/amigor/storage

RUN apt update
RUN apt install -y default-mysql-client

ENTRYPOINT ["/var/www/html/amigor/docker/entryfile.sh"]
