const fs = require('fs');
const yaml = require('js-yaml');

const name = 'v1api'

// Read the JSON file as UTF-8
const json = JSON.parse(fs.readFileSync(name + '.json', 'utf8'));

// Convert to YAML
const yamlContent = yaml.dump(json, {
    lineWidth: -1,
    noCompatMode: true,
    quotingType: "'",
    forceQuotes: false
});

// Write YAML as UTF-8
fs.writeFileSync(name + '.yaml', yamlContent, 'utf8');
// delete json file
// fs.unlinkSync(name + '.json');
