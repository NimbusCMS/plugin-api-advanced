<?php

declare(strict_types=1);

namespace NimbusCMS\ApiAdvanced;

/**
 * The plugin's own append-only audit table (ADR 0005 — own tables, namespaced
 * away from core `nb_*`). One row per recorded API event — a failure (rejected
 * token / scope denial) or a write (create/update/delete). It never stores a
 * token secret, only the id/name of an already-authenticated token, plus the
 * request's IP and path (and, for a write, which entry). Retention is handled by
 * `nimbus prune` (see ApiAdvancedPlugin).
 */
final class Schema
{
    public const TABLE = 'api_audit_log';

    /** @return list<string> */
    public static function audit(): array
    {
        return [
            'CREATE TABLE ' . self::TABLE . ' (
                id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                kind        VARCHAR(32)  NOT NULL,
                reason      VARCHAR(32)  NULL,
                token_id    INT UNSIGNED NULL,
                token_name  VARCHAR(120) NULL,
                resource    VARCHAR(191) NULL,
                action      VARCHAR(32)  NULL,
                ip          VARCHAR(45)  NULL,
                path        VARCHAR(191) NULL,
                occurred_at DATETIME     NOT NULL,
                INDEX idx_occurred (occurred_at),
                INDEX idx_kind (kind)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        ];
    }

    /**
     * Adds the entry a write touched (its slug), for the write-audit trail.
     *
     * @return list<string>
     */
    public static function auditTarget(): array
    {
        return ['ALTER TABLE ' . self::TABLE . ' ADD COLUMN target VARCHAR(191) NULL AFTER resource'];
    }
}
