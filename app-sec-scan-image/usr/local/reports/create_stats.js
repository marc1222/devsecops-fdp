// Script to get stats from reports
// Usage: node create_stats.js <OUT_FILENAME>
// Generates json file named <OUT_FILENAME>.json
var fs = require('fs');
var params = (process.argv.slice(2)); // first param on position 0
const util = require('util');
const exec = util.promisify(require('child_process').exec);

var output = params[0]+'.json';

var reports_base_dir = process.env.UPLOAD_DIR + "/report/";
console.log("base report dir: "+reports_base_dir);

// var trivy_stat = {
//     target: "debian",
//     total: -1,
//     critical: -1,
//     high: -1,
//     medium: -1,
//     unpatched: -1
// };

// var shiftleft_stat = {
//     total: 0,
//     critical: 0,
//     high: 0,
//     medium: 0,
//     low: 0,
//     tool: 'Java Source Analyzer'
// };

var metadata = {
    date: new Date().toUTCString(),
    project: (process.env.PROJECT_NAME || null),
    commit: (process.env.COMMIT || null),
    branch: (process.env.BRANCH || null)
};

var os_json_model = {
    type: "OS",
    trivy: [], //array of trivy_stat json obj
    dockle: {
        "fatal": 1,
        "warn": -1,
        "info": -1,
        "pass": 11
    },
    snyk: {
        "vul_paths": -1,
        "total": -1,
    }
};

var app_json_model = {
    type: "APP",
    shiftleft: [], //array of shiftleft_stat json obj
    credscan: {
        "secrets": -1,
    },
    depscan: {
        "critical": -1,
        "high": -1,
        "medium": -1,
        "low": -1
    },
    components: {
        "fossa_imports": -1,
        "bom": -1
    },
    trivy: [] //array of trivy_stat json obj
};

var reports_path = {
    trivy: reports_base_dir+'trivy_report.json',
    snyk: reports_base_dir+'snyk_report.json',
    dockle: reports_base_dir+'dockle_report.json',
    depscan: reports_base_dir+'depscan.json',
    fossa: reports_base_dir+'fossa_report.json',
    bom: reports_base_dir+'bom.json',
    shiftleft: reports_base_dir+'shiftleft/scan-full-report.json',
    credscan: reports_base_dir+'shiftleft/credscan-report.sarif',
}

async function command_exec(command) {
    try {
        const { stdout, stderr } = await exec(command);
        if (stderr) console.log(stderr);
        return stdout.toString('utf8');
    } catch (err) {
        console.log(err);
    }
}

if ( output.includes('OS') ) {
    var json = os_json_model;

    if (fs.existsSync(reports_path.trivy)) { //trivy report
        fs.readFile(reports_path.trivy, function (err, text) {
            if (err) throw (err)
            var json_report = JSON.parse(text);
            json_report.forEach( (trivy) => {
                if (trivy["Vulnerabilities"] !== null) {
                    var vulns = JSON.stringify(trivy["Vulnerabilities"]).toString();
                    var trivy_stat = {
                        total: (vulns.match(/,"Severity":/g) || []).length,
                        critical: (vulns.match(/,"Severity":"CRITICAL"/g) || []).length,
                        high: (vulns.match(/,"Severity":"HIGH"/g) || []).length,
                        medium: (vulns.match(/,"Severity":"MEDIUM"/g) || []).length,
                        unpatched: (vulns.match(/,"FixedVersion"/g) || []).length
                    };
                } else var trivy_stat = {
                                            total: 0,
                                            critical: 0,
                                            high: 0,
                                            medium: 0,
                                            unpatched: 0
                                        };
                trivy_stat.target = trivy["Type"];
                json["trivy"].push(trivy_stat);
            });
            //console.log(json["trivy"]); TESTED
        });
    } else json.trivy = null;

    if (fs.existsSync(reports_path.dockle)) { //dockle report
        fs.readFile(reports_path.dockle, function (err, text) {
            if (err) throw (err)
            var json_report = JSON.parse(text);
            json["dockle"] = json_report["summary"];
            //console.log(json["dockle"]); TESTED
        });
    } else json.dockle = null;

    if (fs.existsSync(reports_path.snyk)) { //snyk report
        command_exec('cat '+reports_path.snyk+' | grep \'"summary":\'')
            .then( (result) => {
                var secretNum = result.match(/^\d+|\d+\b|\d+(?=\w)/g)[0];
                json["snyk"]["vul_paths"] = parseInt(secretNum);
                //console.log(json["snyk"]["vul_paths"]); TESTED
            });
        command_exec('cat '+reports_base_dir+'snyk_report.json | grep \'"uniqueCount":\'')
            .then( (result) => {
                var secretNum = result.match(/^\d+|\d+\b|\d+(?=\w)/g)[0];
                json["snyk"]["total"] = parseInt(secretNum);
                //console.log(json["snyk"]["total"]); TESTED
            });
    } else json.snyk = null;

} else if ( output.includes('APP') ) {
    var json = app_json_model;

    if (fs.existsSync(reports_path.bom)) { //library report part1
        command_exec('cat '+reports_path.bom+' | grep \'"group":\' | wc -l')
            .then( (result) => {
                json["components"]["bom"] = parseInt(result);
                //console.log(json["components"]["bom"]); TESTED
            });
    } else json.components.bom = null;

    if (fs.existsSync(reports_path.fossa)) { //library report part2
        command_exec('cat '+reports_path.fossa+' | grep -o \'"locator":\' | wc -l')
            .then( (result) => {
                json["components"]["fossa_imports"] = parseInt(result);
                //console.log(json["components"]["fossa_imports"]); TESTED
            });
    } else json.components.fossa_imports = null;

    if (fs.existsSync(reports_path.depscan)) { //dependency report
        command_exec('cat '+reports_path.depscan+' | grep \'"CRITICAL"\' | wc -l')
            .then( (result) => {
                json["depscan"]["critical"] = parseInt(result);
                //console.log(json["depscan"]["critical"]); TESTED
            });
        command_exec('cat '+reports_path.depscan+' | grep \'"HIGH"\' | wc -l')
            .then( (result) => {
                json["depscan"]["high"] = parseInt(result);
                //console.log(json["depscan"]["high"]); TESTED
            });
        command_exec('cat '+reports_path.depscan+' | grep \'"MEDIUM"\' | wc -l')
            .then( (result) => {
                json["depscan"]["medium"] = parseInt(result);
                //console.log(json["depscan"]["medium"]); TESTED
            });
        command_exec('cat '+reports_path.depscan+' | grep \'"LOW"\' | wc -l')
            .then( (result) => {
                json["depscan"]["low"] = parseInt(result);
                //console.log(json["depscan"]["low"]); TESTED
            });
        command_exec('cat '+reports_path.depscan+' | grep \'"severity":\' | wc -l')
            .then( (result) => {
                json["depscan"]["total"] = parseInt(result);
                //console.log(json["depscan"]["total"]); TESTED
            });
    } else json.depscan = null;

    if (fs.existsSync(reports_path.credscan)) { //credscan report
        command_exec('cat '+reports_path.credscan+' | grep \'"total":\'')
            .then( (result) => {
                var secretNum = result.match(/^\d+|\d+\b|\d+(?=\w)/g)[0];
                json["credscan"]["secrets"] = parseInt(secretNum);
                //console.log(json["credscan"]["secrets"]); TESTED

            });
    } else json.credscan = null;

    if (fs.existsSync(reports_path.shiftleft)) { //shiftleft full report json
        fs.readFile(reports_path.shiftleft, function (err, text) {
            if (err) throw (err)
            var lines = text.toString().split(/\n/);
            if (lines.length > 1) {
                text = '[' + text;
                text = text.replace(/\n/g, ',');
                text = text.slice(0, -1) + ']';
            }
            var json_report = JSON.parse(text);
            var used_tools = [];
            json_report.forEach( (tool) => {
                var toolName = tool["tool"]["driver"]["name"].toString();
                if (toolName !== "Secrets Audit" && !used_tools.includes(toolName) ) { //avoid secrets and duplicates
                    used_tools.push(toolName);
                    var shiftleft_tool = tool["properties"]["metrics"];
                    shiftleft_tool["tool"] = toolName;
                    json["shiftleft"].push(shiftleft_tool);
                }
            });
            //console.log(json["shiftleft"]); //TESTED
        });
    } else json.shiftleft = null;

    if (fs.existsSync(reports_path.trivy)) { //trivy report
        fs.readFile(reports_path.trivy, function (err, text) {
            if (err) throw (err)
            var json_report = JSON.parse(text);
            json_report.forEach( (trivy) => {
                if (trivy["Vulnerabilities"] !== null) {
                    var vulns = JSON.stringify(trivy["Vulnerabilities"]).toString();
                    var trivy_stat = {
                        total: (vulns.match(/,"Severity":/g) || []).length,
                        critical: (vulns.match(/,"Severity":"CRITICAL"/g) || []).length,
                        high: (vulns.match(/,"Severity":"HIGH"/g) || []).length,
                        medium: (vulns.match(/,"Severity":"MEDIUM"/g) || []).length,
                        unpatched: (vulns.match(/,"FixedVersion"/g) || []).length
                    };
                } else var trivy_stat = {
                    total: 0,
                    critical: 0,
                    high: 0,
                    medium: 0,
                    unpatched: 0
                };
                trivy_stat.target = trivy["Type"];
                json["trivy"].push(trivy_stat);
            });
            //console.log(json["trivy"]); TESTED
        });
    } else json.trivy = null;

} else {
    print("Script could not get anay");
    process.exit(1);
}

process.on('exit', function(code) {
    if (code !== 1) {
        json.metadata = metadata;
        fs.writeFileSync(output, JSON.stringify(json));
    }
    return console.log(`Script exited with code: ${code}`);
});


