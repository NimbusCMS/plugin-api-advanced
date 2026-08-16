<?php

declare(strict_types=1);

namespace NimbusCMS\ApiAdvanced\Tests;

use Nimbus\Plugin\PluginStorage;
use NimbusCMS\ApiAdvanced\AuditRecorder;
use PHPUnit\Framework\TestCase;

/**
 * The pure payload → row mapping. Storage is never resolved here, so the
 * resolver deliberately throws if anything tries to touch a database.
 */
final class AuditRecorderTest extends TestCase
{
    private AuditRecorder $recorder;

    protected function setUp(): void
    {
        $this->recorder = new AuditRecorder(static fn (): PluginStorage => throw new \RuntimeException('storage must not be touched'));
    }

    public function test_it_maps_a_token_rejection_without_a_token(): void
    {
        $row = $this->recorder->entry('token_rejected', [
            'reason' => 'invalid', 'ip' => '203.0.113.5', 'path' => '/api/v1/x', 'at' => '2026-08-16 10:00:00',
        ]);

        self::assertIsArray($row);
        self::assertSame('token_rejected', $row['kind']);
        self::assertSame('invalid', $row['reason']);
        self::assertSame('203.0.113.5', $row['ip']);
        self::assertNull($row['token_id'], 'a rejection has no resolved token');
        self::assertNull($row['token_name']);
    }

    public function test_it_maps_a_scope_denial_with_the_token(): void
    {
        $row = $this->recorder->entry('access_denied', [
            'token_id' => 7, 'token_name' => 'CI', 'resource' => 'pages', 'action' => 'read',
            'ip' => '203.0.113.5', 'path' => '/api/v1/pages',
        ]);

        self::assertIsArray($row);
        self::assertSame('access_denied', $row['kind']);
        self::assertSame(7, $row['token_id']);
        self::assertSame('CI', $row['token_name']);
        self::assertSame('pages', $row['resource']);
        self::assertSame('read', $row['action']);
    }

    public function test_it_defaults_the_timestamp_when_absent(): void
    {
        $row = $this->recorder->entry('token_rejected', ['reason' => 'missing']);

        self::assertIsArray($row);
        self::assertNotNull($row['at']);
    }

    public function test_a_non_array_payload_is_skipped(): void
    {
        self::assertNull($this->recorder->entry('token_rejected', 'nonsense'));
        self::assertNull($this->recorder->entry('token_rejected', null));
    }
}
