# eXist-db 6.4.0 on Java 11 (works in production despite eXist's docs saying
# Java 8 is required). Built from the official unix distribution tarball rather
# than the interactive installer jar, since that's non-interactive and
# reproducible - see the repo's build requirements notes for where these
# settings came from.
FROM eclipse-temurin:11-jre-jammy

ARG EXISTDB_VERSION=6.4.0
ENV EXIST_HOME=/opt/exist-db \
    EXIST_DATA_DIR=/exist/data \
    EXIST_PORT=8086 \
    JAVA_OPTS="-Djetty.http.port=8086 -Xmx4g"

RUN apt-get update \
    && apt-get install -y --no-install-recommends curl ca-certificates bzip2 \
    && rm -rf /var/lib/apt/lists/*

# Match the EFS access point's POSIX uid/gid (see infra/lib/efs-stack.ts) so the
# mounted /exist/data volume is actually writable by this user in ECS.
RUN groupadd -g 1000 existdb \
    && useradd -u 1000 -g existdb -d ${EXIST_HOME} -s /usr/sbin/nologin -M existdb

RUN curl -fsSL -o /tmp/existdb.tar.bz2 \
      "https://github.com/eXist-db/exist/releases/download/eXist-${EXISTDB_VERSION}/exist-distribution-${EXISTDB_VERSION}-unix.tar.bz2" \
    && mkdir -p ${EXIST_HOME} \
    && tar xjf /tmp/existdb.tar.bz2 -C ${EXIST_HOME} --strip-components=1 \
    && rm /tmp/existdb.tar.bz2 \
    && chown -R existdb:existdb ${EXIST_HOME}

# The real, tuned config (cacheSize, collectionCache, pool sizing, no query
# timeout, etc.) from the legacy production install - kept byte-identical here
# (diff this against the copy this was sourced from - it should be empty).
COPY conf.xml ${EXIST_HOME}/etc/conf.xml.orig

# Two required path/port fixes, applied at build time rather than by hand-editing
# the file above, so it stays diffable against the original:
#  1. The legacy host's absolute data path (/tietokannat/exist-6.4.0/data)
#     appears twice - <db-connection files="..."> AND <recovery journal-dir="...">
#     (configureRecovery validates journal-dir *before* configureBackend checks
#     files, so missing either one fails startup). conf.xml's own header comment
#     claims files= is overridable via -Dorg.exist.db-connection.files, but
#     that's empirically false (verified: eXist-db 6.4.0 still tried to read the
#     literal old path and failed to start) - so both occurrences are rewritten
#     directly, globally, rather than attribute-by-attribute.
#  2. eXist-db's XML-RPC client defaults to port 8080 (etc/client.properties) -
#     point it at our real port so bin/client.sh (used by the entrypoint for
#     first-boot admin password setup) works without extra flags.
RUN sed "s#/tietokannat/exist-6.4.0/data#${EXIST_DATA_DIR}#g" \
      ${EXIST_HOME}/etc/conf.xml.orig > ${EXIST_HOME}/etc/conf.xml \
    && rm ${EXIST_HOME}/etc/conf.xml.orig \
    && chown existdb:existdb ${EXIST_HOME}/etc/conf.xml \
    && sed -i "s/localhost:8080/localhost:8086/g" ${EXIST_HOME}/etc/client.properties

COPY --chown=existdb:existdb docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# /exist/data is normally an EFS mount in ECS (see infra/lib/efs-stack.ts) - pre-
# create the parent so it also works standalone (e.g. local testing, or if the
# volume isn't mounted yet on first start).
RUN mkdir -p /exist && chown existdb:existdb /exist

USER existdb
WORKDIR ${EXIST_HOME}
EXPOSE 8086

HEALTHCHECK --interval=30s --timeout=5s --start-period=90s --retries=5 \
  CMD curl -fsS "http://localhost:${EXIST_PORT}/exist/status" || exit 1

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["bin/startup.sh"]
