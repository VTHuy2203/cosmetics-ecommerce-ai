<?php
declare(strict_types=1);

final class CustomerScoringService
{
    public const VERSION = 'rules-v1.0';

    public function __construct(private PDO $db)
    {
    }

    /** @return array{updated:int,failed:int,errors:list<string>} */
    public function scoreAll(): array
    {
        $updated = 0;
        $failed = 0;
        $errors = [];

        // Staff accounts are never scored; clear any legacy customer label they still carry.
        $this->db->exec(
            "UPDATE khachhang SET customer_label='normal',lead_score=0,spam_risk_score=0,customer_value_score=0,
                    customer_value_tier='new',scoring_version=NULL
             WHERE Role IS NOT NULL AND Role <> 'customer' AND (customer_label <> 'normal' OR lead_score <> 0)"
        );

        $users = $this->db->query(
            "SELECT ID_KhachHang FROM khachhang WHERE Role = 'customer' OR Role IS NULL ORDER BY ID_KhachHang"
        )->fetchAll(PDO::FETCH_COLUMN);
        foreach ($users as $userId) {
            try {
                $this->scoreSubject((int) $userId, null);
                $updated++;
            } catch (Throwable $error) {
                $failed++;
                $errors[] = 'user:' . (int) $userId . ': ' . $error->getMessage();
            }
        }

        $statement = $this->db->query(
            "SELECT session_id FROM customer_analysis WHERE user_id IS NULL AND session_id <> ''
             UNION SELECT session_id FROM user_events WHERE user_id IS NULL AND session_id <> ''
             UNION SELECT session_id FROM guest_customers WHERE session_id <> ''
             ORDER BY session_id"
        );
        foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $sessionId) {
            try {
                $this->scoreSubject(null, (string) $sessionId);
                $updated++;
            } catch (Throwable $error) {
                $failed++;
                $errors[] = 'session:' . substr((string) $sessionId, 0, 24) . ': ' . $error->getMessage();
            }
        }

        return ['updated' => $updated, 'failed' => $failed, 'errors' => $errors];
    }

    /**
     * Re-score one subject right after a customer action (order, cart, wishlist, chat).
     * Fail-open like CustomerEventService: scoring must never interrupt the action itself.
     */
    public static function refreshQuietly(?int $userId, ?string $sessionId): void
    {
        $userId = ($userId ?? 0) > 0 ? $userId : null;
        $sessionId = trim((string) $sessionId) ?: null;
        if ($userId === null && $sessionId === null) {
            return;
        }
        try {
            if (!function_exists('app_pdo')) {
                require_once dirname(__DIR__, 3) . '/config/bootstrap.php';
            }
            $db = app_pdo();
            if ($userId !== null) {
                // Only customers are scored; staff accounts keep the neutral label set by scoreAll().
                $role = $db->prepare('SELECT Role FROM khachhang WHERE ID_KhachHang = ? LIMIT 1');
                $role->execute([$userId]);
                $value = $role->fetchColumn();
                if ($value !== false && $value !== null && $value !== 'customer') {
                    return;
                }
            }
            (new self($db))->scoreSubject($userId, $userId === null ? $sessionId : null);
        } catch (Throwable $error) {
            error_log('Customer score refresh failed: ' . $error->getMessage());
        }
    }

    /** @return array<string,mixed> */
    public function scoreSubject(?int $userId, ?string $sessionId): array
    {
        if (($userId ?? 0) <= 0 && trim((string) $sessionId) === '') {
            throw new InvalidArgumentException('A customer or session is required.');
        }

        $sessionId = trim((string) $sessionId) ?: null;
        $features = $this->collectFeatures($userId, $sessionId);
        $score = self::calculate($features);
        $this->persist($userId, $sessionId, $score, $features['last_activity_at'] ?? null);

        return $score;
    }

    /**
     * Pure scoring function used by production and regression tests.
     *
     * @param array<string,mixed> $features
     * @return array{intent_score:int,spam_risk_score:int,value_score:int,segment:string,value_tier:string,reasons:list<array{code:string,points:int,label:string}>}
     */
    public static function calculate(array $features): array
    {
        $reasons = [];
        $intent = 0;
        $daysSinceActivity = $features['days_since_activity'] ?? null;
        $recentWithin14Days = $daysSinceActivity !== null && (int) $daysSinceActivity <= 14;
        $recentWithin30Days = $daysSinceActivity !== null && (int) $daysSinceActivity <= 30;

        $add = static function (string $code, int $points, string $label) use (&$intent, &$reasons): void {
            if ($points === 0) {
                return;
            }
            $intent += $points;
            $reasons[] = ['code' => $code, 'points' => $points, 'label' => $label];
        };

        $commercial = 0;
        if ((int) ($features['pending_qr_orders'] ?? 0) > 0) {
            $commercial += 25;
        }
        if ((int) ($features['pending_orders'] ?? 0) > 0) {
            $commercial += 20;
        }
        if (!empty($features['has_appointment']) && $recentWithin14Days) {
            $commercial += 18;
        }
        if (!empty($features['has_product_interest']) && $recentWithin30Days) {
            $commercial += 12;
        }
        $commercial = min(35, $commercial);
        $add('commercial_intent', $commercial, 'Tín hiệu mua hàng');

        $funnel = min(8, (int) ($features['product_views_30d'] ?? 0));
        $funnel += min(8, (int) ($features['wishlist_items'] ?? 0) * 4);
        $funnel += min(12, (int) ($features['cart_adds_30d'] ?? 0) * 4);
        $funnel += min(5, (int) ($features['return_visits_30d'] ?? 0));
        $funnel = min(25, $funnel);
        $add('funnel_activity', $funnel, 'Tương tác sản phẩm và hành trình mua');

        $userMessages = (int) ($features['user_messages_30d'] ?? 0);
        $engagement = min(15, $userMessages >= 6 ? 15 : ($userMessages >= 3 ? 10 : ($userMessages > 0 ? 5 : 0)));
        $add('engagement', $engagement, 'Mức độ tương tác hội thoại');

        $recency = 0;
        if ($daysSinceActivity !== null) {
            $daysSinceActivity = max(0, (int) $daysSinceActivity);
            $recency = match (true) {
                $daysSinceActivity <= 1 => 15,
                $daysSinceActivity <= 3 => 11,
                $daysSinceActivity <= 7 => 7,
                $daysSinceActivity <= 14 => 3,
                default => 0,
            };
        }
        $add('recency', $recency, 'Độ mới của tương tác');

        $profile = !empty($features['is_registered']) ? 5 : 0;
        $profile += !empty($features['has_verified_contact']) ? 5 : 0;
        $add('profile_quality', min(10, $profile), 'Hồ sơ và liên hệ hợp lệ');

        $negative = 0;
        if ((int) ($features['cancelled_orders_30d'] ?? 0) > 0) {
            $negative -= min(12, (int) $features['cancelled_orders_30d'] * 4);
        }
        if ((int) ($features['expired_payments_30d'] ?? 0) > 0) {
            $negative -= min(10, (int) $features['expired_payments_30d'] * 3);
        }
        $add('negative_signals', $negative, 'Tín hiệu gián đoạn giao dịch');
        $intent = max(0, min(100, $intent));

        $spamRisk = 0;
        if (!empty($features['spam_confirmed'])) {
            $spamRisk = 100;
        } else {
            if ((int) ($features['duplicate_messages_10m'] ?? 0) >= 10) {
                $spamRisk += 60;
            }
            if ((int) ($features['rapid_messages_5m'] ?? 0) >= 30) {
                $spamRisk += 30;
            }
            if (!empty($features['suspicious_link'])) {
                $spamRisk += 40;
            }
            $spamRisk = min(100, $spamRisk);
        }

        $strongSignal = (int) ($features['pending_orders'] ?? 0) > 0
            || (int) ($features['pending_qr_orders'] ?? 0) > 0
            || (int) ($features['cart_adds_30d'] ?? 0) >= 2
            || ($recentWithin14Days && !empty($features['has_appointment']) && !empty($features['has_product_interest']));

        if ($spamRisk >= 80) {
            $segment = 'spam';
        } elseif ($intent >= 70 && $strongSignal) {
            $segment = 'potential';
        } elseif ($intent >= 40 || ($recentWithin14Days && (!empty($features['has_appointment']) || !empty($features['has_product_interest'])))) {
            $segment = 'follow-up';
        } else {
            $segment = 'normal';
        }

        $completedOrders = (int) ($features['completed_orders'] ?? 0);
        $paidAmount90d = (int) ($features['paid_amount_90d'] ?? 0);
        $lifetimePaid = (int) ($features['lifetime_paid_amount'] ?? 0);
        $value = min(100, min(40, $completedOrders * 10)
            + min(35, (int) floor($paidAmount90d / 500000) * 5)
            + min(25, (int) floor($lifetimePaid / 2000000) * 5));
        $valueTier = match (true) {
            $value >= 80 => 'vip',
            $value >= 50 => 'loyal',
            $value >= 20 => 'regular',
            default => 'new',
        };

        return [
            'intent_score' => $intent,
            'spam_risk_score' => $spamRisk,
            'value_score' => $value,
            'segment' => $segment,
            'value_tier' => $valueTier,
            'reasons' => $reasons,
        ];
    }

    /** @return array<string,mixed> */
    private function collectFeatures(?int $userId, ?string $sessionId): array
    {
        $lastActivity = null;
        $features = [
            'is_registered' => $userId !== null,
            'has_verified_contact' => false,
            'has_product_interest' => false,
            'has_appointment' => false,
            'pending_orders' => 0,
            'pending_qr_orders' => 0,
            'cancelled_orders_30d' => 0,
            'expired_payments_30d' => 0,
            'completed_orders' => 0,
            'paid_amount_90d' => 0,
            'lifetime_paid_amount' => 0,
            'product_views_30d' => 0,
            'cart_adds_30d' => 0,
            'return_visits_30d' => 0,
            'wishlist_items' => 0,
            'user_messages_30d' => 0,
            'days_since_activity' => null,
            'last_activity_at' => null,
            'duplicate_messages_10m' => 0,
            'rapid_messages_5m' => 0,
            'suspicious_link' => false,
            'spam_confirmed' => false,
        ];

        if ($userId !== null) {
            $statement = $this->db->prepare('SELECT Email, SDT FROM khachhang WHERE ID_KhachHang = ? LIMIT 1');
            $statement->execute([$userId]);
            $profile = $statement->fetch();
            $features['has_verified_contact'] = $profile
                && (filter_var($profile['Email'] ?? '', FILTER_VALIDATE_EMAIL) !== false
                    || preg_match('/^[0-9+ .()-]{9,20}$/', (string) ($profile['SDT'] ?? '')) === 1);

            $statement = $this->db->prepare(
                "SELECT SUM(CASE WHEN DaThanhToan = 1 AND TrangThai = 3 THEN 1 ELSE 0 END) completed_orders,
                        COALESCE(SUM(CASE WHEN DaThanhToan = 1 AND NgayTao >= NOW() - INTERVAL 90 DAY THEN TongTien ELSE 0 END),0) paid_amount_90d,
                        COALESCE(SUM(CASE WHEN DaThanhToan = 1 THEN TongTien ELSE 0 END),0) lifetime_paid_amount,
                        SUM(CASE WHEN DaThanhToan = 0 AND TrangThai = 0 AND NgayTao >= NOW() - INTERVAL 14 DAY THEN 1 ELSE 0 END) pending_orders,
                        SUM(CASE WHEN DaThanhToan = 0 AND TrangThai = 0 AND PhuongThucThanhToan IN ('qr','sepay_pg') AND NgayTao >= NOW() - INTERVAL 14 DAY THEN 1 ELSE 0 END) pending_qr_orders,
                        SUM(CASE WHEN TrangThai = -1 AND NgayTao >= NOW() - INTERVAL 30 DAY THEN 1 ELSE 0 END) cancelled_orders_30d,
                        SUM(CASE WHEN TrangThaiThanhToan = 'expired' AND NgayTao >= NOW() - INTERVAL 30 DAY THEN 1 ELSE 0 END) expired_payments_30d,
                        MAX(NgayTao) last_order_activity
                 FROM donhang WHERE ID_KhachHang = ?"
            );
            $statement->execute([$userId]);
            $order = $statement->fetch() ?: [];
            foreach (['completed_orders','paid_amount_90d','lifetime_paid_amount','pending_orders','pending_qr_orders','cancelled_orders_30d','expired_payments_30d'] as $key) {
                $features[$key] = (int) ($order[$key] ?? 0);
            }
            if (!empty($order['last_order_activity'])) {
                $lastActivity = $order['last_order_activity'];
            }

            $statement = $this->db->prepare('SELECT COUNT(*) FROM danhsachyeuthich WHERE ID_KhachHang = ?');
            $statement->execute([$userId]);
            $features['wishlist_items'] = (int) $statement->fetchColumn();
        }

        [$subjectSql, $subjectParams] = $userId !== null
            ? ['user_id = ?', [$userId]]
            : ['user_id IS NULL AND session_id = ?', [$sessionId]];

        $statement = $this->db->prepare(
            "SELECT MAX(product_interest IS NOT NULL AND product_interest <> '') has_product_interest,
                    MAX(has_appointment) has_appointment
             FROM customer_analysis WHERE {$subjectSql}"
        );
        $statement->execute($subjectParams);
        $analysis = $statement->fetch() ?: [];
        $features['has_product_interest'] = (int) ($analysis['has_product_interest'] ?? 0) === 1;
        $features['has_appointment'] = (int) ($analysis['has_appointment'] ?? 0) === 1;

        $statement = $this->db->prepare(
            "SELECT message, created_at FROM chat_messages
             WHERE {$subjectSql} AND direction = 'user'
             ORDER BY created_at DESC LIMIT 200"
        );
        $statement->execute($subjectParams);
        $messages = $statement->fetchAll();
        $thirtyDaysAgo = time() - (30 * 86400);
        $recentMessages = array_values(array_filter(
            $messages,
            static fn(array $message): bool => (strtotime((string) ($message['created_at'] ?? '')) ?: 0) >= $thirtyDaysAgo
        ));
        $features['user_messages_30d'] = count($recentMessages);
        if ($messages !== [] && (!$lastActivity || $messages[0]['created_at'] > $lastActivity)) {
            $lastActivity = $messages[0]['created_at'];
        }
        $this->addSpamSignals($features, $recentMessages);

        $statement = $this->db->prepare(
            "SELECT MAX(created_at) last_appointment, SUM(created_at >= NOW() - INTERVAL 14 DAY) recent_appointments
             FROM chat_appointments WHERE {$subjectSql}"
        );
        $statement->execute($subjectParams);
        $appointment = $statement->fetch() ?: [];
        $features['has_appointment'] = (int) ($appointment['recent_appointments'] ?? 0) > 0;
        if (!empty($appointment['last_appointment']) && (!$lastActivity || $appointment['last_appointment'] > $lastActivity)) {
            $lastActivity = $appointment['last_appointment'];
        }

        $statement = $this->db->prepare(
            "SELECT event_type, COUNT(*) total, COUNT(DISTINCT DATE(created_at)) active_days, MAX(created_at) last_activity
             FROM user_events WHERE {$subjectSql} AND created_at >= NOW() - INTERVAL 30 DAY GROUP BY event_type"
        );
        $statement->execute($subjectParams);
        foreach ($statement->fetchAll() as $event) {
            $type = strtolower((string) $event['event_type']);
            if (in_array($type, ['product_view', 'view_product'], true)) {
                $features['product_views_30d'] += (int) $event['total'];
            }
            if ($type === 'cart_add') {
                $features['cart_adds_30d'] += (int) $event['total'];
            }
            $features['return_visits_30d'] = max($features['return_visits_30d'], (int) $event['active_days']);
            if (!$lastActivity || $event['last_activity'] > $lastActivity) {
                $lastActivity = $event['last_activity'];
            }
        }

        if ($lastActivity) {
            $features['days_since_activity'] = max(0, (int) floor((time() - strtotime((string) $lastActivity)) / 86400));
            $features['last_activity_at'] = (string) $lastActivity;
        }

        return $features;
    }

    /** @param array<string,mixed> $features @param list<array<string,mixed>> $messages */
    private function addSpamSignals(array &$features, array $messages): void
    {
        $groups = [];
        $rapid = [];
        foreach ($messages as $message) {
            $text = trim(mb_strtolower((string) ($message['message'] ?? ''), 'UTF-8'));
            $timestamp = strtotime((string) ($message['created_at'] ?? '')) ?: 0;
            if ($text !== '') {
                $groups[$text][] = $timestamp;
                if (preg_match('~(?:https?://|www\.)~i', $text) === 1) {
                    $features['suspicious_link'] = true;
                }
            }
            $rapid[] = $timestamp;
        }
        foreach ($groups as $timestamps) {
            rsort($timestamps);
            $start = $timestamps[0] ?? 0;
            $withinTenMinutes = count(array_filter($timestamps, static fn(int $time): bool => $start - $time <= 600));
            $features['duplicate_messages_10m'] = max($features['duplicate_messages_10m'], $withinTenMinutes);
        }
        rsort($rapid);
        $start = $rapid[0] ?? 0;
        $features['rapid_messages_5m'] = count(array_filter($rapid, static fn(int $time): bool => $start - $time <= 300));
    }

    /** @param array<string,mixed> $score */
    private function persist(?int $userId, ?string $sessionId, array $score, ?string $lastActivityAt = null): void
    {
        $subjectKey = $userId !== null ? 'user:' . $userId : 'session:' . $sessionId;
        $reasons = json_encode($score['reasons'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $this->db->beginTransaction();
        try {
            $current = $this->db->prepare('SELECT segment, intent_score, spam_risk_score, value_score, manual_segment FROM customer_scores_current WHERE subject_key = ? FOR UPDATE');
            $current->execute([$subjectKey]);
            $previous = $current->fetch();
            $effectiveSegment = $previous['manual_segment'] ?? $score['segment'];

            $statement = $this->db->prepare(
                "INSERT INTO customer_scores_current
                    (subject_key,user_id,session_id,intent_score,spam_risk_score,value_score,segment,value_tier,reason_codes,scoring_version,last_activity_at,calculated_at,expires_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW() + INTERVAL 30 DAY)
                 ON DUPLICATE KEY UPDATE user_id=VALUES(user_id),session_id=VALUES(session_id),
                    intent_score=VALUES(intent_score),spam_risk_score=VALUES(spam_risk_score),value_score=VALUES(value_score),
                    segment=IF(manual_segment IS NULL,VALUES(segment),manual_segment),value_tier=VALUES(value_tier),
                    reason_codes=VALUES(reason_codes),scoring_version=VALUES(scoring_version),last_activity_at=VALUES(last_activity_at),
                    calculated_at=NOW(),expires_at=VALUES(expires_at)"
            );
            $statement->execute([$subjectKey, $userId, $sessionId, $score['intent_score'], $score['spam_risk_score'], $score['value_score'], $effectiveSegment, $score['value_tier'], $reasons, self::VERSION, $lastActivityAt]);

            // customer_analysis mirrors the single scoring result so every screen shows the same label.
            [$analysisSql, $analysisParams] = $userId !== null
                ? ['user_id = ?', [$userId]]
                : ['user_id IS NULL AND session_id = ?', [$sessionId]];
            $analysis = $this->db->prepare(
                "UPDATE customer_analysis SET label=?,lead_score=?,spam_risk_score=?,score_reasons=?,scoring_version=? WHERE {$analysisSql}"
            );
            $analysis->execute([$effectiveSegment, $score['intent_score'], $score['spam_risk_score'], $reasons, self::VERSION, ...$analysisParams]);

            $changed = !$previous
                || (int) $previous['intent_score'] !== $score['intent_score']
                || (int) $previous['spam_risk_score'] !== $score['spam_risk_score']
                || (int) $previous['value_score'] !== $score['value_score']
                || (string) $previous['segment'] !== $effectiveSegment;
            if ($changed) {
                $snapshot = $this->db->prepare(
                    'INSERT INTO customer_score_snapshots (subject_key,user_id,session_id,intent_score,spam_risk_score,value_score,segment,value_tier,reason_codes,scoring_version) VALUES (?,?,?,?,?,?,?,?,?,?)'
                );
                $snapshot->execute([$subjectKey, $userId, $sessionId, $score['intent_score'], $score['spam_risk_score'], $score['value_score'], $effectiveSegment, $score['value_tier'], $reasons, self::VERSION]);
            }

            if ($userId !== null) {
                $statement = $this->db->prepare(
                    'UPDATE khachhang SET customer_label=?,lead_score=?,spam_risk_score=?,customer_value_score=?,customer_value_tier=?,label_updated_at=NOW(),scoring_version=? WHERE ID_KhachHang=?'
                );
                $statement->execute([$effectiveSegment, $score['intent_score'], $score['spam_risk_score'], $score['value_score'], $score['value_tier'], self::VERSION, $userId]);
            }

            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }
}
