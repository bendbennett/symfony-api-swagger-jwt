FROM busybox

COPY . /var/www/html

VOLUME /var/www/html

CMD [ "true" ]
