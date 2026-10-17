<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/TTTT/WebSocketServer/vendor/autoload.php';
require_once dirname(__DIR__) . '/TTTT/config/realtime.php';

#[AllowDynamicProperties]
final class RealtimeTestConnection implements Ratchet\ConnectionInterface {
    public bool $closed = false;
    public array $messages = [];
    public int $resourceId;
    public $httpRequest;
    public function __construct(int $id, string $origin) {
        $this->resourceId = $id;
        $this->httpRequest = new GuzzleHttp\Psr7\ServerRequest('GET', 'ws://127.0.0.1:18080', ['Origin'=>$origin]);
    }
    public function send($data) { $this->messages[] = json_decode((string)$data,true); }
    public function close() { $this->closed = true; }
}
function realtimeCheck(bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
}
$secret = 'realtime-test-secret-at-least-32-characters';
$origin = 'http://127.0.0.1:18089';
$now = time();
$queue = dirname(__DIR__) . '/.runtime/realtime-test-queue.json';
$server = new WebSocketServer\RealtimeServer($secret, $queue);

$unauthenticated = new RealtimeTestConnection(1,$origin);
ob_start();
$server->onOpen($unauthenticated);
$server->onMessage($unauthenticated,json_encode(['action'=>'subscribe','channel'=>'admin_channel','role'=>'admin','user_id'=>3]));
ob_end_clean();
realtimeCheck($unauthenticated->closed, 'Unauthenticated connection was not closed.');
realtimeCheck(!array_filter($unauthenticated->messages, fn($m)=>($m['action']??'')==='subscribed'), 'Unauthenticated admin subscription succeeded.');

$adminTicket = realtime_ticket_issue(['sub'=>3,'role'=>'admin','channel'=>'admin_channel','context'=>'admin','origin'=>$origin],$secret,$now,60);
$admin = new RealtimeTestConnection(2,$origin);
ob_start();
$server->onOpen($admin);
$server->onMessage($admin,json_encode([
    'action'=>'subscribe','ticket'=>$adminTicket,
    'channel'=>'user_channel','role'=>'guest','user_id'=>0,
]));
ob_end_clean();
$subscription = array_values(array_filter($admin->messages, fn($m)=>($m['action']??'')==='subscribed'));
realtimeCheck(count($subscription)===1 && $subscription[0]['channel']==='admin_channel', 'Signed identity did not override client claims.');
realtimeCheck($admin->role==='admin' && $admin->user_id===3, 'Server identity does not match signed ticket.');

$replay = new RealtimeTestConnection(3,$origin);
ob_start(); $server->onOpen($replay); $server->onMessage($replay,json_encode(['action'=>'subscribe','ticket'=>$adminTicket])); ob_end_clean();
realtimeCheck($replay->closed, 'A ticket replay was accepted.');

foreach ([
    substr($adminTicket,0,-1) . (str_ends_with($adminTicket,'A') ? 'B' : 'A'),
    realtime_ticket_issue(['sub'=>3,'role'=>'admin','channel'=>'admin_channel','context'=>'admin','origin'=>$origin],$secret,$now-100,60),
] as $invalidTicket) {
    $connection = new RealtimeTestConnection(random_int(10,99),$origin);
    ob_start(); $server->onOpen($connection); $server->onMessage($connection,json_encode(['action'=>'subscribe','ticket'=>$invalidTicket])); ob_end_clean();
    realtimeCheck($connection->closed, 'Tampered or expired ticket was accepted.');
}
$wrongOriginTicket = realtime_ticket_issue(['sub'=>3,'role'=>'admin','channel'=>'admin_channel','context'=>'admin','origin'=>$origin],$secret,$now,60);
$wrongOrigin = new RealtimeTestConnection(100,'https://evil.example');
ob_start(); $server->onOpen($wrongOrigin); $server->onMessage($wrongOrigin,json_encode(['action'=>'subscribe','ticket'=>$wrongOriginTicket])); ob_end_clean();
realtimeCheck($wrongOrigin->closed, 'Wrong browser origin was accepted.');

$admin->auth_expires_at = $now-1;
ob_start(); $sent = $server->broadcastToChannel('admin_channel',['type'=>'test']); ob_end_clean();
realtimeCheck($sent===false && $admin->closed, 'Expired lease received a broadcast.');

echo "realtime_ticket_auth=ok\n";
