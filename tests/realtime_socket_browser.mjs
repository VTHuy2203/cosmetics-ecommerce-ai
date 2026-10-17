import assert from 'node:assert/strict';
const tabs = await (await fetch('http://127.0.0.1:19227/json/list')).json();
const tab = tabs.find(x => x.type === 'page');
assert.ok(tab, 'Dedicated Chrome page is available');
const cdp = new WebSocket(tab.webSocketDebuggerUrl);
await new Promise((resolve,reject)=>{cdp.onopen=resolve;cdp.onerror=reject;});
let sequence=0;
const pending=new Map();
cdp.onmessage=event=>{
    const message=JSON.parse(event.data);
    if (message.id && pending.has(message.id)) {
        const item=pending.get(message.id); pending.delete(message.id); clearTimeout(item.timer);
        message.error ? item.reject(new Error(message.error.message)) : item.resolve(message.result);
    }
};
function call(method,params={}) {
    return new Promise((resolve,reject)=>{
        const id=++sequence;
        const timer=setTimeout(()=>{pending.delete(id);reject(new Error(`CDP timeout: ${method}`));},15000);
        pending.set(id,{resolve,reject,timer}); cdp.send(JSON.stringify({id,method,params}));
    });
}
async function evaluate(expression) {
    const result=await call('Runtime.evaluate',{expression,awaitPromise:true,returnByValue:true});
    if(result.exceptionDetails) throw new Error(result.exceptionDetails.exception?.description||result.exceptionDetails.text);
    return result.result.value;
}
async function navigate(url) {
    await call('Page.navigate',{url});
    for(let i=0;i<80;i++){
        await new Promise(r=>setTimeout(r,100));
        if(await evaluate('document.readyState === "complete"')) return;
    }
    throw new Error('Navigation timeout');
}
const fixture='http://127.0.0.1:18089';
async function ticket(as,context) {
    return evaluate(`(async()=>{
        const session=await fetch('/fixture-session?as=${as}').then(r=>r.json());
        const response=await fetch('/TTTT/WebBanHangMyPham/src/Processing/Realtime_Ticket.php',{
            method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-CSRF-Token':session.csrf},
            body:JSON.stringify({context:${JSON.stringify(context)}})
        });
        return {status:response.status,body:await response.json()};
    })()`);
}
async function socket(frame) {
    return evaluate(`new Promise(resolve=>{
        const events=[]; const ws=new WebSocket('ws://127.0.0.1:18080');
        const timer=setTimeout(()=>{try{ws.close()}catch{};resolve(events)},4000);
        ws.onopen=()=>ws.send(${JSON.stringify(JSON.stringify(frame))});
        ws.onmessage=e=>{events.push(JSON.parse(e.data));if(events.some(x=>x.action==='subscribed'||x.action==='error')){clearTimeout(timer);setTimeout(()=>resolve(events),100)}};
        ws.onclose=()=>{events.push({action:'closed'});clearTimeout(timer);resolve(events)};
        ws.onerror=()=>events.push({action:'socket_error'});
    })`);
}
try {
    await call('Page.enable');
    await navigate(fixture+'/fixture-session?as=buyer');
    let response=await ticket('buyer','customer');
    assert.equal(response.status,200,'Customer ticket endpoint succeeds');
    let events=await socket({action:'subscribe',ticket:response.body.ticket,channel:'admin_channel',role:'admin',user_id:3});
    assert.ok(events.some(x=>x.action==='subscribed'&&x.channel==='user_channel'),
        `Server maps customer from ticket; events=${JSON.stringify(events)}`);
    events=await socket({action:'subscribe',ticket:response.body.ticket});
    assert.ok(events.some(x=>x.action==='error')&&events.some(x=>x.action==='closed'),'Replay is rejected and closed');

    response=await ticket('admin','admin');
    assert.equal(response.status,200,'Admin ticket endpoint succeeds');
    events=await socket({action:'subscribe',ticket:response.body.ticket,channel:'user_channel',role:'guest',user_id:0});
    assert.ok(events.some(x=>x.action==='subscribed'&&x.channel==='admin_channel'),'Server maps admin from ticket');

    events=await socket({action:'subscribe',channel:'admin_channel',role:'admin',user_id:3});
    assert.ok(events.some(x=>x.action==='error')&&events.some(x=>x.action==='closed'),'Unsigned admin claim is rejected');
    response=await ticket('buyer','admin');
    assert.equal(response.status,403,'Customer session cannot request admin ticket');

    response=await ticket('staff','admin');
    assert.equal(response.status,200,'Staff ticket endpoint succeeds');
    events=await socket({action:'subscribe',ticket:response.body.ticket,channel:'admin_channel',role:'admin'});
    assert.ok(events.some(x=>x.action==='subscribed'&&x.channel==='staff_channel'),'Employee is restricted to staff channel');

    response=await ticket('manager','admin');
    assert.equal(response.status,200,'Manager ticket endpoint succeeds');
    events=await socket({action:'subscribe',ticket:response.body.ticket,channel:'staff_channel',role:'employee'});
    assert.ok(events.some(x=>x.action==='subscribed'&&x.channel==='admin_channel'),'Manager is mapped to admin channel');

    response=await ticket('shipper','customer');
    assert.equal(response.status,200,'Shipper ticket endpoint succeeds');
    events=await socket({action:'subscribe',ticket:response.body.ticket,channel:'admin_channel',role:'admin'});
    assert.ok(events.some(x=>x.action==='subscribed'&&x.channel==='shipper_channel'),'Shipper is restricted to shipper channel');
    response=await ticket('shipper','admin');
    assert.equal(response.status,403,'Shipper session cannot request admin ticket');
    console.log('realtime_socket_browser=ok');
} finally { cdp.close(); }
