// Requires the guarded fixture HTTP server :18089 and a dedicated headless Chrome :19227.
import assert from 'node:assert/strict';
const tabs = await (await fetch('http://127.0.0.1:19227/json/list')).json();
const tab = tabs.find(x => x.type === 'page');
assert.ok(tab, 'Dedicated Chrome page is available');
const socket = new WebSocket(tab.webSocketDebuggerUrl);
await new Promise((resolve, reject) => { socket.onopen = resolve; socket.onerror = reject; });
let nextId = 0;
const pending = new Map();
const dialogs = [];
function call(method, params = {}) {
    return new Promise((resolve, reject) => {
        const id = ++nextId;
        const timeout = setTimeout(() => { pending.delete(id); reject(new Error(`CDP timeout: ${method}`)); }, 15000);
        pending.set(id, { resolve, reject, timeout }); socket.send(JSON.stringify({id,method,params}));
    });
}
socket.onmessage = event => {
    const data = JSON.parse(event.data);
    if (data.id && pending.has(data.id)) {
        const p = pending.get(data.id); pending.delete(data.id); clearTimeout(p.timeout);
        if (data.error) p.reject(new Error(data.error.message)); else p.resolve(data.result);
    }
    if (data.method === 'Page.javascriptDialogOpening') {
        dialogs.push(data.params.message);
        void call('Page.handleJavaScriptDialog', {accept:true});
    }
};
async function evaluate(expression) {
    const r = await call('Runtime.evaluate', {expression,awaitPromise:true,returnByValue:true});
    if (r.exceptionDetails) throw new Error(r.exceptionDetails.text);
    return r.result.value;
}
const origin = 'http://127.0.0.1:18089';
async function navigate(path, ready) {
    await call('Page.navigate', {url:origin+path});
    const deadline = Date.now()+20000;
    while (Date.now()<deadline) {
        await new Promise(r=>setTimeout(r,200));
        try { if (await evaluate(ready)) return; } catch {}
    }
    throw new Error('Page did not reach expected ready state: '+path);
}
try {
    await call('Page.enable');
    await navigate('/fixture-session?as=buyer', 'document.readyState === "complete"');
    for (const width of [1440,390]) {
        await evaluate('fetch("/fixture-session?as=buyer").then(r=>r.json())');
        await call('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:width===390});
        await navigate('/TTTT/WebBanHangMyPham/src/View/Detail_Product.php?product_id=1', 'document.readyState === "complete" && typeof Comment === "function" && typeof jQuery === "function"');
        assert.equal(await evaluate('Boolean(window.fixtureXss)'),false,'Stored name/comment HTML must not execute');
        assert.equal(await evaluate('document.querySelectorAll(".review-item img,.review-item script").length'),0,'Comment markup remains text');
        assert.equal(await evaluate('document.body.innerText.includes("Fatal error") || document.body.innerText.includes("Warning:")'),false,'Page renders without PHP warnings');
        const text = `Browser ${width} O'Reilly <img src=x onerror=window.fixtureXss=1>`;
        await evaluate(`document.getElementById('noidungbinhluan').value=${JSON.stringify(text)}; Comment(1);`);
        await new Promise(r=>setTimeout(r,1800));
        assert.equal(await evaluate(`document.getElementById('reviews-container').innerText.includes(${JSON.stringify(text)})`),true,'AJAX comment persists after reload');
        assert.equal(await evaluate('Boolean(window.fixtureXss)'),false,'New comment HTML must not execute');
        await evaluate('fetch("/fixture-session?as=admin").then(r=>r.json())');
        await navigate('/TTTT/AdminManage/src/Comments/Manage_Comments.php', 'document.readyState === "complete" && typeof DelComment === "function" && typeof jQuery === "function"');
        assert.equal(await evaluate('Boolean(window.fixtureXss)'),false,'Admin comment/name HTML must not execute');
        assert.equal(await evaluate('document.body.innerText.includes("Fatal error") || document.body.innerText.includes("Warning:")'),false,'Admin renders without PHP warnings');
        assert.equal(await evaluate(`Array.from(document.querySelectorAll('#dataTable tbody tr')).some(row => row.innerText.includes(${JSON.stringify(text)}))`),true,'Admin lists new comment');
        await evaluate(`Array.from(document.querySelectorAll('#dataTable tbody tr')).find(row=>row.innerText.includes(${JSON.stringify(text)})).querySelector('button').click()`);
        await new Promise(r=>setTimeout(r,1300));
        assert.equal(await evaluate(`document.querySelector('#dataTable tbody').innerText.includes(${JSON.stringify(text)})`),false,'Admin AJAX delete with CSRF succeeds');
        console.log(`comments_browser_${width}=ok`);
    }
    assert.ok(dialogs.filter(x=>x.includes('Xóa bình luận thành công')).length===2,'Both delete actions reported success');
} finally { socket.close(); }
