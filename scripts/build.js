/**
 * Build scripts
 */

const fs = require('fs');
const path = require('path');

const cleanCSS = require('clean-css');
const del = require('del');
const utils = require('loader-utils');
const rjs = require('requirejs');
const uglify = require('uglify-js');

const config = {
	base: path.join(__dirname, '..', 'www'),
	dist: path.join(__dirname, '..', 'www', 'dist'),
};

function clearFiles(files) {
	del.sync(files);
}

function processScript(filePath) {
	rjs.optimize({
		baseUrl: filePath,
		optimize: 'none',
		name: 'app',
		out: path.join(config.dist, 'app.js'),
	}, () => {
		var script = fs.readFileSync(path.join(config.dist, 'app.js'), 'utf8');

		// Mangle "define" name
		script = script.replace(
			/define =|define;|define.amd|define\(/g,
			function(match, cls) {
				return match.replace('define', '__d');
			}
		);

		script = uglify.minify(script, {fromString: true}).code.replace(
			/}\),/g,
			function(match) {
				return match.replace('}),', '});\n');
			}
		);

		writeFile(path.join(config.dist, 'app.js'), script);
	});
}

function processStyle(filePath) {
	var style = fs.readFileSync(filePath, 'utf8');
	var selectorMap = {};

	style = style.replace(
		// Regex from the facebook/draft-js library
		/\/\*.*?\*\/|'(?:\\.|[^'])*'|"(?:\\.|[^"])*"|url\([^)]*\)|(\.(?:public\/)?[\w-]*\/{1,2}[\w-]+)/g,
		function(match, cls) {
			if (cls) {
				var selector = cls.substr(1);

				if (selector.indexOf('public') !== -1) {
					selectorMap[selector] = selector.substr('public'.length + 1);
				} else {
					selectorMap[selector] = '_' + utils.getHashDigest(selector, 'sha1', 'base64', 5);
				}

				return '.' + selectorMap[selector];
			} else {
				return match;
			}
		}
	);

	// new CleanCSS().minify(source).styles;

	writeFile(path.join(config.dist, 'style.css'), style);
	writeFile(path.join(config.dist, 'css-map.json'), JSON.stringify(selectorMap));
}

function writeFile(filePath, content) {
	fs.writeFileSync(filePath, content, 'utf8');
}

console.log('Cleaning previous build files...');
clearFiles([path.join(config.base, 'dist', '*')]);

console.log('Processing styles...');
processStyle(path.join(config.base, 'assets', 'styles', 'style.css'));

console.log('Processing scripts...');
processScript(path.join(config.base, 'assets', 'scripts'));
