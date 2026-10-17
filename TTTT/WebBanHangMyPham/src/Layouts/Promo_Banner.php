<?php
/**
 * Promotional Banner for Potential Customer Program
 * Displays an eye-catching banner encouraging customers to spend more
 */

// Get customer status if logged in
$customerLabel = 'normal';

if (isset($_SESSION['IDNguoiDung'])) {
    try {
        $pdoBanner = app_pdo();
        $stmt = $pdoBanner->prepare("SELECT customer_label,customer_value_tier FROM khachhang WHERE ID_KhachHang = ?");
        $stmt->execute([$_SESSION['IDNguoiDung']]);
        $custData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($custData) {
            $customerLabel = $custData['customer_label'] ?? 'normal';
        }
    } catch (Exception $e) {
        // Silently fail
    }
}

$isPotential = ($customerLabel === 'potential');
?>

<!-- Promotional Banner -->
<div id="promoBanner" class="promo-banner-container">
    <div class="promo-banner <?php echo $isPotential ? 'promo-potential' : 'promo-normal'; ?>">
        <button class="promo-close" onclick="closePromoBanner()">&times;</button>
        
        <?php if ($isPotential): ?>
        <!-- Banner for Potential Customers -->
        <div class="promo-content">
            <div class="promo-icon">⭐</div>
            <div class="promo-text">
                <div class="promo-title">Sẵn sàng hỗ trợ nhu cầu của bạn</div>
                <div class="promo-desc">
                    Bạn đang có nhu cầu mua sắm rõ ràng. Mơ Shop sẽ ưu tiên hỗ trợ tư vấn sản phẩm phù hợp.
                </div>
            </div>
            <a href="View_Product.php" class="promo-btn">Mua sắm ngay</a>
        </div>
        
        <?php else: ?>
        <!-- Banner for Regular Customers -->
        <div class="promo-content">
            <div class="promo-icon">🛍️</div>
            <div class="promo-text">
                <div class="promo-title">Khám phá sản phẩm phù hợp với bạn</div>
                <div class="promo-desc">
                    Tìm kiếm mỹ phẩm theo loại da, nhu cầu và ngân sách của bạn.
                </div>
            </div>
            <a href="View_Product.php" class="promo-btn pulse-animation">Mua ngay!</a>
        </div>
        <?php endif; ?>
        
    </div>
</div>

<style>
/* Promotional Banner Styles */
.promo-banner-container {
    position: fixed;
    bottom: 80px;
    left: 20px;
    z-index: 9990;
    max-width: 420px;
    animation: slideInLeft 0.5s ease-out;
}

@keyframes slideInLeft {
    from { transform: translateX(-100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

.promo-banner {
    border-radius: 16px;
    padding: 16px 20px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    position: relative;
    overflow: hidden;
}

.promo-normal {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
}

.promo-potential {
    background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
    color: #333;
}

.promo-close {
    position: absolute;
    top: 8px;
    right: 12px;
    background: rgba(255,255,255,0.3);
    border: none;
    color: inherit;
    font-size: 20px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}

.promo-close:hover {
    background: rgba(255,255,255,0.5);
}

.promo-content {
    display: flex;
    align-items: center;
    gap: 14px;
}

.promo-icon {
    font-size: 36px;
    flex-shrink: 0;
}

.promo-text {
    flex: 1;
}

.promo-title {
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 4px;
}

.promo-desc {
    font-size: 12px;
    opacity: 0.95;
    line-height: 1.4;
}

.promo-progress {
    display: inline-block;
    margin-top: 4px;
    padding: 2px 8px;
    background: rgba(255,255,255,0.25);
    border-radius: 10px;
    font-size: 11px;
}

.promo-btn {
    flex-shrink: 0;
    background: #fff;
    color: #764ba2;
    padding: 10px 16px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    font-size: 13px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transition: transform 0.2s, box-shadow 0.2s;
}

.promo-potential .promo-btn {
    color: #e85d04;
}

.promo-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 16px rgba(0,0,0,0.2);
    color: inherit;
    text-decoration: none;
}

.pulse-animation {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    50% { box-shadow: 0 4px 20px rgba(255,255,255,0.5), 0 0 30px rgba(255,215,0,0.4); }
}

/* Progress bar */
.promo-progress-bar {
    margin-top: 12px;
    height: 8px;
    background: rgba(255,255,255,0.3);
    border-radius: 4px;
    position: relative;
    overflow: hidden;
}

.promo-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #4ade80, #22c55e);
    border-radius: 4px;
    transition: width 0.5s ease;
}

.promo-progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 9px;
    font-weight: 600;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

/* Mobile responsive */
@media (max-width: 480px) {
    .promo-banner-container {
        left: 10px;
        right: 10px;
        max-width: none;
        bottom: 70px;
    }
    .promo-content {
        flex-wrap: wrap;
    }
    .promo-btn {
        width: 100%;
        text-align: center;
        margin-top: 10px;
    }
}

/* Hide banner when closed */
.promo-banner-container.hidden {
    display: none;
}
</style>

<script>
function closePromoBanner() {
    const banner = document.getElementById('promoBanner');
    if (banner) {
        banner.classList.add('hidden');
        // Remember closure for today only (reset next day)
        const today = new Date().toDateString();
        localStorage.setItem('promoBannerClosedDate', today);
    }
}

// Check if banner was closed today
document.addEventListener('DOMContentLoaded', function() {
    const closedDate = localStorage.getItem('promoBannerClosedDate');
    const today = new Date().toDateString();

    // If closed date is not today, show banner again
    if (closedDate && closedDate === today) {
        const banner = document.getElementById('promoBanner');
        if (banner) banner.classList.add('hidden');
    } else {
        // Clear old closed date if it's a new day
        localStorage.removeItem('promoBannerClosedDate');
    }
});
</script>
