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

## The base image is a dev one and ships xdebug enabled: the ping endpoint reported mode=develop on
## production, which the original profiling measured at 1.9x on a whole request. Turned off for php-fpm,
## measured here at 0.58 s against 0.78 s on the same workload.
## Off rather than uninstalled, because the pool runs with clear_env = no: a development container turns it
## back on with XDEBUG_MODE in its environment, and one image serves both. The cli is untouched, since that
## is where phpunit would ask for coverage.
RUN echo 'xdebug.mode=off' > /etc/php/8.3/fpm/conf.d/99-xdebug-off.ini

ADD ./app_rest /var/www/cplatform/public/app_rest
ADD ./docker/sites/nginx-site-dev.conf /etc/nginx/sites-available/courseticket
ADD ./docker/sites/nchan-dev.conf /etc/nginx/sites-enabled/nchan.conf
RUN chown www-data:www-data -R /var/www/cplatform/public/app_rest/tmp
RUN chown www-data:www-data -R /var/www/cplatform/public/app_rest/logs

## Cleanup apt and lists
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
