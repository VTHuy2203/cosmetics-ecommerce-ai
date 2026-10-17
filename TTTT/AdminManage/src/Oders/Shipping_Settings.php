<?php
declare(strict_types=1);

require_once __DIR__ . '/../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/security.php';
require_once __DIR__ . '/../Layouts/Admin_Access_Check.php';

$role = (string) ($_SESSION['admin_role'] ?? '');
if (!in_array($role, ['admin','manager'], true)) { http_response_code(403); exit('Không có quyền.'); }
$db = app_pdo();
$actorId = (int) $_SESSION['admin_id'];
$flash = $_SESSION['shipping_flash'] ?? null;
unset($_SESSION['shipping_flash']);

function shipping_redirect(string $type, string $message, ?int $versionId = null): never
{
    $_SESSION['shipping_flash'] = [$type,$message];
    header('Location: Shipping_Settings.php' . ($versionId ? '?version_id='.$versionId : ''), true, 303);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $versionId = filter_var($_POST['version_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    try {
        if ($action === 'create_version') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $origin = trim((string) ($_POST['origin_label'] ?? ''));
            $freeRaw = trim((string) ($_POST['free_shipping_min'] ?? ''));
            $free = $freeRaw === '' ? null : filter_var($freeRaw, FILTER_VALIDATE_INT);
            if ($name === '' || mb_strlen($name)>120 || $origin === '' || mb_strlen($origin)>255 || ($freeRaw!=='' && ($free===false || $free<0))) {
                throw new DomainException('Thông tin phiên bản không hợp lệ.');
            }
            $stmt=$db->prepare('INSERT INTO shipping_config_versions(name,origin_label,free_shipping_min,created_by) VALUES (?,?,?,?)');
            $stmt->execute([$name,$origin,$free,$actorId]);
            $versionId=(int)$db->lastInsertId();
            $db->prepare("INSERT INTO shipping_config_audit(version_id,actor_id,action,details) VALUES (?,?,'created',JSON_OBJECT())")->execute([$versionId,$actorId]);
            shipping_redirect('success','Đã tạo bản nháp biểu phí.',$versionId);
        }
        if (!$versionId) { throw new DomainException('Thiếu phiên bản biểu phí.'); }
        $version=$db->prepare('SELECT status FROM shipping_config_versions WHERE id=?');
        $version->execute([$versionId]);
        if ($version->fetchColumn()!=='draft') { throw new DomainException('Chỉ được sửa bản nháp.'); }

        if ($action === 'add_region') {
            $code=strtoupper(trim((string)($_POST['code']??'')));
            $name=trim((string)($_POST['region_name']??''));
            if(!preg_match('/^[A-Z0-9_-]{1,32}$/',$code)||$name===''||mb_strlen($name)>120){throw new DomainException('Mã hoặc tên khu vực không hợp lệ.');}
            $db->prepare('INSERT INTO shipping_regions(version_id,code,name) VALUES (?,?,?)')->execute([$versionId,$code,$name]);
        } elseif ($action === 'add_band') {
            $regionId=filter_var($_POST['region_id']??null,FILTER_VALIDATE_INT);
            $min=filter_var($_POST['min_weight_grams']??null,FILTER_VALIDATE_INT);
            $maxRaw=trim((string)($_POST['max_weight_grams']??''));
            $max=$maxRaw===''?null:filter_var($maxRaw,FILTER_VALIDATE_INT);
            $fee=filter_var($_POST['fee']??null,FILTER_VALIDATE_INT);
            if(!$regionId||$min===false||$min<1||($maxRaw!==''&&($max===false||$max<$min))||$fee===false||$fee<0){throw new DomainException('Bậc khối lượng hoặc phí không hợp lệ.');}
            $owner=$db->prepare('SELECT COUNT(*) FROM shipping_regions WHERE id=? AND version_id=?');$owner->execute([$regionId,$versionId]);
            if(!(int)$owner->fetchColumn()){throw new DomainException('Khu vực không thuộc bản nháp.');}
            $overlap=$db->prepare('SELECT COUNT(*) FROM shipping_rate_bands WHERE region_id=? AND NOT (COALESCE(max_weight_grams,2147483647) < ? OR min_weight_grams > COALESCE(?,2147483647))');
            $overlap->execute([$regionId,$min,$max]);
            if((int)$overlap->fetchColumn()){throw new DomainException('Bậc khối lượng bị chồng lấn.');}
            $db->prepare('INSERT INTO shipping_rate_bands(region_id,min_weight_grams,max_weight_grams,fee) VALUES (?,?,?,?)')->execute([$regionId,$min,$max,$fee]);
        } elseif ($action === 'delete_band') {
            $id=filter_var($_POST['band_id']??null,FILTER_VALIDATE_INT);
            $db->prepare('DELETE b FROM shipping_rate_bands b JOIN shipping_regions r ON r.id=b.region_id WHERE b.id=? AND r.version_id=?')->execute([$id,$versionId]);
        } elseif ($action === 'delete_region') {
            $id=filter_var($_POST['region_id']??null,FILTER_VALIDATE_INT);
            $db->prepare('DELETE FROM shipping_regions WHERE id=? AND version_id=?')->execute([$id,$versionId]);
        } elseif ($action === 'update_weight') {
            $productId=filter_var($_POST['product_id']??null,FILTER_VALIDATE_INT);
            $weight=filter_var($_POST['weight_grams']??null,FILTER_VALIDATE_INT);
            if(!$productId||$weight===false||$weight<1||$weight>1000000){throw new DomainException('Khối lượng sản phẩm không hợp lệ.');}
            $db->prepare('UPDATE sanpham SET TrongLuongGram=? WHERE IDSanPham=?')->execute([$weight,$productId]);
        } elseif ($action === 'activate') {
            $db->beginTransaction();
            $locked=$db->prepare("SELECT id FROM shipping_config_versions WHERE id=? AND status='draft' FOR UPDATE");$locked->execute([$versionId]);
            if(!$locked->fetchColumn()){throw new DomainException('Bản nháp không còn khả dụng.');}
            $regions=$db->prepare('SELECT id,name FROM shipping_regions WHERE version_id=? ORDER BY id');$regions->execute([$versionId]);
            $regions=$regions->fetchAll();
            if($regions===[]){throw new DomainException('Cần ít nhất một khu vực.');}
            foreach($regions as $region){
                $bands=$db->prepare('SELECT min_weight_grams,max_weight_grams FROM shipping_rate_bands WHERE region_id=? ORDER BY min_weight_grams');$bands->execute([$region['id']]);
                $bands=$bands->fetchAll(); $expected=1;
                if($bands===[]){throw new DomainException('Khu vực '.$region['name'].' chưa có bậc phí.');}
                foreach($bands as $index=>$band){
                    if((int)$band['min_weight_grams']!==$expected){throw new DomainException('Bậc phí của '.$region['name'].' có khoảng trống.');}
                    if($band['max_weight_grams']===null){if($index!==array_key_last($bands)){throw new DomainException('Bậc không giới hạn phải nằm cuối.');}$expected=-1;}
                    else{$expected=(int)$band['max_weight_grams']+1;}
                }
                if($expected!==-1){throw new DomainException('Bậc cuối của '.$region['name'].' phải để trống khối lượng tối đa.');}
            }
            // Product weights are required only when a region charges by weight band (flat rate = one band).
            $tiered=$db->prepare('SELECT COUNT(*) FROM shipping_regions r WHERE r.version_id=? AND (SELECT COUNT(*) FROM shipping_rate_bands b WHERE b.region_id=r.id)>1');$tiered->execute([$versionId]);
            if((int)$tiered->fetchColumn()>0&&(int)$db->query('SELECT COUNT(*) FROM sanpham WHERE SoLuong>0 AND (TrongLuongGram IS NULL OR TrongLuongGram=0)')->fetchColumn()>0){throw new DomainException('Còn sản phẩm đang bán chưa có khối lượng.');}
            $db->exec("UPDATE shipping_config_versions SET status='archived' WHERE status='active'");
            $db->prepare("UPDATE shipping_config_versions SET status='active',activated_by=?,activated_at=NOW() WHERE id=?")->execute([$actorId,$versionId]);
            $db->prepare("INSERT INTO shipping_config_audit(version_id,actor_id,action,details) VALUES (?,?,'activated',JSON_OBJECT())")->execute([$versionId,$actorId]);
            $db->commit();
            shipping_redirect('success','Đã kích hoạt biểu phí.',$versionId);
        } else { throw new DomainException('Thao tác không hợp lệ.'); }
        $db->prepare("INSERT INTO shipping_config_audit(version_id,actor_id,action,details) VALUES (?,?,?,JSON_OBJECT())")->execute([$versionId,$actorId,$action]);
        shipping_redirect('success','Đã cập nhật bản nháp.',$versionId);
    } catch (Throwable $error) {
        if($db->inTransaction()){$db->rollBack();}
        shipping_redirect('danger',$error instanceof DomainException?$error->getMessage():'Không thể cập nhật biểu phí.',$versionId);
    }
}

$versions=$db->query('SELECT * FROM shipping_config_versions ORDER BY id DESC')->fetchAll();
$selectedId=filter_input(INPUT_GET,'version_id',FILTER_VALIDATE_INT)?:($versions[0]['id']??0);
$selected=null;foreach($versions as $item){if((int)$item['id']===(int)$selectedId){$selected=$item;break;}}
$regions=[];if($selected){$q=$db->prepare('SELECT * FROM shipping_regions WHERE version_id=? ORDER BY sort_order,name');$q->execute([$selectedId]);$regions=$q->fetchAll();}
$products=$db->query('SELECT IDSanPham,TenSanPham,SoLuong,TrongLuongGram FROM sanpham ORDER BY TenSanPham')->fetchAll();
include __DIR__ . '/../Layouts/headHTML.php';
?>
<body id="page-top"><div id="wrapper"><?php include __DIR__.'/../Layouts/SlideBar.php'; ?><div id="content-wrapper"><div id="content"><?php include __DIR__.'/../Layouts/TopBar.php'; ?>
<main class="container-fluid py-4"><h1 class="h3 mb-3">Cấu hình phí vận chuyển</h1>
<?php if($flash):?><div class="alert alert-<?php echo htmlspecialchars($flash[0]);?>"><?php echo htmlspecialchars($flash[1]);?></div><?php endif;?>
<div class="card mb-4"><div class="card-body"><h2 class="h5">Tạo bản nháp</h2><form method="post" class="form-row"><?php echo csrf_field();?><input type="hidden" name="action" value="create_version"><div class="col-md-3"><input class="form-control" name="name" placeholder="Tên phiên bản" required></div><div class="col-md-4"><input class="form-control" name="origin_label" placeholder="Địa chỉ kho gửi" required></div><div class="col-md-3"><input class="form-control" type="number" min="0" name="free_shipping_min" placeholder="Miễn ship từ (đ), để trống nếu không"></div><div class="col-md-2"><button class="btn btn-primary">Tạo</button></div></form></div></div>
<div class="mb-3"><?php foreach($versions as $v):?><a class="btn btn-sm btn-<?php echo (int)$v['id']===(int)$selectedId?'primary':'outline-secondary';?>" href="?version_id=<?php echo (int)$v['id'];?>"><?php echo htmlspecialchars($v['name']);?> · <?php echo htmlspecialchars($v['status']);?></a> <?php endforeach;?></div>
<?php if($selected):?><div class="alert alert-info">Kho: <?php echo htmlspecialchars($selected['origin_label']);?>. Miễn ship: <?php echo $selected['free_shipping_min']===null?'Không áp dụng':number_format((int)$selected['free_shipping_min']).' đ';?>. Trạng thái: <strong><?php echo htmlspecialchars($selected['status']);?></strong></div>
<?php if($selected['status']==='draft'):?><div class="card mb-4"><div class="card-body"><form method="post" class="form-inline"><?php echo csrf_field();?><input type="hidden" name="action" value="add_region"><input type="hidden" name="version_id" value="<?php echo (int)$selectedId;?>"><input class="form-control mr-2" name="code" placeholder="Mã vùng" required><input class="form-control mr-2" name="region_name" placeholder="Tên khu vực" required><button class="btn btn-success">Thêm khu vực</button></form></div></div><?php endif;?>
<?php foreach($regions as $region):?><section class="card mb-3"><div class="card-header d-flex justify-content-between"><strong><?php echo htmlspecialchars($region['name']);?> (<?php echo htmlspecialchars($region['code']);?>)</strong><?php if($selected['status']==='draft'):?><form method="post"><?php echo csrf_field();?><input type="hidden" name="action" value="delete_region"><input type="hidden" name="version_id" value="<?php echo (int)$selectedId;?>"><input type="hidden" name="region_id" value="<?php echo (int)$region['id'];?>"><button class="btn btn-sm btn-danger">Xóa vùng</button></form><?php endif;?></div><div class="card-body"><table class="table table-sm"><thead><tr><th>Từ gram</th><th>Đến gram</th><th>Phí</th><th></th></tr></thead><tbody><?php $b=$db->prepare('SELECT * FROM shipping_rate_bands WHERE region_id=? ORDER BY min_weight_grams');$b->execute([$region['id']]);foreach($b as $band):?><tr><td><?php echo (int)$band['min_weight_grams'];?></td><td><?php echo $band['max_weight_grams']===null?'Không giới hạn':(int)$band['max_weight_grams'];?></td><td><?php echo number_format((int)$band['fee']);?> đ</td><td><?php if($selected['status']==='draft'):?><form method="post"><?php echo csrf_field();?><input type="hidden" name="action" value="delete_band"><input type="hidden" name="version_id" value="<?php echo (int)$selectedId;?>"><input type="hidden" name="band_id" value="<?php echo (int)$band['id'];?>"><button class="btn btn-sm btn-outline-danger">Xóa</button></form><?php endif;?></td></tr><?php endforeach;?></tbody></table><?php if($selected['status']==='draft'):?><form method="post" class="form-row"><?php echo csrf_field();?><input type="hidden" name="action" value="add_band"><input type="hidden" name="version_id" value="<?php echo (int)$selectedId;?>"><input type="hidden" name="region_id" value="<?php echo (int)$region['id'];?>"><div class="col"><input class="form-control" type="number" min="1" name="min_weight_grams" placeholder="Từ gram" required></div><div class="col"><input class="form-control" type="number" min="1" name="max_weight_grams" placeholder="Đến gram (trống = ∞)"></div><div class="col"><input class="form-control" type="number" min="0" name="fee" placeholder="Phí VND" required></div><div class="col"><button class="btn btn-success">Thêm bậc</button></div></form><?php endif;?></div></section><?php endforeach;?>
<?php if($selected['status']==='draft'):?><form method="post" class="mb-4"><?php echo csrf_field();?><input type="hidden" name="action" value="activate"><input type="hidden" name="version_id" value="<?php echo (int)$selectedId;?>"><button class="btn btn-warning">Kiểm tra và kích hoạt</button></form><?php endif;?><?php endif;?>
<section class="card"><div class="card-header"><strong>Khối lượng sản phẩm</strong></div><div class="card-body"><table class="table table-sm"><thead><tr><th>Sản phẩm</th><th>Tồn</th><th>Gram/đơn vị</th><th></th></tr></thead><tbody><?php foreach($products as $product):?><tr><td><?php echo htmlspecialchars($product['TenSanPham']);?></td><td><?php echo (int)$product['SoLuong'];?></td><td><form method="post" class="form-inline"><?php echo csrf_field();?><input type="hidden" name="action" value="update_weight"><input type="hidden" name="version_id" value="<?php echo (int)($selectedId?:0);?>"><input type="hidden" name="product_id" value="<?php echo (int)$product['IDSanPham'];?>"><input class="form-control form-control-sm mr-2" type="number" min="1" max="1000000" name="weight_grams" value="<?php echo htmlspecialchars((string)$product['TrongLuongGram']);?>" required><button class="btn btn-sm btn-primary" <?php echo !$selected||$selected['status']!=='draft'?'disabled':'';?>>Lưu</button></form></td><td><?php echo $product['TrongLuongGram']?'':'Thiếu khối lượng';?></td></tr><?php endforeach;?></tbody></table></div></section>
</main></div></div></div></body></html>
