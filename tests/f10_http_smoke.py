import requests

base = "http://127.0.0.1:18010"
token = {"Authorization": "Bearer f10-test-service-token-32-characters"}
assert requests.get(base + "/ping", timeout=5).json() == {"ok": True}
ready = requests.get(base + "/ready", headers=token, timeout=10)
assert ready.status_code == 200 and ready.json()["ok"] is True, ready.text
assert requests.post(base + "/chat", json={"message": "Tìm son dưỡng"}, timeout=5).status_code == 401
assert requests.post(base + "/chat", headers=token, json={"message": ""}, timeout=5).status_code == 422
product = requests.post(base + "/chat", headers=token, json={
    "message": "Tìm son dưỡng môi", "session_id": "g_test12345",
    "history": [{"role": "user", "content": "Tôi cần dưỡng môi"}],
}, timeout=60).json()
assert product["intent"] == "product" and product["sources"][0]["source"] == "mysql-live", product
shipping = requests.post(base + "/chat", headers=token, json={"message": "Phí ship bao nhiêu?"}, timeout=60).json()
assert shipping["intent"] == "knowledge" and shipping["sources"][0]["source"] == "mysql-live", shipping
print("f10_http=ok; ready=ok; auth=ok; product=mysql-live; shipping=mysql-live")
