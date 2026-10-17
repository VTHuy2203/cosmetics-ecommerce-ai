import assert from 'node:assert/strict';

const origin = 'http://127.0.0.1:18089';
async function createSession(as) {
  const response = await fetch(origin + '/fixture-session?as=' + as);
  assert.equal(response.status, 200);
  return {data: await response.json(), cookie: (response.headers.get('set-cookie') || '').split(';', 1)[0]};
}
const adminSession = await createSession('admin');
const session = adminSession.data;
const cookie = adminSession.cookie;
assert.ok(cookie.includes('MOSHOPSESSID='), 'Fixture issued the application session cookie');
const request = (path, options = {}) => fetch(origin + path, {
  redirect: 'manual',
  ...options,
  headers: {Cookie: cookie, ...(options.headers || {})}
});
const state = async () => (await (await request('/fixture-product-state')).json()).featured;

try {
  await request('/fixture-product-state', {method: 'POST'});
  assert.equal(await state(), 0, 'Fixture starts with a non-featured product');
  const legacyGet = await request('/TTTT/AdminManage/src/Products/Manage_Product.php?noibat_product_id=1&noibat=1');
  assert.equal(legacyGet.status, 200, 'Legacy GET is a read-only page request');
  assert.equal(await state(), 0, 'GET cannot change featured state');

  const invalidCsrf = await request('/TTTT/AdminManage/src/Products/Manage_Product.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({noibat_product_id: '1', noibat: '1', csrf_token: 'wrong'})
  });
  assert.equal(invalidCsrf.status, 403, 'Featured mutation rejects an invalid CSRF token');
  assert.equal(await state(), 0, 'Rejected CSRF request cannot mutate the product');

  const validPost = await request('/TTTT/AdminManage/src/Products/Manage_Product.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({noibat_product_id: '1', noibat: '1', csrf_token: session.csrf})
  });
  assert.equal(validPost.status, 303, 'Valid featured mutation uses POST and PRG');
  assert.equal(await state(), 1, 'Valid POST changes featured state');

  await request('/fixture-admin-role?role=customer');
  const revoked = await request('/TTTT/AdminManage/src/Products/Manage_Product.php');
  assert.equal(revoked.status, 302, 'A role revoked in the database invalidates the existing admin session');
  assert.match(revoked.headers.get('location') || '', /Admin_Login\.php$/, 'Revoked session redirects to admin login');
  await request('/fixture-admin-role?role=admin');

  const logoutSession = await createSession('admin_combined');
  // Preserve a simultaneous customer UI identity in the same PHP session.
  const logoutCookie = logoutSession.cookie;
  const logoutRequest = (path, options = {}) => fetch(origin + path, {
    redirect: 'manual', ...options, headers: {Cookie: logoutCookie, ...(options.headers || {})}
  });
  const logoutGet = await logoutRequest('/TTTT/AdminManage/src/Processing/Admin_Logout.php');
  assert.equal(logoutGet.status, 405, 'Admin logout rejects GET');
  assert.equal((await (await logoutRequest('/fixture-session-state')).json()).admin, true, 'GET does not clear admin session');
  const logoutBadCsrf = await logoutRequest('/TTTT/AdminManage/src/Processing/Admin_Logout.php', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'csrf_token=wrong'
  });
  assert.equal(logoutBadCsrf.status, 403, 'Admin logout rejects invalid CSRF');
  const logoutPost = await logoutRequest('/TTTT/AdminManage/src/Processing/Admin_Logout.php', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:new URLSearchParams({csrf_token:logoutSession.data.csrf})
  });
  assert.equal(logoutPost.status, 303, 'Admin logout uses POST/CSRF and PRG');
  assert.equal((await (await logoutRequest('/fixture-session-state')).json()).admin, false, 'Valid logout clears admin session');
  assert.equal((await (await logoutRequest('/fixture-session-state')).json()).customer, true, 'Admin logout preserves customer UI session');

  const employeeSession = await createSession('employee_ui');
  const employeeRequest = (path, options = {}) => fetch(origin + path, {
    redirect:'manual', ...options, headers:{Cookie:employeeSession.cookie, ...(options.headers || {})}
  });
  const appointmentFixture = await (await employeeRequest('/fixture-appointment', {method:'POST'})).json();
  const past = appointmentFixture.rows.find(row => row.session_id === 'f06-appointment-past');
  const future = appointmentFixture.rows.find(row => row.session_id === 'f06-appointment-future');
  assert.ok(past && future, 'Appointment fixture provides past and future rows');
  const appointmentGet = await employeeRequest('/TTTT/WebBanHangMyPham/src/Processing/Employee_Delete_Appointment.php?id=' + past.id);
  assert.equal(appointmentGet.status, 405, 'Appointment deletion rejects GET');
  const appointmentNoCsrf = await employeeRequest('/TTTT/WebBanHangMyPham/src/Processing/Employee_Delete_Appointment.php', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams({id:String(past.id)})
  });
  assert.equal(appointmentNoCsrf.status, 403, 'Appointment deletion requires CSRF');
  const futureDelete = await employeeRequest('/TTTT/WebBanHangMyPham/src/Processing/Employee_Delete_Appointment.php', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:new URLSearchParams({id:String(future.id),csrf_token:employeeSession.data.csrf})
  });
  assert.equal(futureDelete.status, 404, 'Employee cannot delete a future appointment');
  await employeeRequest('/fixture-employee-role?role=customer');
  const revokedEmployee = await employeeRequest('/TTTT/WebBanHangMyPham/src/Processing/Employee_Delete_Appointment.php', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:new URLSearchParams({id:String(past.id),csrf_token:employeeSession.data.csrf})
  });
  assert.equal(revokedEmployee.status, 403, 'Database role revocation blocks appointment deletion');
  await employeeRequest('/fixture-employee-role?role=employee');
  const pastDelete = await employeeRequest('/TTTT/WebBanHangMyPham/src/Processing/Employee_Delete_Appointment.php', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:new URLSearchParams({id:String(past.id),csrf_token:employeeSession.data.csrf})
  });
  assert.equal(pastDelete.status, 200, 'Current employee can delete a past appointment');

  const guestSession = await createSession('guest');
  const guestRequest = (path, options = {}) => fetch(origin + path, {
    redirect: 'manual', ...options, headers: {Cookie: guestSession.cookie, ...(options.headers || {})}
  });
  await guestRequest('/fixture-cart-state', {method: 'POST'});
  const cartState = async () => (await (await guestRequest('/fixture-cart-state')).json()).quantity;
  const cartGet = await guestRequest('/TTTT/WebBanHangMyPham/src/View/Cart.php?CapNhatSoLuong=4&IDCapNhatSoLuong=1');
  assert.equal(cartGet.status, 200, 'Legacy cart quantity GET is read-only');
  assert.equal(await cartState(), 1, 'GET cannot alter cart quantity');
  const cartPost = await guestRequest('/TTTT/WebBanHangMyPham/src/View/Cart.php', {
    method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({CapNhatSoLuong: '4', IDCapNhatSoLuong: '1', csrf_token: guestSession.data.csrf})
  });
  assert.equal(cartPost.status, 303, 'Cart quantity uses POST/CSRF and PRG');
  assert.equal(await cartState(), 4, 'Valid cart POST updates quantity');

  for (const [path, body, sessionCookie] of [
    ['/TTTT/WebBanHangMyPham/src/Processing/Chat_Send.php', JSON.stringify({message:'test'}), guestSession.cookie],
    ['/TTTT/WebBanHangMyPham/src/Processing/Process_Contact.php', new URLSearchParams({ho_ten:'A',email:'a@example.test',noi_dung:'B'}), guestSession.cookie],
    ['/TTTT/WebBanHangMyPham/src/Processing/XuLy_DanhGia.php', new URLSearchParams({IDSanPham:'1',rating:'5'}), (await createSession('buyer')).cookie],
    ['/TTTT/AdminManage/src/Users/Processing_Update_User.php', new URLSearchParams({User_id:'1'}), (await createSession('admin')).cookie]
  ]) {
    const response = await fetch(origin + path, {method:'POST', redirect:'manual', headers:{Cookie:sessionCookie,'Content-Type': path.includes('Chat_Send') ? 'application/json' : 'application/x-www-form-urlencoded'}, body});
    assert.equal(response.status, 403, `${path} rejects a missing CSRF token`);
  }
  console.log('f06_browser=ok');
} finally {
  await fetch(origin + '/fixture-admin-role?role=admin');
  await fetch(origin + '/fixture-employee-role?role=employee');
}
