<?php

declare(strict_types=1);

namespace NimbusCMS\ApiAdvanced;

/**
 * Renders the audit page's inner HTML (the admin shell wraps it). Pure and
 * database-free, so it is unit-testable. Every value is escaped — the stored
 * data (token names, paths) originates from untrusted requests.
 */
final class AuditView
{
    private const LABELS = [
        'token_rejected' => 'Token rejected',
        'access_denied'  => 'Access denied',
        'entry_written'  => 'Entry written',
        'management'     => 'Management',
    ];

    /**
     * @param list<array<string,mixed>> $recent
     * @param array<string,int>         $summary counts by kind, last 24h
     */
    public function html(array $recent, array $summary): string
    {
        $e = static fn (mixed $value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');

        $html = '<div class="nb-page-head"><h1>API audit</h1></div>';

        $rejected = $summary['token_rejected'] ?? 0;
        $denied   = $summary['access_denied'] ?? 0;
        $writes   = $summary['entry_written'] ?? 0;
        $managed  = $summary['management'] ?? 0;
        $html .= '<p class="nb-muted">Last 24 hours: '
            . '<strong>' . $rejected . '</strong> rejected token' . ($rejected === 1 ? '' : 's') . ', '
            . '<strong>' . $denied . '</strong> scope denial' . ($denied === 1 ? '' : 's') . ', '
            . '<strong>' . $writes . '</strong> write' . ($writes === 1 ? '' : 's') . ', '
            . '<strong>' . $managed . '</strong> management action' . ($managed === 1 ? '' : 's') . '.</p>';

        if ($recent === []) {
            $html .= '<div class="nb-empty-panel"><span class="nb-empty-ic">🛡️</span>'
                . '<h2>Nothing recorded yet</h2>'
                . '<p>API writes, rejected tokens, and out-of-scope requests will appear here.</p></div>';

            return $html;
        }

        $html .= '<table class="nb-table"><thead><tr>'
            . '<th>When</th><th>Kind</th><th>Detail</th><th>Target</th><th>Token</th><th>IP</th>'
            . '</tr></thead><tbody>';

        foreach ($recent as $row) {
            $kind   = (string) ($row['kind'] ?? '');
            $detail = match ($kind) {
                'access_denied', 'entry_written', 'management' => $e($row['resource'] ?? '') . ':' . $e($row['action'] ?? ''),
                default                                        => $e($row['reason'] ?? ''),
            };
            $target = ($row['target'] ?? '') !== '' ? $e($row['target']) : '<span class="nb-muted">—</span>';
            $token  = ($row['token_name'] ?? '') !== '' ? $e($row['token_name']) : '<span class="nb-muted">—</span>';

            $html .= '<tr>'
                . '<td>' . $e($row['occurred_at'] ?? '') . '</td>'
                . '<td>' . $e(self::LABELS[$kind] ?? $kind) . '</td>'
                . '<td>' . $detail . '</td>'
                . '<td>' . $target . '</td>'
                . '<td>' . $token . '</td>'
                . '<td>' . $e($row['ip'] ?? '') . '</td>'
                . '</tr>';
        }

        return $html . '</tbody></table>';
    }
}
