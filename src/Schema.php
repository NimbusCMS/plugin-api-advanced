<?php

declare(strict_types=1);

namespace NimbusCMS\ApiAdvanced;

/**
 * The plugin's own append-only audit table (ADR 0005 — own tables, namespaced
 * away from core `nb_*`). One row per recorded API failure. It never stores a
 * token secret — only the id/name of an already-authenticated token, and the
 * request's IP and path.
 *
 * Rows are not self-expiring; a long-lived install should prune old entries
 * (a retention policy is a planned follow-up).
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
}
