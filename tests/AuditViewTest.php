<?php

declare(strict_types=1);

namespace NimbusCMS\ApiAdvanced\Tests;

use NimbusCMS\ApiAdvanced\AuditView;
use PHPUnit\Framework\TestCase;

final class AuditViewTest extends TestCase
{
    public function test_it_renders_recent_failures_and_a_summary(): void
    {
        $html = (new AuditView())->html(
            [[
                'kind' => 'access_denied', 'reason' => null, 'token_id' => 7, 'token_name' => 'CI',
                'resource' => 'pages', 'action' => 'read', 'ip' => '203.0.113.5',
                'path' => '/api/v1/collections/pages/entries', 'occurred_at' => '2026-08-16 10:00:00',
            ]],
            ['access_denied' => 3, 'token_rejected' => 5],
        );

        self::assertStringContainsString('API audit', $html);
        self::assertStringContainsString('<strong>5</strong> rejected tokens', $html);
        self::assertStringContainsString('CI', $html, 'the token name');
        self::assertStringContainsString('pages:read', $html, 'the denied resource:action');
    }

    public function test_it_escapes_untrusted_values(): void
    {
        $html = (new AuditView())->html(
            [['kind' => 'token_rejected', 'token_name' => '<script>', 'ip' => 'x', 'path' => '/a"<b>', 'occurred_at' => 't']],
            [],
        );

        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_an_empty_log_reads_gracefully(): void
    {
        $html = (new AuditView())->html([], []);

        self::assertStringContainsString('No API failures recorded', $html);
        self::assertStringContainsString('<strong>0</strong> rejected tokens', $html);
    }
}
