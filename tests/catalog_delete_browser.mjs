import assert from 'node:assert/strict';

const tabs = await (await fetch('http://127.0.0.1:19227/json/list')).json();
const tab = tabs.find(item => item.type === 'page');
assert.ok(tab, 'Dedicated Chrome page is available');
const cdp = new WebSocket(tab.webSocketDebuggerUrl);
await new Promise((resolve, reject) => { cdp.onopen = resolve; cdp.onerror = reject; });
let id = 0;
const pending = new Map();
cdp.onmessage = event => {
    const message = JSON.parse(event.data);
    if (message.id && pending.has(message.id)) {
        const request = pending.get(message.id);
        pending.delete(message.id);
        clearTimeout(request.timer);
        message.error ? request.reject(new Error(message.error.message)) : request.resolve(message.result);
    }
    if (message.method === 'Page.javascriptDialogOpening') {
        void call('Page.handleJavaScriptDialog', {accept:true});
    }
};
function call(method, params={}) {
    return new Promise((resolve,reject) => {
        const requestId=++id;
        const timer=setTimeout(()=>{pending.delete(requestId);reject(new Error(`CDP timeout: ${method}`));},15000);
        pending.set(requestId,{resolve,reject,timer});
        cdp.send(JSON.stringify({id:requestId,method,params}));
    });
}
async function evaluate(expression) {
    const result=await call('Runtime.evaluate',{expression,awaitPromise:true,returnByValue:true});
    if(result.exceptionDetails) throw new Error(result.exceptionDetails.text);
    return result.result.value;
}
async function navigate(path) {
    await call('Page.navigate',{url:'http://127.0.0.1:18089'+path});
    for(let i=0;i<100;i++){
        await new Promise(resolve=>setTimeout(resolve,100));
        if(await evaluate('document.readyState === "complete"')) return;
    }
    throw new Error(`Navigation timeout: ${path}`);
}
async function submitRow(label) {
    const submitted=await evaluate(`(()=>{const row=Array.from(document.querySelectorAll('#dataTable tbody tr')).find(x=>x.innerText.includes(${JSON.stringify(label)}));if(!row)return false;const form=row.querySelector('form');if(!form)return false;form.requestSubmit();return true})()`);
    assert.equal(submitted,true,`Delete form exists for ${label}`);
    for(let i=0;i<100;i++){
        await new Promise(resolve=>setTimeout(resolve,100));
        if((await evaluate('location.search')).includes('delete_status=')
            && await evaluate('document.readyState === "complete" && Boolean(document.body)')) return;
    }
    throw new Error(`Delete redirect timeout: ${label}`);
}
try {
    await call('Page.enable');
    await navigate('/fixture-session?as=admin');

    await navigate('/TTTT/AdminManage/src/Categorys/Manage_Category.php');
    await submitRow('Danh mục thử');
    assert.ok((await evaluate('document.body.innerText')).includes('Không thể xóa'),'Referenced category warning is visible');
    await navigate('/TTTT/AdminManage/src/Categorys/Manage_Category.php');
    await submitRow('Danh mục chưa dùng');
    assert.ok((await evaluate('document.body.innerText')).includes('Đã xóa'),'Unused category success is visible');

    await navigate('/TTTT/AdminManage/src/Brands/Manage_Brand.php');
    await submitRow('Thương hiệu thử');
    assert.ok((await evaluate('document.body.innerText')).includes('Không thể xóa'),'Referenced brand warning is visible');

    await navigate('/TTTT/AdminManage/src/Products/Manage_Product.php');
    await submitRow('Sản phẩm có đơn');
    assert.ok((await evaluate('document.body.innerText')).includes('Không thể xóa'),'Sold product warning is visible');
    await navigate('/TTTT/AdminManage/src/Products/Manage_Product.php');
    await submitRow('Sản phẩm chưa bán');
    assert.ok((await evaluate('document.body.innerText')).includes('Đã xóa'),'Unsold product success is visible');
    assert.equal((await evaluate('document.body.innerText')).includes('Fatal error'),false,'Catalog pages render without fatal errors');
    console.log('catalog_delete_browser=ok');
} finally {
    cdp.close();
}
