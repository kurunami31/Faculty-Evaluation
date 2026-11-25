<?php

declare(strict_types=1);

namespace App\Shared;

/**
 * Append-only audit logger.
 *
 * Writes structured JSONL records to storage/logs/audit.log.
 * Records are immutable once written (no update/delete).
 */
final class AuditLogger
{
    private string $logPath;

    public function __construct(?string $logPath = null)
    {
        $this->logPath = $logPath ?? sys_get_temp_dir() . "/faculty_eval_audit/audit.log";
        $dir = dirname($this->logPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    /**
     * Log an audit event.
     *
     * @param array<string, mixed> $context
     */
    public function log(
        ?int $userId,
        string $action,
        string $entityType,
        ?int $entityId,
        ?array $oldValues,
        ?array $newValues,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        $record = [
            "ts" => (new \DateTimeImmutable())->format("Y-m-d\TH:i:s.vP"),
            "user_id" => $userId,
            "action" => $action,
            "entity_type" => $entityType,
            "entity_id" => $entityId,
            "old_values" => $oldValues,
            "new_values" => $newValues,
            "ip_address" => $ipAddress ?? ($_SERVER["REMOTE_ADDR"] ?? null),
            "user_agent" => $userAgent ?? ($_SERVER["HTTP_USER_AGENT"] ?? null),
        ];

        $line = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

        $fp = @fopen($this->logPath, "a");
        if ($fp === false) {
            // Fall back to error_log so audit trail is never silently lost
            error_log("AUDIT_LOG_WRITE_FAILED: " . $line);
            return;
        }

        flock($fp, LOCK_EX);
        fwrite($fp, $line);
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    public function getLogPath(): string
    {
        return $this->logPath;
    }
}