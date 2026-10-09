# Note: addUserData() automatically adds shebang at the start of the file.

# amazon-efs-utils for mounting the eXist-db EFS file system, e.g.:
#   sudo mount -t efs -o tls,iam fs-XXXXXXXX:/ /mnt/existdb-data
amazon-linux-extras install -y epel
# psql client - AL2's default repos only carry an ancient 9.2 build and there's
# no postgresql16 package/topic available at all; postgresql14 is the newest
# extras topic AL2 offers. A 14 client against the Aurora 16 server works fine
# for ordinary queries/\copy - full version match isn't available without
# adding the external PGDG yum repo, which isn't worth it for this use.
amazon-linux-extras install -y postgresql14
yum install -y amazon-efs-utils jq htop tmux

# eXist-db client (bin/client.sh) for loading article XML content - same
# release as the running eXist-db service, for XML-RPC wire compatibility.
# Only the client tooling is used here; nothing from this tarball is ever
# started as a server on the bastion. EXISTDB_HOST is exported by the
# user-data line BastionStack adds before this script.
amazon-linux-extras install -y java-openjdk11
EXISTDB_VERSION=6.4.0
EXISTDB_CLIENT_HOME=/opt/existdb-client
curl -fsSL -o /tmp/existdb.tar.bz2 \
  "https://github.com/eXist-db/exist/releases/download/eXist-${EXISTDB_VERSION}/exist-distribution-${EXISTDB_VERSION}-unix.tar.bz2"
mkdir -p ${EXISTDB_CLIENT_HOME}
tar xjf /tmp/existdb.tar.bz2 -C ${EXISTDB_CLIENT_HOME} --strip-components=1
rm /tmp/existdb.tar.bz2
# Point the client at this environment's eXist-db service (Cloud Map service
# discovery, VPC-internal) instead of the distribution's localhost:8080 default.
sed -i "s/localhost:8080/${EXISTDB_HOST}:8086/g" ${EXISTDB_CLIENT_HOME}/etc/client.properties
ln -sf ${EXISTDB_CLIENT_HOME}/bin/client.sh /usr/local/bin/existdb-client
