FROM redis:latest

## For security settings uncomment, make the dir, copy conf, and also start with the conf, to use it
RUN mkdir -p /usr/local/etc/redis
COPY ./environment/redis/redis.conf /usr/local/etc/redis/redis.conf
COPY ./environment/redis/sentinel.conf /usr/local/etc/redis/sentinel.conf
RUN chown redis:redis /usr/local/etc/redis/*
#VOLUME ./environment/redis/data

CMD ["redis-server", "/usr/local/etc/redis/redis.conf"]
