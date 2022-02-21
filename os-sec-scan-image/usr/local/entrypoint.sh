#!/bin/sh
# Add /opt/ folder to path
export PATH=$PATH:/opt/:/opt/sonar-scanner/bin
# Create Link to find global installed npm modules when execute node scripts
ln -s /usr/lib/node_modules/ /usr/local/reports/node_modules
# Switch context to a new shell
exec /bin/sh