# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project aims
to follow [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **API security audit log.** Listens to core's `api.token_rejected` and
  `api.access_denied` events and records each refused API request into a table
  the plugin owns — the reason/IP/path for a rejection, and the token id/name and
  `resource:action` for a scope denial (never the presented token). An **API
  audit** admin page shows a 24-hour summary and the most recent failures.
- Retention: a `nimbus prune` maintenance task drops audit rows older than
  `API_AUDIT_RETENTION_DAYS` (default 30; `0` keeps everything).
