#!/bin/sh
# Check JAVA (missing? - for sonnar scanner)
arg_java=""
if ! which java >/dev/null; then
  arg_java=openjdk8-jre
fi
echo $arg_java # Prints nothing if java is already installed
# Shifleft Image provides java and nodejs frameworks
microdnf --nodocs install -y perl curl php php-curl ncurses-devel gettext && microdnf clean all
# Install npm and pip tools
npm install -g json-pretty-html bower @appthreat/cdxgen
pip install appthreat-depscan
# Install latest SonnarQube Scanner version
export SONAR_SCANNER_VERSION=$(wget -qO - "https://api.github.com/repos/SonarSource/sonar-scanner-cli/releases/latest" | grep '"tag_name":'| sed -E 's/.*"([^"]+)".*/\1/') \
    && wget --no-verbose https://binaries.sonarsource.com/Distribution/sonar-scanner-cli/sonar-scanner-cli-${SONAR_SCANNER_VERSION}.zip \
    && unzip sonar-scanner-cli-${SONAR_SCANNER_VERSION}.zip && mv sonar-scanner-${SONAR_SCANNER_VERSION} sonar-scanner
# Install latest Trivy version
export TRIVY_VERSION=$(wget -qO - "https://api.github.com/repos/aquasecurity/trivy/releases/latest" | grep '"tag_name":' | sed -E 's/.*"v([^"]+)".*/\1/') \
    && wget --no-verbose https://github.com/aquasecurity/trivy/releases/download/v${TRIVY_VERSION}/trivy_${TRIVY_VERSION}_Linux-64bit.tar.gz -O - | tar -zxvf -
# Install latest FOSSA-cli version
curl -H 'Cache-Control: no-cache' https://raw.githubusercontent.com/fossas/fossa-cli/master/install.sh | sh
# Install Tini *dumb init*
wget --no-verbose https://github.com/krallin/tini/releases/download/v0.19.0/tini && mv tini /bin && chmod +x /bin/tini
# Clean compressed files
rm -f  /opt/*.tar.gz /opt/*.zip /opt/*.sh