// Script to get stats from reports
// Usage: node create_stats.js <OUT_FILENAME>
// Generates json file named <OUT_FILENAME>.json
var fs = require('fs');
var params = (process.argv.slice(2)); // first param on position 0
const util = require('util');
const exec = util.promisify(require('child_process').exec);

var dir = params[0];

fs.readdir(dir, (err, files) => {
    if (err) throw (err)
    files.forEach( (file) => {
        if (file.includes(".html")) {
            fs.readFile(dir+file, (err, text) => {
                console.log(file);
                if (err) throw (err)
                text = text.toString();
                var tableopen = text.split(/<table/).length;
                var tableclose = text.split(/<\/table/).length;
                if (tableopen > tableclose) {
					var regex = new RegExp("<table" + "(.*?)" + ">", 'g');
                    var matches = text.match(regex);
                    for (var i = 0; i < matches.length; ++i) {
						var match = matches[i];
                        if (!match.includes('id="severity-table"')) {
                            var start = text.indexOf(match) - 6;
                            var end = start + match.length + 7;
                            text = text.substr(0, start) + text.substr(end, text.length);
                        }
                    }
                    fs.writeFileSync(dir+file, text);
                }

                var divopen = text.split(/<div/).length;
                var divclose = text.split(/<\/div/).length;
                console.log(divopen - divclose);
                if (divopen > divclose) {
                    var divappend = "";
                    for (var i = divclose; i < divopen; ++i) {
                        divappend = divappend + "</div>";
                    }
                    divappend = divappend + "\n</body>";
                    var splittext = text.split(/<\/body>/);
                    if (splittext.length === 2) {
                        text = splittext[0] + divappend + splittext[1];
                        fs.writeFileSync(dir+file, text);
                    }
                }
            });
        }
    });
});
