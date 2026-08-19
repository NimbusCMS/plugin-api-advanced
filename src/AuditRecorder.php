<?php

declare(strict_types=1);

namespace NimbusCMS\ApiAdvanced;

use Closure;
use Nimbus\Plugin\PluginStorage;

/**
 * Records one API failure per core event.
 *
 * The pure mapping from an event payload to a stored row is `entry()` —
 * unit-testable without a database; `record()` just runs the insert. Storage is
 * resolved lazily so the plugin loads without a database and does no work until
 * an event actually fires.
 */
final class AuditRecorder
{
    /** @var Closure():PluginStorage */
    private Closure $storage;

    /** @param callable():PluginStorage $storage resolved lazily, when an event fires */
    public function __construct(callable $storage)
    {
        $this->storage = Closure::fromCallable($storage);
    }

    public function record(string $kind, mixed $payload): void
    {
        $row = $this->entry($kind, $payload);
        if ($row === null) {
            return;
        }

        ($this->storage)()->insert(
            'INSERT INTO ' . Schema::TABLE . '
                (kind, reason, token_id, token_name, resource, target, action, ip, path, occurred_at)
             VALUES (:kind, :reason, :token_id, :token_name, :resource, :target, :action, :ip, :path, :at)',
            $row,
        );
    }

    /**
     * Map an event payload to a stored row, or null to skip a malformed one.
     * Handles failure payloads (which carry `resource`), content-write payloads
     * (`collection` + `slug`), and management payloads (`capability` + `target`).
     *
     * @return array<string,mixed>|null
     */
    public function entry(string $kind, mixed $payload): ?array
    {
        if (!is_array($payload)) {
            return null;
        }

        $str = static fn (string $key): ?string => isset($payload[$key]) && is_scalar($payload[$key]) ? (string) $payload[$key] : null;
        $int = static fn (string $key): ?int => isset($payload[$key]) && is_numeric($payload[$key]) ? (int) $payload[$key] : null;

        return [
            'kind'       => $kind,
            'reason'     => $str('reason'),
            'token_id'   => $int('token_id'),
            'token_name' => $str('token_name'),
            'resource'   => $str('resource') ?? $str('collection') ?? $str('capability'),
            'target'     => $str('target') ?? $str('slug'),
            'action'     => $str('action'),
            'ip'         => $str('ip'),
            'path'       => $str('path'),
            'at'         => $str('at') ?? date('Y-m-d H:i:s'),
        ];
    }
}
