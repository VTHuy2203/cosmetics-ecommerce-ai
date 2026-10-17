<?php
declare(strict_types=1);

final class ShippingException extends RuntimeException
{
    public function __construct(public readonly string $reason, string $message) { parent::__construct($message); }
}

final class ShippingService
{
    public function __construct(private readonly mysqli $db) {}

    /** @return array<int,array{code:string,name:string}> */
    public function activeRegions(): array
    {
        $result = $this->db->query(
            "SELECT r.code,r.name FROM shipping_config_versions v
             JOIN shipping_regions r ON r.version_id=v.id
             WHERE v.status='active' ORDER BY r.sort_order,r.name"
        );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** @param array<int,array<int,mixed>> $cart
     *  @return array{version_id:int,region_code:string,region_name:string,total_weight_grams:int,fee:int,subtotal:int}
     */
    public function quoteCart(array $cart, string $regionCode): array
    {
        $quantities=[];
        foreach($cart as $item){$id=filter_var($item[0]??null,FILTER_VALIDATE_INT);$qty=filter_var($item[3]??null,FILTER_VALIDATE_INT);if(!$id||!$qty||$qty<1){throw new ShippingException('invalid_cart','Giỏ hàng không hợp lệ.');}$quantities[$id]=($quantities[$id]??0)+$qty;}
        if($quantities===[]){throw new ShippingException('invalid_cart','Giỏ hàng đang trống.');}
        $stmt=$this->db->prepare('SELECT GiaTien,TrongLuongGram FROM sanpham WHERE IDSanPham=?');
        $subtotal=0;$weight=0;
        foreach($quantities as $id=>$qty){$stmt->bind_param('i',$id);$stmt->execute();$product=$stmt->get_result()->fetch_assoc();if(!$product){throw new ShippingException('product_not_found','Sản phẩm không còn tồn tại.');}$grams=max(0,(int)($product['TrongLuongGram']??0));$subtotal+=(int)$product['GiaTien']*$qty;$weight+=$grams*$qty;}
        return $this->quote($regionCode,$weight,$subtotal)+['subtotal'=>$subtotal];
    }

    /** @return array{version_id:int,region_code:string,region_name:string,total_weight_grams:int,fee:int} */
    public function quote(string $regionCode, int $totalWeightGrams, int $subtotal, bool $lock = false): array
    {
        $regionCode = strtoupper(trim($regionCode));
        if (!preg_match('/^[A-Z0-9_-]{1,32}$/', $regionCode) || $totalWeightGrams < 0 || $subtotal < 0) {
            throw new ShippingException('invalid_shipping_input', 'Thông tin tính phí vận chuyển không hợp lệ.');
        }
        // Weight only matters when the region has several weight bands; a flat rate (one 1g..∞ band)
        // works for products whose weight has not been entered yet.
        if ($totalWeightGrams < 1 && $this->regionBandCount($regionCode) > 1) {
            throw new ShippingException('shipping_weight_missing', 'Sản phẩm chưa có khối lượng vận chuyển.');
        }
        $lookupWeight = max(1, $totalWeightGrams);
        $sql = "SELECT v.id AS version_id,v.free_shipping_min,r.code,r.name,b.fee
                FROM shipping_config_versions v
                JOIN shipping_regions r ON r.version_id=v.id AND r.code=?
                JOIN shipping_rate_bands b ON b.region_id=r.id
                  AND b.min_weight_grams<=? AND (b.max_weight_grams IS NULL OR b.max_weight_grams>=?)
                WHERE v.status='active'" . ($lock ? ' FOR SHARE' : '');
        $statement = $this->db->prepare($sql);
        $statement->bind_param('sii', $regionCode, $lookupWeight, $lookupWeight);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        if (count($rows) !== 1) {
            throw new ShippingException('shipping_unavailable', 'Chưa có biểu phí phù hợp cho khu vực và khối lượng đơn hàng.');
        }
        $row = $rows[0];
        $freeMinimum = $row['free_shipping_min'] === null ? null : (int) $row['free_shipping_min'];
        return [
            'version_id'=>(int)$row['version_id'], 'region_code'=>(string)$row['code'],
            'region_name'=>(string)$row['name'], 'total_weight_grams'=>$totalWeightGrams,
            'fee'=>$freeMinimum !== null && $subtotal >= $freeMinimum ? 0 : (int)$row['fee'],
            'free_shipping_min'=>$freeMinimum,
        ];
    }

    private function regionBandCount(string $regionCode): int
    {
        $statement = $this->db->prepare(
            "SELECT COUNT(*) FROM shipping_config_versions v
             JOIN shipping_regions r ON r.version_id=v.id AND r.code=?
             JOIN shipping_rate_bands b ON b.region_id=r.id
             WHERE v.status='active'"
        );
        $statement->bind_param('s', $regionCode);
        $statement->execute();
        return (int) $statement->get_result()->fetch_row()[0];
    }
}
