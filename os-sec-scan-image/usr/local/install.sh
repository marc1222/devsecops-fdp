#!/bin/sh
# Install JAVA if missing (for sonnar scanner)
arg_java=""
if ! which java >/dev/null; then
  arg_java=openjdk11-jre
fi
# Install required packages
apk --no-cache update  \
    && apk add --no-cache \
    npm nodejs tini ca-certificates unzip perl curl php7 php7-curl ncurses bash gettext $arg_java
# Install latest SNYK version & npm report tools
npm install -g snyk snyk-to-html json-pretty-html
# Install latest SonnarQube Scanner version
export SONAR_SCANNER_VERSION=$(wget -qO - "https://api.github.com/repos/SonarSource/sonar-scanner-cli/releases/latest" | grep '"tag_name":'| sed -E 's/.*"([^"]+)".*/\1/') \
    && wget --no-verbose https://binaries.sonarsource.com/Distribution/sonar-scanner-cli/sonar-scanner-cli-${SONAR_SCANNER_VERSION}.zip \
    && unzip sonar-scanner-cli-${SONAR_SCANNER_VERSION}.zip && mv sonar-scanner-${SONAR_SCANNER_VERSION} sonar-scanner
# Install latest Trivy version
export TRIVY_VERSION=$(wget -qO - "https://api.github.com/repos/aquasecurity/trivy/releases/latest" | grep '"tag_name":' | sed -E 's/.*"v([^"]+)".*/\1/') \
    && wget --no-verbose https://github.com/aquasecurity/trivy/releases/download/v${TRIVY_VERSION}/trivy_${TRIVY_VERSION}_Linux-64bit.tar.gz -O - | tar -zxvf -
# Install latest Dockle version
export DOCKLE_VERSION=$(wget -qO - https://api.github.com/repos/goodwithtech/dockle/releases/latest | grep '"tag_name":' | sed -E 's/.*"v([^"]+)".*/\1/') \
    && wget --no-verbose https://github.com/goodwithtech/dockle/releases/download/v${DOCKLE_VERSION}/dockle_${DOCKLE_VERSION}_Linux-64bit.tar.gz -O - | tar -zxvf -
# Clean compressed files
rm -f  /opt/*.tar.gz /opt/*.zip