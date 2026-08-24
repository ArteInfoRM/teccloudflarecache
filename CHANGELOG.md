# Changelog

All notable changes to this project are documented in this file.

## [1.0.2] - 2026-08-24

### Fixed

- Create Back Office cache bypass rules with the current store host and base path.
- Match both the Back Office directory URL and its nested paths.

## [1.0.1] - 2026-08-24

### Fixed

- Align all PHP license headers with PrestaShop validator ordering requirements.
- Align PHP file headers and visibility modifiers with PrestaShop validator requirements.
- Simplify Cache Rules payload handling for static-analysis compatibility.

## [1.0.0] - 2026-08-24

### Added

- Initial Cloudflare cache purge and monitor module.
- Optional purge synchronization with the PrestaShop cache-clear hook.
- Minimal Cache Rule manager for Back Office and configured URL bypasses.
- Optional public cache status URL for Docker and staging environments.

### Changed

- Document the minimum zone-scoped Cloudflare token permissions.
- Clarify that account-level Rulesets and Filter Lists permissions are not used.

### Fixed

- Do not send an empty request body with Cloudflare API GET requests.
- Explain when the current storefront is not served through the configured Cloudflare zone.
- Reject Back Office rules when the current shop host does not belong to the selected zone.
