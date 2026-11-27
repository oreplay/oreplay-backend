const fs = require('fs');
const yaml = require('js-yaml');

const name = 'v1api'
const SPEC_SERVER_URL = 'https://127.0.0.1'

// Read the JSON file as UTF-8
const json = JSON.parse(fs.readFileSync(name + '.json', 'utf8'));

json.servers = [{ url: SPEC_SERVER_URL }];

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
