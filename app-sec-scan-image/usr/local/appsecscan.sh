#!/bin/sh
# Run this script as GitLab Runner would! *consider permissions and $PWD and OPTIONAL argument SCAN_DIR*
# THUS, THIS SCRIPT MUST BE RUN FROM THE FOLDER CONTAINING THE PROJECT SOURCE CODE
Usage="appsecscan.sh <SCAN_TYPES> [<SCAN_DIR>]"
# SCAN_DIR: relative path to append to $PWD, $PWD=/git root repository folder path/

bold=$(tput -T screen bold)
normal=$(tput -T screen sgr0)
cyan=$(tput -T screen setaf 6)
white=$(tput -T screen setaf 7)
underlined=$(tput -T screen smul)
rm_underlined=$(tput -T screen rmul)
banner="-----------------------------------------------------------------------------"

script_dir="/usr/local/reports"
app_report="APPSecScanReport.html"
shiftleft_dir="report/shiftleft"
shiftleft_report="report/shiftleft_report.html"
bom_report="report/bom"
license_report="report/license"
depscan_report="report/depscan"
fossa_report="report/fossa_report"
trivy_report="report/trivy_report"

if [ $# -gt 2 ]; then
  echo ${Usage}
  exit 1
fi
SCAN_TYPES=$1
echo "${banner}"
if [ -z "$2" ]; then
  SCAN_DIR=${PWD}
  echo "SCAN_DIR: <unset> ... App security tools will be scanning git root folder path..."
else
  SCAN_DIR=${PWD}/$2
  echo "SCAN_DIR: <$2>... App security tools will be scanning requested path..."
  if [ ! -d "${SCAN_DIR}" ]; then
    echo -e "ERROR ... ${bold}${SCAN_DIR}${normal} does NOT exist or is NOT a folder\n"
    exit 1
  fi
fi
echo -e "Application scan path: ${bold}${SCAN_DIR}${normal}\n${banner}"
# Substitute and Load custom env variables
envsubst < /.env > /.env_subst
export $(grep -v '^#' /.env_subst | xargs)

# Clean previous execution report files
rm -rf report ${app_report} statsAPP.json
# Add /opt/ folder to path and gitlab access token for depscan
export PATH=${PATH}/opt/:/opt/sonar-scanner/bin
# Credscan - Depsscan config env vars
export SCAN_TYPES=${SCAN_TYPES}
#export GITHUB_TOKEN=${git_acces_token}
#export CREDSCAN_DEPTH=5000
#export NVD_START_YEAR=2010
# Create Links (find global installed npm modules when execute node scripts)
ln -s /usr/local/lib/node_modules/ ${script_dir}/node_modules &>/dev/null
# Create reports folders using relative path from current folder
mkdir -p ${shiftleft_dir}
# AutoCompile project if necessary & Scan App & Build report
scan --src ${SCAN_DIR} --type ${SCAN_TYPES} --out_dir ${PWD}/${shiftleft_dir} --build --no-error && echo "${banner}"
# Depscan trigger if no output generated, and report recolect & generate
if [ -z $(find ${PWD}/${shiftleft_dir} -type f -name "depscan*.json") ]; then
  depscan --no-banner --sync --suggest --src ${SCAN_DIR} --report_file ${shiftleft_dir}/depscan-rerun.json
  echo "${banner}"
fi
# Start & Run FOSSA
fossa init 2>/dev/null && fossa build 2>/dev/null && fossa analyze --output 2>/dev/null > ${fossa_report}.json
# Run Trivy
trivy --exit-code 0 --cache-dir .trivycache/ --quiet --no-progress --vuln-type library fs --input ${SCAN_DIR}
trivy --exit-code 0 --cache-dir .trivycache/ --no-progress --vuln-type library --format template --template "@contrib/html.tpl" -o report/trivy_report.html fs --input ${SCAN_DIR} > /dev/null
echo ${banner}
# Generate HTML reports
test -e "${fossa_report}.json" && node ${script_dir}/json_to_html.js "${PWD}/${fossa_report}" "FOSSA report - Library dependency tree" || echo "FOSSA dep list file - Not found"
mv -f $(find ${shiftleft_dir} -type f -name "bom*.json" | head -n 1) "${bom_report}.json" 2>/dev/null && node ${script_dir}/json_to_html.js "${PWD}/${bom_report}" "Dep-Scan Application Libraries" || echo "BOM library list file - Not found"
mv -f $(find ${shiftleft_dir} -type f -name "depscan*.json" | head -n 1) "${depscan_report}.json" 2>/dev/null && node ${script_dir}/json_to_html.js "${PWD}/${depscan_report}" "Dep-Scan Vulnerability" || echo "Dep-scan Vulnerability - Not found"
mv -f $(find ${shiftleft_dir} -type f -name "license*.json" | head -n 1) "${license_report}.json" 2>/dev/null && node ${script_dir}/json_to_html.js "${PWD}/${license_report}" "Dep-Scan License" || echo "Dep-scan License - Not found"
# Combine multiple HTML and add custom script
/usr/bin/perl ${script_dir}/htmlfix ${shiftleft_dir}/*.html 2>/dev/null
node ${script_dir}/fix_html.js "${PWD}/${shiftleft_dir}/" > /dev/null
/usr/bin/perl ${script_dir}/htmlcat -d -o ${shiftleft_report} ${shiftleft_dir}/*.html 2>/dev/null
/usr/bin/perl ${script_dir}/htmlfix report/*.html 2>/dev/null
/usr/bin/perl ${script_dir}/htmlcat -d -o ${app_report} ${shiftleft_report} "${depscan_report}.html" "${bom_report}.html" "${fossa_report}.html" "${license_report}.html" "${trivy_report}.html" 2>/dev/null
cat ${script_dir}/appsec_script >> ${app_report}
#Create APP scan stats
node /usr/local/reports/create_stats.js statsAPP > /dev/null
# Send report via php script to webapp
echo -e "${bold}${banner}\nBrowse APP security report:\n${banner}${cyan}${underlined}" && php -f ${script_dir}/send_script.php ${SCAN_TYPES} && echo -e "${rm_underlined}${white}${banner}\n${normal}"
