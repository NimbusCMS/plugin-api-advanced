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
        $html .= '<p class="nb-muted">Last 24 hours: '
            . '<strong>' . $rejected . '</strong> rejected token' . ($rejected === 1 ? '' : 's') . ', '
            . '<strong>' . $denied . '</strong> scope denial' . ($denied === 1 ? '' : 's') . '.</p>';

        if ($recent === []) {
            $html .= '<div class="nb-empty-panel"><span class="nb-empty-ic">🛡️</span>'
                . '<h2>No API failures recorded</h2>'
                . '<p>Rejected tokens and out-of-scope requests to the API will appear here.</p></div>';

            return $html;
        }

        $html .= '<table class="nb-table"><thead><tr>'
            . '<th>When</th><th>Kind</th><th>Detail</th><th>Token</th><th>IP</th><th>Path</th>'
            . '</tr></thead><tbody>';

        foreach ($recent as $row) {
            $kind   = (string) ($row['kind'] ?? '');
            $detail = $kind === 'access_denied'
                ? $e($row['resource'] ?? '') . ':' . $e($row['action'] ?? 'read')
                : $e($row['reason'] ?? '');
            $token = $row['token_name'] !== null && $row['token_name'] !== ''
                ? $e($row['token_name'])
                : '<span class="nb-muted">—</span>';

            $html .= '<tr>'
                . '<td>' . $e($row['occurred_at'] ?? '') . '</td>'
                . '<td>' . $e(self::LABELS[$kind] ?? $kind) . '</td>'
                . '<td>' . $detail . '</td>'
                . '<td>' . $token . '</td>'
                . '<td>' . $e($row['ip'] ?? '') . '</td>'
                . '<td>' . $e($row['path'] ?? '') . '</td>'
                . '</tr>';
        }

        return $html . '</tbody></table>';
    }
}
