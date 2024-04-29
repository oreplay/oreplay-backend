FROM public.ecr.aws/ct-nilus/base-images:nginx-php-dev-v18-focal-7.4
ADD ./app_rest /var/www/cplatform/public/app_rest
ADD ./docker/sites/nginx-site-dev.conf /etc/nginx/sites-available/courseticket
RUN chown www-data:www-data -R /var/www/cplatform/public/app_rest/tmp
RUN chown www-data:www-data -R /var/www/cplatform/public/app_rest/logs

## Cleanup apt and lists
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
