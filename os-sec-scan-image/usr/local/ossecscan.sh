#!/bin/bash
# Run this script as GitLab Runner would! *consider permissions and $PWD*
# to test this script wihtou pipeline:
# apk add iptables git
# dockerd --host=unix:///var/run/docker.sock --host=tcp://0.0.0.0:2375 --storage-driver=vfs
# git clone https://gitlab.domain.edu/so/logs
# docker build
Usage="ossecscan.sh <IMAGE>"

bold=$(tput -T screen bold)
normal=$(tput -T screen sgr0)
cyan=$(tput -T screen setaf 6)
white=$(tput -T screen setaf 7)
underlined=$(tput -T screen smul)
rm_underlined=$(tput -T screen rmul)
banner="-----------------------------------------------------------------------------"

if [ $# -ne 1 ]; then
  echo $Usage;  exit 1;
fi
IMAGE=$1
if [ $(docker images -q ${IMAGE} | wc -l) -eq 0 ]; then
  echo -e "Image: [${IMAGE}] not found in Docker daemon... exiting...\n${banner}"; exit 1;
else
  echo -e "Found already built docker image [${IMAGE}]... Starting image security scan...\n${banner}"
fi
export IMAGE=${IMAGE}

# Substitute and Load custom env variables
envsubst < /.env > /.env_subst
export $(grep -v '^#' /.env_subst | xargs)

os_report="OSSecScanReport.html"
# Add /opt/ folder to path
export PATH=$PATH:/opt/:/opt/sonar-scanner/bin
# Create Links (1.find global installed npm modules when execute node scripts | 2. find templates required by trivy)
ln -s /usr/lib/node_modules/ /usr/local/reports/node_modules
ln -s /opt/contrib contrib
# Switch context to a new shell
# Create artifacts folders with relative path from current folder
mkdir image report
# Download Trivy DB for CI
trivy --cache-dir .trivycache/ --quiet image --download-db-only --no-progress --clear-cache
# Run Trivy
trivy --exit-code 0 --cache-dir .trivycache/ --no-progress --severity CRITICAL,HIGH,MEDIUM --format json -o report/trivy_report.json ${IMAGE} > /dev/null
trivy --exit-code 0 --cache-dir .trivycache/ --no-progress --severity CRITICAL,HIGH,MEDIUM --format template --template "@contrib/html.tpl" -o report/trivy_report.html ${IMAGE} > /dev/null
echo -e "         ${bold}TRIVY${normal} --- ${bold}Vulnerabilities with available/distributed patch${normal}\n${banner}";
trivy --exit-code 0 --cache-dir .trivycache/ --quiet --ignore-unfixed --no-progress --severity CRITICAL,HIGH,MEDIUM,LOW,UNKNOWN ${IMAGE}
echo ${banner}
# Store snyk token in array
IFS=':' read -r -a snyk_token <<< $SNYK_ACC
# Run SNYK
DOCKERFILE_PATH=$(find . -type f -name Dockerfile | head -n 1)
snyk_auth=0
for token in ${snyk_token[@]}; do
  if snyk auth ${token} | grep "Your account has been authenticated" > /dev/null; then
    snyk_auth=1;  break 1;
  fi
done
[ "$snyk_auth" -eq 0 ] && echo "SNYK IS NOT AUTHENTICATED" || snyk container test ${IMAGE} --file=${DOCKERFILE_PATH} --json > report/snyk_report.json
snyk-to-html -i report/snyk_report.json -o report/snyk_report.html -a -s -t /usr/lib/node_modules/snyk-to-html/template/test-cve-report.hbs > /dev/null
# Run Dockle
dockle --exit-code 0 -f json --output report/dockle_report.json ${IMAGE}
node /usr/local/reports/json_to_html.js ${PWD}/report/dockle_report "Dockle report - Security linter audit"
# Combine multiple html to one
/usr/bin/perl /usr/local/reports/htmlcat -d -o ${os_report} report/trivy_report.html report/snyk_report.html report/dockle_report.html
sed -e "s/onclick=\"loadTab(event, 'vulnDetails')\"//g" -i ${os_report}
sed -e "s/onclick=\"loadTab(event, 'cveSummary')\"//g" -i ${os_report}
cat /usr/local/reports/ossec_script >> ${os_report}
#Create OS scan stats
node /usr/local/reports/create_stats.js statsOS > /dev/null
# Send report via php script to webapp
echo -e "\n${bold}Browse OS security report:\n${banner}${cyan}${underlined}" && php -f /usr/local/reports/send_script.php ${IMAGE} && echo -e "${rm_underlined}${white}${banner}\n${normal}"
