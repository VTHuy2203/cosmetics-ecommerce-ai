<?php
declare(strict_types=1);
require_once '../DB_Connect.php';
require_once dirname(__DIR__,3).'/config/security.php';
require_once dirname(__DIR__).'/Services/ShippingService.php';
require_once dirname(__DIR__).'/Services/VoucherService.php';
if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){json_response(['success'=>false,'error'=>'method_not_allowed'],405);}
require_csrf(true);
if(empty($_SESSION['cart'])){json_response(['success'=>false,'error'=>'empty_cart'],422);}
try{
    $quote=(new ShippingService($conn))->quoteCart($_SESSION['cart'],(string)($_POST['region_code']??''));
    $discount=0;
    if(!empty($_SESSION['voucher_code'])&&!empty($_SESSION['IDNguoiDung'])){
        try{$voucher=(new VoucherService($conn))->validate((string)$_SESSION['voucher_code'],(int)$_SESSION['IDNguoiDung'],$quote['subtotal']);$discount=(int)$voucher['discount_amount'];}catch(VoucherException){}
    }
    $quote['discount_amount']=$discount;$quote['total']=max(0,$quote['subtotal']-$discount)+$quote['fee'];
    json_response(['success'=>true,'quote'=>$quote]);
}catch(ShippingException $error){json_response(['success'=>false,'error'=>$error->reason,'message'=>$error->getMessage()],422);}
