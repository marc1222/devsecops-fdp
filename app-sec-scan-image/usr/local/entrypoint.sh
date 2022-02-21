#!/bin/sh
# Add /opt/ folder to path
export PATH=$PATH:/opt/:/opt/sonar-scanner/bin
# Start FOSSA
fossa init
# Switch context to a new shell
exec /bin/sh