# Changelog - Trackers

## [2.0.0-RC2] - 2026-02-01
### Added
- New Migration (m14): Added `project_total_tickets` to track ticket counts efficiently.
- Quick Reply: Now supports file attachments directly from the ticket view.
- ACP Dashboard: New "Terminal Style" Changelog viewer with remote GitHub sync.
- Admin Styles: Externalized CSS for ACP components for better maintainability.

### Fixed
- SQL Error: Resolved "Unknown column project_total_tickets" during resync.
- UI Logic: Fixed ticket history entries disappearing when adding new comments.
- Redundancy: Prevented duplicate history logs when status/severity remain unchanged.
- Ordering: Ticket responses now show newest first (DESC) while keeping original post fixed at top.

### Security
- Permission Sync: Quick Reply and Attachments now respect granular ACL permissions.