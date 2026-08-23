FROM public.ecr.aws/ct-nilus/base-images:nginx-php-dev-v21-noble-8.3

## nchan holds the long-lived SSE connections that php-fpm cannot: every connection would occupy one
## worker, so a few dozen viewers would starve the pool and the uploads themselves would begin to queue.
## It is packaged for noble, so no module has to be built from source.
## Installed before the application is copied in, so a code change does not invalidate this layer.
## The grep fails the build here rather than at boot, where a missing module makes nginx refuse to start:
## sites-available/courseticket declares nchan directives and is meaningless without it.
RUN apt-get update -qq \
    && apt-get install -y --no-install-recommends libnginx-mod-nchan \
    && ls /etc/nginx/modules-enabled/ | grep -q nchan

ADD ./app_rest /var/www/cplatform/public/app_rest
ADD ./docker/sites/nginx-site-dev.conf /etc/nginx/sites-available/courseticket
ADD ./docker/sites/nchan-dev.conf /etc/nginx/sites-enabled/nchan.conf
RUN chown www-data:www-data -R /var/www/cplatform/public/app_rest/tmp
RUN chown www-data:www-data -R /var/www/cplatform/public/app_rest/logs

## Cleanup apt and lists
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
