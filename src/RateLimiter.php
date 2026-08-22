<?php

require_once __DIR__ . '/Database.php';

class RateLimiter {
    /** @var PDO */
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureTable();
    }

    private function ensureTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS rate_limits (
            key_identifier TEXT PRIMARY KEY,
            tokens REAL NOT NULL,
            last_updated REAL NOT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $this->db->exec($sql);
    }

    /**
     * Check if a request is allowed using the Token Bucket algorithm
     *
     * @param string $key Unique identifier (e.g. source_id or IP)
     * @param int $maxRequests Capacity of bucket (e.g. 60 requests)
     * @param int $windowSeconds Window in seconds (e.g. 60 seconds)
     * @return array [allowed, limit, remaining, reset, retry_after]
     */
    public function check(string $key, int $maxRequests = 60, int $windowSeconds = 60): array {
        $now = microtime(true);
        $fillRate = $maxRequests / $windowSeconds; // tokens added per second

        $stmt = $this->db->prepare("SELECT tokens, last_updated FROM rate_limits WHERE key_identifier = :key");
        $stmt->bindValue(':key', $key, PDO::PARAM_STR);
        $stmt->execute();
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            // First time seeing this key: create with full bucket minus 1 token
            $tokens = $maxRequests - 1;
            $ins = $this->db->prepare("INSERT INTO rate_limits (key_identifier, tokens, last_updated) VALUES (:key, :tokens, :now)");
            $ins->bindValue(':key', $key, PDO::PARAM_STR);
            $ins->bindValue(':tokens', $tokens, PDO::PARAM_STR);
            $ins->bindValue(':now', $now, PDO::PARAM_STR);
            $ins->execute();

            return [
                'allowed' => true,
                'limit' => $maxRequests,
                'remaining' => (int)floor($tokens),
                'reset' => (int)ceil($windowSeconds),
                'retry_after' => 0
            ];
        }

        $lastUpdated = (float)$record['last_updated'];
        $oldTokens = (float)$record['tokens'];

        // Add leaked/refilled tokens based on time elapsed
        $elapsed = max(0, $now - $lastUpdated);
        $newTokens = min($maxRequests, $oldTokens + ($elapsed * $fillRate));

        if ($newTokens >= 1.0) {
            // Request allowed: consume 1 token
            $newTokens -= 1.0;
            $upd = $this->db->prepare("UPDATE rate_limits SET tokens = :tokens, last_updated = :now, updated_at = CURRENT_TIMESTAMP WHERE key_identifier = :key");
            $upd->bindValue(':tokens', $newTokens, PDO::PARAM_STR);
            $upd->bindValue(':now', $now, PDO::PARAM_STR);
            $upd->bindValue(':key', $key, PDO::PARAM_STR);
            $upd->execute();

            return [
                'allowed' => true,
                'limit' => $maxRequests,
                'remaining' => (int)floor($newTokens),
                'reset' => (int)ceil(($maxRequests - $newTokens) / $fillRate),
                'retry_after' => 0
            ];
        } else {
            // Rate limit exceeded: do not consume, update timestamp and calculate retry_after
            $timeUntilNextToken = (1.0 - $newTokens) / $fillRate;
            return [
                'allowed' => false,
                'limit' => $maxRequests,
                'remaining' => 0,
                'reset' => (int)ceil(($maxRequests - $newTokens) / $fillRate),
                'retry_after' => (int)ceil($timeUntilNextToken)
            ];
        }
    }

    /**
     * Set standard rate limit HTTP headers
     */
    public static function setHeaders(array $result): void {
        header("X-RateLimit-Limit: " . $result['limit']);
        header("X-RateLimit-Remaining: " . $result['remaining']);
        header("X-RateLimit-Reset: " . $result['reset']);
        if (!$result['allowed'] && isset($result['retry_after']) && $result['retry_after'] > 0) {
            header("Retry-After: " . $result['retry_after']);
        }
    }
}
