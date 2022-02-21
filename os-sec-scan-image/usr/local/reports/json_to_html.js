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
     var json = JSON.parse(text);
     var html = jsonToHtml(json, json.dimensions);
     html = html.replace(/\[/g,'');
     html = html.replace(/]/g,'');
     html = html.replace(/\{/g,'');
     html = html.replace(/}/g,'');
     html = html.replace(/,/g,'');
     html = '<!DOCTYPE html>\n<html lang="en">\n<body>\n' +
            '<div class="json-pretty"><h1 style="margin-top: 35px;">'+params[1]+'</h1></div>\n' +
             html + '\n</body>\n</html>';
     fs.writeFileSync(params[0]+".html", html);
});
