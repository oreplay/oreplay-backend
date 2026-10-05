#!/bin/sh
# Renders the nginx snippet that proxies the frontend's static files to FRONT_DOMAIN.
# It has to run before nginx starts: sites-available/courseticket includes the file it writes, and nginx
# refuses to start without it, which is deliberate. A container that silently proxied to another host than
# the one php reads from FRONT_DOMAIN would serve an index.html and a bundle from two different builds.
set -eu

origin="${FRONT_DOMAIN:-}"
origin="${origin%/}"
host="${origin#*://}"
case "$origin" in
    http://*/* | https://*/*)
        echo "FRONT_DOMAIN must not have a path: $origin" >&2
        exit 1
        ;;
    http://?* | https://?*) ;;
    *)
        echo "FRONT_DOMAIN must be set to a http(s) origin, got: '$origin'" >&2
        exit 1
        ;;
esac

sed -e "s|__FRONT_ORIGIN__|$origin|" -e "s|__FRONT_HOST__|$host|" \
    "${FRONT_PROXY_TEMPLATE:-/etc/nginx/snippets/front-proxy.conf.template}" \
    > "${FRONT_PROXY_CONF:-/etc/nginx/snippets/front-proxy.conf}"
