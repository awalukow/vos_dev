const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const test = require('node:test');
const path = require('node:path');

const template = fs.readFileSync(path.join(__dirname, '../resources/views/tickets/_social-buttons.blade.php'), 'utf8');
const script = template.match(/<script>([\s\S]*?)<\/script>/)[1];

for (const [name, navigator, visible] of [
    ['iPhone', { userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X)', platform: 'iPhone' }, true],
    ['iPad', { userAgent: 'Mozilla/5.0 (iPad; CPU OS 18_0 like Mac OS X)', platform: 'iPad' }, true],
    ['iPad desktop mode', { userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15)', platform: 'MacIntel', maxTouchPoints: 5 }, true],
    ['Mac Safari', { userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15)', platform: 'MacIntel' }, true],
    ['Mac Chrome', { userAgent: '', platform: '', userAgentData: { platform: 'macOS' } }, true],
    ['Windows', { userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', platform: 'Win32' }, false],
    ['Android', { userAgent: 'Mozilla/5.0 (Linux; Android 15) AppleWebKit/537.36', platform: 'Linux armv8l' }, false],
    ['Linux', { userAgent: 'Mozilla/5.0 (X11; Linux x86_64)', platform: 'Linux x86_64' }, false],
]) {
    test(`Apple sign-in visibility on ${name}`, () => {
        const apple = { hidden: true };
        const group = { hidden: true };
        vm.runInNewContext(script, { navigator, document: { getElementById: id => id === 'apple-sign-in' ? apple : group } });
        assert.equal(apple.hidden, !visible);
        assert.equal(group.hidden, !visible);
    });
}
