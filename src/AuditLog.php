<?php

declare(strict_types=1);

namespace NimbusCMS\ApiAdvanced;

use Closure;
use Nimbus\Plugin\PluginStorage;

/** Reads the audit table for the admin view. Storage is resolved lazily, at render time. */
final class AuditLog
{
    private const RECENT_LIMIT = 100;

    /** @var Closure():PluginStorage */
    private Closure $storage;

    /** @param callable():PluginStorage $storage */
    public function __construct(callable $storage)
    {
        $this->storage = Closure::fromCallable($storage);
    }

    /**
     * The most recent failures, newest first.
     *
     * @return list<array<string,mixed>>
     */
    public function recent(): array
    {
        return ($this->storage)()->select(
            'SELECT kind, reason, token_id, token_name, resource, target, action, ip, path, occurred_at
             FROM ' . Schema::TABLE . ' ORDER BY id DESC LIMIT ' . self::RECENT_LIMIT,
        );
    }

    /**
     * Counts by kind over the last 24 hours.
     *
     * @return array<string,int>
     */
    public function summary(): array
    {
        $rows = ($this->storage)()->select(
            'SELECT kind, COUNT(*) AS c FROM ' . Schema::TABLE . ' WHERE occurred_at >= :since GROUP BY kind',
            ['since' => date('Y-m-d H:i:s', (int) strtotime('-24 hours'))],
        );

        $summary = [];
        foreach ($rows as $row) {
            $summary[(string) $row['kind']] = (int) $row['c'];
        }

        return $summary;
    }
}
