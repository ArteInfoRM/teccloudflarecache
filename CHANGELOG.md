# Changelog

All notable changes to this project are documented in this file.

## [Unreleased]

### Fixed

- Do not send an empty request body with Cloudflare API GET requests.
- Explain when the current storefront is not served through the configured Cloudflare zone.
- Reject Back Office rules when the current shop host does not belong to the selected zone.

### Added

- Optional public cache status URL for Docker and staging environments.

### Changed

- Document the minimum zone-scoped Cloudflare token permissions.
- Clarify that account-level Rulesets and Filter Lists permissions are not used.

## [1.0.0] - 2026-08-24

### Added

- Initial Cloudflare cache purge and monitor module.
- Optional purge synchronization with the PrestaShop cache-clear hook.
- Minimal Cache Rule manager for Back Office and configured URL bypasses.
