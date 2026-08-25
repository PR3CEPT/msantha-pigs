<?php
session_start();

$host   = getenv('DB_HOST') ?: '127.0.0.1';
if ($host === 'localhost') {
    $host = '127.0.0.1';
}
$port   = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_NAME') ?: 'msantha_pigs';
$user   = getenv('DB_USER') ?: 'root';
$pass   = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET NAMES utf8mb4");

    // Auto-migrate database & tables to utf8mb4 to prevent notification question marks (?)
    try {
        $pdo->exec("ALTER DATABASE `$dbname` CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci");
    } catch (PDOException $e) {}

    // Auto-seed admin and clerk users if they don't exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
        $clerkHash = password_hash('clerk123', PASSWORD_DEFAULT);
        
        $insertStmt = $pdo->prepare("INSERT INTO users (username, password, role, full_name, phone) VALUES (?, ?, ?, ?, ?)");
        $insertStmt->execute(['admin', $adminHash, 'admin', 'System Admin', '0000000000']);
        $insertStmt->execute(['clerk', $clerkHash, 'clerk', 'Farm Clerk', '0000000000']);
    }

    // Ensure 'source' column exists in pigs table
    try {
        $pdo->query("SELECT source FROM pigs LIMIT 1");
    } catch (PDOException $e) {
        $pdo->exec("ALTER TABLE pigs ADD COLUMN source VARCHAR(50) DEFAULT 'Born on Farm'");
    }

    // Ensure 'castrated' and 'castration_date' columns exist in pigs table
    try {
        $pdo->query("SELECT castrated FROM pigs LIMIT 1");
    } catch (PDOException $e) {
        $pdo->exec("ALTER TABLE pigs ADD COLUMN castrated TINYINT(1) DEFAULT 0");
        $pdo->exec("ALTER TABLE pigs ADD COLUMN castration_date DATE NULL");
    }

    // Ensure 'weaning_date' and 'weaned_count' columns exist in breeding_records table
    try {
        $pdo->query("SELECT weaning_date FROM breeding_records LIMIT 1");
    } catch (PDOException $e) {
        $pdo->exec("ALTER TABLE breeding_records ADD COLUMN weaning_date DATE NULL");
        $pdo->exec("ALTER TABLE breeding_records ADD COLUMN weaned_count INT NULL");
    }

    // Ensure 'activity_logs' table exists
    try {
        $pdo->query("SELECT 1 FROM activity_logs LIMIT 1");
    } catch (PDOException $e) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            username VARCHAR(50) NOT NULL,
            user_role VARCHAR(20) DEFAULT 'clerk',
            action VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
        )");
    }

    // Ensure 'last_known_stage' column exists so we can detect stage transitions
    try {
        $pdo->query("SELECT last_known_stage FROM pigs LIMIT 1");
    } catch (PDOException $e) {
        $pdo->exec("ALTER TABLE pigs ADD COLUMN last_known_stage VARCHAR(20) DEFAULT NULL");
    }

    // Ensure 'notifications' table exists
    try {
        $pdo->query("SELECT 1 FROM notifications LIMIT 1");
    } catch (PDOException $e) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(30) NOT NULL DEFAULT 'info',
            title VARCHAR(120) NOT NULL,
            message TEXT NOT NULL,
            pig_id INT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }

    // Convert tables to utf8mb4_unicode_ci
    try {
        $pdo->exec("ALTER TABLE notifications CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("ALTER TABLE pigs CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("ALTER TABLE activity_logs CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // Clean up legacy question mark prefixes in existing notifications
        $pdo->exec("UPDATE notifications SET title = TRIM(BOTH '?' FROM title), message = TRIM(BOTH '?' FROM message) WHERE title LIKE '%?%' OR message LIKE '%?%'");
    } catch (PDOException $e) {}

} catch(PDOException $e) {
    die("Database Connection failed: " . $e->getMessage() . "<br>Please ensure XAMPP MySQL is running and you have imported setup.sql.");
}

// System Activity Audit Logger Function
function logActivity($pdo, $action, $description, $userId = null, $username = null, $role = null) {
    try {
        if ($userId === null && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }
        if ($username === null) {
            $username = $_SESSION['username'] ?? ($_SESSION['user_fullname'] ?? 'System User');
        }
        if ($role === null) {
            $role = $_SESSION['user_role'] ?? 'system';
        }
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if ($ip === '::1') {
            $ip = '127.0.0.1';
        }
        
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, username, user_role, action, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $username, $role, $action, $description, $ip]);
    } catch (Exception $e) {
        // Log errors locally without interrupting application flow
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

// Authentication Check function
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        die("Forbidden: Admins only.");
    }
}

// ============================================================
//  AUTO PIG STAGE — Calculated from Date of Birth (dob)
//  Stages (based on age in days from dob):
//    Piglet    : 0  – 27  days  (0 – 4 weeks)
//    Weaner    : 28 – 83  days  (4 – 12 weeks)
//    Grower    : 84 – 152 days  (3 – ~5 months)
//    Finisher  : 153 – 213 days (5 – ~7 months)
//    Adult     : 214+ days      (7+ months, breeding/sow/boar)
//
//  $PIG_STAGE_SQL  — use in SQL queries that DON'T use SELECT *
//                    (avoids duplicate column name error)
//  computePigStage() — use in PHP after a SELECT * fetch to
//                      override the stored stage column value
// ============================================================
$PIG_STAGE_SQL = "CASE
    WHEN DATEDIFF(CURDATE(), dob) < 28   THEN 'piglet'
    WHEN DATEDIFF(CURDATE(), dob) < 84   THEN 'weaner'
    WHEN DATEDIFF(CURDATE(), dob) < 153  THEN 'grower'
    WHEN DATEDIFF(CURDATE(), dob) < 214  THEN 'finisher'
    ELSE 'adult'
END";

// PHP mirror of the SQL CASE — call after SELECT * fetches
// to override the stored stage column with the live calculation.
function computePigStage($dob) {
    if (empty($dob)) return 'adult';
    try {
        $ageDays = (int)(new DateTime('today'))->diff(new DateTime($dob))->days;
        if ($ageDays < 28)  return 'piglet';
        if ($ageDays < 84)  return 'weaner';
        if ($ageDays < 153) return 'grower';
        if ($ageDays < 214) return 'finisher';
        return 'adult';
    } catch (Exception $e) {
        return 'adult';
    }
}

// Compute estimated Date of Birth from selected stage or approximate age in days
function getEstimatedDobForStage($stage, $customDays = null) {
    if ($customDays !== null && is_numeric($customDays) && (int)$customDays >= 0) {
        $days = (int)$customDays;
    } else {
        $days = match(strtolower($stage)) {
            'piglet'   => 14,   // ~2 weeks old
            'weaner'   => 56,   // ~8 weeks old
            'grower'   => 118,  // ~4 months old
            'finisher' => 180,  // ~6 months old
            default    => 240,  // ~8 months old (adult)
        };
    }
    $dt = new DateTime('today');
    $dt->modify("-{$days} days");
    return $dt->format('Y-m-d');
}

// Stage display labels & icons
define('STAGE_LABELS', [
    'piglet'   => '🐽 Piglet',
    'weaner'   => '🐖 Weaner',
    'grower'   => '📈 Grower',
    'finisher' => '🏁 Finisher',
    'adult'    => '🐗 Adult/Breeder',
]);

// ============================================================
//  PUSH NOTIFICATION HELPER
//  Creates a notification record in the notifications table.
//  type: 'info' | 'warning' | 'alert' | 'success'
// ============================================================
function pushNotification($pdo, $type, $title, $message, $pigId = null) {
    try {
        // Avoid duplicate unread notifications for the same pig+title combo
        if ($pigId !== null) {
            $dup = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE pig_id = ? AND title = ? AND is_read = 0");
            $dup->execute([$pigId, $title]);
            if ($dup->fetchColumn() > 0) return; // Already notified
        }
        $stmt = $pdo->prepare("INSERT INTO notifications (type, title, message, pig_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$type, $title, $message, $pigId]);
    } catch (Exception $e) {
        error_log("pushNotification failed: " . $e->getMessage());
    }
}

// ============================================================
//  STAGE TRANSITION DETECTOR
//  Compares each active pig's live auto-stage against the last
//  recorded stage in last_known_stage. When a pig graduates to
//  a new stage it writes an audit log entry AND a notification.
// ============================================================
function checkStageTransitions($pdo, $PIG_STAGE_SQL) {
    try {
        // Fetch all active pigs with their calculated stage
        $sql = "SELECT id, tag_no, breed, last_known_stage, ($PIG_STAGE_SQL) as current_stage FROM pigs WHERE status = 'active'";
        $pigs = $pdo->query($sql)->fetchAll();

        $stageLabels = STAGE_LABELS;

        foreach ($pigs as $pig) {
            $lks = $pig['last_known_stage'];
            $cur = $pig['current_stage'];

            // First time we see this pig — just record its stage, no notification
            if ($lks === null || $lks === '') {
                $pdo->prepare("UPDATE pigs SET last_known_stage = ? WHERE id = ?")->execute([$cur, $pig['id']]);
                continue;
            }

            // Stage changed since last check — fire audit + notification
            if ($lks !== $cur) {
                $fromLabel = $stageLabels[$lks] ?? ucfirst($lks);
                $toLabel   = $stageLabels[$cur] ?? ucfirst($cur);
                $tagNo     = $pig['tag_no'];
                $breed     = $pig['breed'] ?: 'Unknown Breed';

                // Write to activity_logs (system action, no user)
                logActivity($pdo,
                    'stage_transition',
                    "Pig #$tagNo ($breed) has graduated: $fromLabel → $toLabel (auto-detected from date of birth)",
                    null, 'System', 'system'
                );

                // Push a notification
                pushNotification($pdo,
                    'info',
                    "Stage Update: Pig #$tagNo",
                    "Pig #$tagNo ($breed) has moved from $fromLabel to $toLabel.",
                    $pig['id']
                );

                // Update last_known_stage
                $pdo->prepare("UPDATE pigs SET last_known_stage = ? WHERE id = ?")->execute([$cur, $pig['id']]);
            }
        }
    } catch (Exception $e) {
        error_log("checkStageTransitions failed: " . $e->getMessage());
    }
}

// ============================================================
//  SYSTEM ALERT GENERATOR
//  Runs on every page load. Generates notifications for:
//   - Pregnant sows overdue (farrow date passed, still 'pregnant')
//   - Pregnant sows due within 7 days
//  These are de-duplicated via pushNotification().
// ============================================================
function generateSystemAlerts($pdo) {
    try {
        // Check for breeding_records table
        $pdo->query("SELECT 1 FROM breeding_records LIMIT 1");

        // Overdue pregnant sows
        $overdue = $pdo->query("
            SELECT b.id, b.pig_id, b.expected_farrowing, p.tag_no, p.breed
            FROM breeding_records b
            JOIN pigs p ON b.pig_id = p.id
            WHERE b.status = 'pregnant' AND b.expected_farrowing < CURDATE()
        ")->fetchAll();

        foreach ($overdue as $row) {
            $days = (int)(new DateTime())->diff(new DateTime($row['expected_farrowing']))->days;
            pushNotification($pdo,
                'alert',
                "🚨 Overdue Sow: #{$row['tag_no']}",
                "Sow #{$row['tag_no']} ({$row['breed']}) is overdue by $days day(s). Expected farrowing: {$row['expected_farrowing']}. Please check immediately.",
                $row['pig_id']
            );
        }

        // Sows due within 7 days
        $dueSoon = $pdo->query("
            SELECT b.id, b.pig_id, b.expected_farrowing, p.tag_no, p.breed
            FROM breeding_records b
            JOIN pigs p ON b.pig_id = p.id
            WHERE b.status = 'pregnant'
              AND b.expected_farrowing BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ")->fetchAll();

        foreach ($dueSoon as $row) {
            $days = (int)(new DateTime($row['expected_farrowing']))->diff(new DateTime())->days;
            $days = max(0, (int)((new DateTime($row['expected_farrowing']))->diff(new DateTime('today')))->format('%r%a') * -1 + (int)((new DateTime($row['expected_farrowing']))->diff(new DateTime('today')))->days );
            $daysLeft = (int)(new DateTime('today'))->diff(new DateTime($row['expected_farrowing']))->days;
            pushNotification($pdo,
                'warning',
                "⌛ Sow Due Soon: #{$row['tag_no']}",
                "Sow #{$row['tag_no']} ({$row['breed']}) is due to farrow in $daysLeft day(s) on {$row['expected_farrowing']}. Prepare farrowing pen.",
                $row['pig_id']
            );
        }

    } catch (Exception $e) {
        // Silently skip if breeding_records table doesn't exist yet
    }
}
?>
