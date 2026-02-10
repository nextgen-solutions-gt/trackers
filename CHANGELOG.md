# Changelog - Trackers

## [1.0.0-RC4] - 2026-02-09

### Added
- **My Tickets Operator**: Full implementation of the `my_tickets` logic to allow users to view their own reported tickets in a centralized list.
- **User Menu Integration**: Added a direct link to "My Tickets" within the forum's user header menu for registered members.
- **Ticket Reporting System**: Full implementation of the reporting core, allowing users to flag tickets for moderator review.
- **Ticket Relocation**: Implemented functionality to move tickets between different trackers.
- **User Unassignment**: Added the option to unassign a user from a ticket directly from the management interface.
- **Ticket Subscriptions**: Users can now subscribe to specific tickets to stay updated on progress.
- **New Notification Types**: 
  - Notifications for reported tickets (Moderators).
  - Notifications for updates on subscribed tickets (Users).
- **UCP/MCP Modules**: 
  - Added Ticket Subscription management module.
  - Added Ticket Tracking Report module.
- **Enhanced Quick Reply**: 
  - Integrated Smilies support.
  - Integrated BBCode support.
- **Interaction Tools**: 
  - Added "Quote" message button in ticket threads.
  - Added "Close Ticket" button for authorized users.
- **Structural Overhaul**: Implemented a new hierarchical Tracker and Project system for better data organization.

### Fixed
- **Service Container**: Resolved `ServiceNotFoundException` errors by refactoring the dependency injection in `services.yml`.
- **Notification Persistence**: Fixed issues where orphan notifications would crash the board by implementing a cleaner database purge on extension updates.
- **Language Loading**: Improved the event listener to ensure `report.php` and `common.php` are loaded across all tracker-related pages.

### Technical Changes
- **Database Schema**: Updated `phpbb_trackers_ticket` table with `timestamp_created` and `status_id` for better indexing.
- **PSR-4 Compliance**: Standardized namespaces for notification types to ensure compatibility with Symfony's service container.

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
