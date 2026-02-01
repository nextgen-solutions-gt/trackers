# Changelog - Trackers

## [1.0.0-RC3] - 2026-02-01
### Added
- **Auto-Sync Stats**: Dashboard now automatically synchronizes ticket counters on page load to ensure data integrity.
- **ACP Version Status**: Implementation of "Success" green banner when the extension is fully up to date.

### Fixed
- **Validation Bug**: Fixed issue where the browser forced users to fill the title field when clicking "Cancel" during ticket creation.
- **DI Constructor**: Resolved fatal TypeError in the posting operator by aligning service arguments with services.yml.
- **Version Normalization**: Fixed comparison logic to ignore 'v' prefixes in remote GitHub versions.
- **Undefined Properties**: Removed PHP notices regarding undefined path variables ($root_path, $php_ext).
- **Button Symmetry**: Adjusted CSS styling for the Cancel button (anchor) to maintain visual symmetry with the Submit button.

## [1.0.0-RC2] - 2026-02-01
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