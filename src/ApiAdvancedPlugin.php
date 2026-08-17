<?php

declare(strict_types=1);

namespace NimbusCMS\ApiAdvanced;

use Nimbus\Http\Request;
use Nimbus\Plugin\Plugin;
use Nimbus\Plugin\PluginContext;
use Nimbus\Plugin\PluginStorage;
use Nimbus\Support\CoreEvents;

/**
 * The official **Advanced API** plugin — a home for programmatic-access "pro"
 * features that don't belong in the lean core. The first is a **security audit
 * log** of API access failures.
 *
 * It listens to the core best-effort `api.token_rejected` and `api.access_denied`
 * events and records each into a table it owns, surfaced on an admin page. Those
 * events fire only *after* the per-IP flood guard, so a flood is already `429`'d
 * before it reaches here — the recording is bounded by the core rate limits.
 *
 * It is the **second unrelated consumer** of the plugin event + storage
 * capabilities (after Analytics), the independent proof both were waiting for.
 * Storage is taken lazily — a closure resolved at event/render time — so
 * register() runs no query and loads fine without a database.
 */
final class ApiAdvancedPlugin implements Plugin
{
    /** Matches extra.nimbus.id in composer.json. */
    public const ID = 'nimbuscms.api-advanced';

    public function register(PluginContext $context): void
    {
        $context->migrations()->register('001_audit', Schema::audit());
        $context->migrations()->register('002_audit_target', Schema::auditTarget());

        $storage  = static fn (): PluginStorage => $context->storage();
        $recorder = new AuditRecorder($storage);

        $context->events()->listen(
            CoreEvents::API_TOKEN_REJECTED,
            static function (mixed $payload) use ($recorder): void {
                $recorder->record('token_rejected', $payload);
            },
        );
        $context->events()->listen(
            CoreEvents::API_ACCESS_DENIED,
            static function (mixed $payload) use ($recorder): void {
                $recorder->record('access_denied', $payload);
            },
        );
        $context->events()->listen(
            CoreEvents::API_ENTRY_WRITTEN,
            static function (mixed $payload) use ($recorder): void {
                $recorder->record('entry_written', $payload);
            },
        );

        $log = new AuditLog($storage);
        $context->adminPages()->register(
            'api-audit',
            'API audit',
            '🛡️',
            static fn (Request $request): string => (new AuditView())->html($log->recent(), $log->summary()),
        );

        // Retention: `nimbus prune` drops audit rows older than the window
        // (API_AUDIT_RETENTION_DAYS, default 30; 0 keeps everything).
        $context->maintenance()->register('prune-audit', static function () use ($storage): int {
            $days = (int) (getenv('API_AUDIT_RETENTION_DAYS') ?: '30');
            if ($days <= 0) {
                return 0;
            }

            return $storage()->execute(
                'DELETE FROM ' . Schema::TABLE . ' WHERE occurred_at < :cutoff',
                ['cutoff' => date('Y-m-d H:i:s', (int) strtotime("-{$days} days"))],
            );
        });
    }
}
