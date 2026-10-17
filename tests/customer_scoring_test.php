<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/TTTT/WebBanHangMyPham/src/Services/CustomerScoringService.php';

function scoringCheck(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$normal = CustomerScoringService::calculate([
    'is_registered' => true,
    'has_verified_contact' => true,
    'user_messages_30d' => 1,
    'days_since_activity' => 2,
]);
scoringCheck($normal['segment'] === 'normal', 'A legitimate greeting must be normal, not spam.');
scoringCheck($normal['spam_risk_score'] === 0, 'Low engagement must not create spam risk.');

$followUp = CustomerScoringService::calculate([
    'is_registered' => true,
    'has_verified_contact' => true,
    'has_product_interest' => true,
    'user_messages_30d' => 3,
    'product_views_30d' => 4,
    'days_since_activity' => 1,
]);
scoringCheck($followUp['segment'] === 'follow-up', 'Product interest should create a follow-up.');

$potential = CustomerScoringService::calculate([
    'is_registered' => true,
    'has_verified_contact' => true,
    'has_product_interest' => true,
    'has_appointment' => true,
    'pending_qr_orders' => 1,
    'user_messages_30d' => 6,
    'product_views_30d' => 8,
    'cart_adds_30d' => 2,
    'return_visits_30d' => 3,
    'days_since_activity' => 0,
]);
scoringCheck($potential['segment'] === 'potential', 'Strong recent purchase signals should be potential.');
scoringCheck($potential['intent_score'] >= 70, 'Potential threshold must be respected.');

$spam = CustomerScoringService::calculate([
    'duplicate_messages_10m' => 12,
    'rapid_messages_5m' => 35,
]);
scoringCheck($spam['segment'] === 'spam', 'Explicit abusive velocity should be spam.');
scoringCheck($spam['spam_risk_score'] >= 80, 'Spam uses an independent risk score.');

$oldLead = CustomerScoringService::calculate([
    'has_product_interest' => true,
    'user_messages_30d' => 20,
    'product_views_30d' => 20,
    'days_since_activity' => 60,
]);
scoringCheck($oldLead['segment'] === 'normal', 'Stale interest must decay back to normal.');
scoringCheck($oldLead['intent_score'] < 70, 'Recency decay must reduce stale leads.');

$interrupted = CustomerScoringService::calculate([
    'is_registered' => true,
    'has_verified_contact' => true,
    'has_product_interest' => true,
    'product_views_30d' => 4,
    'user_messages_30d' => 3,
    'days_since_activity' => 1,
    'cancelled_orders_30d' => 2,
    'expired_payments_30d' => 1,
]);
scoringCheck($interrupted['intent_score'] < $followUp['intent_score'], 'Cancelled and expired transactions must reduce intent.');

$vip = CustomerScoringService::calculate([
    'completed_orders' => 8,
    'paid_amount_90d' => 5000000,
    'lifetime_paid_amount' => 12000000,
]);
scoringCheck($vip['value_tier'] === 'vip', 'Customer value must be independent from lead intent.');
scoringCheck($vip['segment'] === 'normal', 'A VIP without current intent is a normal lead, not automatically potential.');

echo "customer scoring tests passed\n";
