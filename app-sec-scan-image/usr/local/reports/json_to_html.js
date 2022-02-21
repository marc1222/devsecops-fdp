// Script to convert desired json file to basic html
// Usage: node json2htm <file_to_parse> *(without extension and WITH full path)*

// var json2html = require('json2html');
//Usage: node json_to_html.js <jsonfile_withoutextension> "H1 Title for html doc"
//Dockle: Dockle security lint audit
var fs = require('fs');
var params = (process.argv.slice(2)); // first param on position 0
var jsonToHtml = require("json-pretty-html").default

fs.readFile(params[0]+".json", function (err, text) {
     if (err) throw (err)
     // BUILD PARSEABLE JSON STRINGS
     if ( params[1].includes('Dep-Scan Vuln') || params[1].includes('Dep-Scan Lic') ) { //Dep-scan vulnerability
          var lines = text.toString().split(/\n/);
          if (lines.length > 1) {
               text = '[' + text;
               text = text.replace(/\n/g, ',');
               text = text.slice(0, -1) + ']';
          }
     }
     var json = JSON.parse(text);
     // CUSTOMIZE JSON OR BUILD depscan table
     if ( params[1].includes('Dep-Scan Vuln') ) { //Dep-scan vulnerability
          var table =  '<h4 style="font-size: 150%; margin-top: 0.5em;">Application Dep-Scan found <b>'+String(json.length)+'</b> vulnerabilities </h4>'+
                       '<details><summary>Show dependencies vulnerability table</summary>' +
                         '<table id="depscantable" style="text-align: center;">'+
                         '<tbody>'+
                              '<tr class="sub-header">'+
                                   '<th>Package</th>'+ //package
                                   '<th>Vulnerability ID</th>'+ //id /> cveXXXX
                                   '<th>Severity</th>'+ //severity
                                   '<th>CVSS</th>'+ //cvss_score
                                   '<th>Vuln Version</th>'+ //version
                                   '<th>Fixed Version</th>'+ //fix_version
                                   '<th>Description</th>'+ //related_urls
                                   '<th>Links</th>'+ //related_urls
                              '</tr>';
          json.sort((a, b) => {
               let cvss_a = parseInt(a['cvss_score']);
               if(isNaN(cvss_a)) cvss_a = 0;
               let cvss_b = parseInt(b['cvss_score']);
               if (isNaN(cvss_b)) cvss_b = 0;
               if (cvss_a > cvss_b) return -1;
               else if (cvss_a < cvss_b) return 1;
               return 0;
          });
          for(var i = 0; i < json.length; i++) {
               var vuln = json[i];
               var urls = '<a>' + vuln["related_urls"].toString().replace(/,/g, '</a><a>') + '</a>';

               table = table + '<tr class="severity-'+String(vuln["severity"])+'">'+ //HIGH&CRITICAL&MEDIUM&LOW
                                  '<td class="pkg-name">'+String(vuln["package"])+'</td>'+
                                  '<td>'+String(vuln["id"])+'</td>'+
                                  '<td class="severity">'+String(vuln["severity"])+'</td>'+
                                  '<td>'+String(vuln["cvss_score"])+'</td>'+
                                  '<td class="pkg-version">'+(String(vuln["version"]).replace(/</g,'lt')).replace(/>/g,'gt')+'</td>'+
                                  '<td class="pkg-name">'+String(vuln["fix_version"])+'</td>'+
                                  '<td class="links2" data-more="'+String(vuln["short_description"]).replace(/"/g, '')+'">'+String(vuln["short_description"])+'</td>'+
                                  '<td class="links" data-more-links="off">'+String(urls)+'</td>'+
                               '</tr>';
          }
          table = table + '</tbody></table></details>';
          table = table.replace(/<style>/g,'>style<');

     } else if ( params[1].includes('Dep-Scan Application') ) { //Bom depscan report
          ['metadata', 'bomFormat', 'specVersion', 'serialNumber', 'version'].forEach( e => delete json[e]);
          json['components'].forEach(obj => {
               var license = (obj.hasOwnProperty('licenses') && Array.isArray(obj['licenses']) && typeof obj['licenses'][0] !== "undefined" && obj['licenses'][0].hasOwnProperty('license')) ? obj['licenses'][0]['license']['url'] : "";
               ['group', 'version', 'type', 'bom-ref', 'externalReferences', 'licenses'].forEach( e => delete obj[e]);
               obj['license'] = (license !== undefined) ? license : "";
          });
     } else if ( params[1].includes('FOSSA') ) { //Fossa scan report
          json.forEach(obj => {
              ['Artifact','Context'].forEach(e => delete obj['Build'][e]);
              if ( Array.isArray(obj['Build']['Dependencies']) ) {
                   var dependencies = [];
                   obj['Build']['Dependencies'].forEach( (val, index) => {
                        dependencies[index] = val.locator;
                   });
                   obj['Build']['Dependencies'] =  dependencies;
              }
          });
     }
     // BUILD HTML
     if ( params[1].includes('Dep-Scan Vuln') ) { //Dep-scan vulnerability (details already included)
          var html = '<!DOCTYPE html>\n<html lang="en">\n' +
                        '<body>\n' +
                              '<div style="padding-left: 0.4rem;"><h1 style="margin-top: 35px;">'+params[1]+'</h1></div>\n' +
                              table + '\n' +
                        '</body>\n' +
                     '</html>';
     } else {
          var html = jsonToHtml(json, json.dimensions);
          html = html.replace(/\[/g,'');
          html = html.replace(/]/g,'');
          html = html.replace(/\{/g,'');
          html = html.replace(/}/g,'');
          html = html.replace(/,/g,'');
          html = html.replace(/"/g,'');

          if ( params[1].includes('FOSSA') ) { //Bom depscan report
               var details = '<details><summary>Show FOSSA detected components</summary>\n';
          } else if ( params[1].includes('Dep-Scan Application') ) { //Bom depscan report
               var details = '<details><summary>Show BOM report <small>(bill of materials list)</small></summary>\n';
          } else if ( params[1].includes('Dep-Scan Lic') ) { //Dep-Scan license report
               var details = '<details><summary>Show used licenses</summary>\n';
          } else {
               var details = '<details><summary>>Show full list</summary>\n';
          }
          html = '<!DOCTYPE html>\n<html lang="en">\n' +
                    '<body>\n' +
                         '<div class="json-pretty"><h1 style="margin-top: 35px;">'+params[1]+'</h1></div>\n' +
                         details + html + '\n</details>\n' +
                    '</body>\n' +
                  '</html>';
     }
     fs.writeFileSync(params[0]+".html", html);
});
