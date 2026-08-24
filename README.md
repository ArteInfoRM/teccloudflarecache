# Tec Cloudflare Cache

Tec Cloudflare Cache is a PrestaShop 1.7 to 9 module by Tecnoacquisti.com®.
It provides Cloudflare cache purge, connection verification, safe synchronization
with the PrestaShop cache-clear hook, and an optional minimal Cache Rule manager.

## Requirements

- PrestaShop 1.7.0 or later.
- A Cloudflare API Token scoped to the selected zone.
- `Zone > Zone > Read` to verify the selected zone.
- `Zone > Cache Purge > Edit` for purge operations.
- `Zone > Cache Rules > Edit` to apply bypass rules.

The module does not use account-level Rulesets or Filter Lists. Restrict the
token to the single Cloudflare zone managed by this PrestaShop installation.

## Safety model

Cloudflare operations are optional. If credentials are absent or an API request
fails, PrestaShop cache clearing continues normally. The module records only
short diagnostic outcomes in the PrestaShop log and never logs API tokens.

The Rule Manager only creates rules identified with the `teccloudflarecache:`
prefix and preserves other Cloudflare Cache Rules. Cloudflare Free allows ten
Cache Rules in total; the module validates the available capacity before it
submits changes.

## Configuration

Enter the API Token, Zone ID, and zone name. The token is rendered as a mask
after saving. Enable synchronization only after the connection test succeeds.

### Cache status monitoring

The Test storefront cache action reads the `CF-Cache-Status` and `Age` response
headers. For a local Docker installation, the PrestaShop hostname may not be
proxied through Cloudflare. In that case, set Cache status URL to an absolute,
public URL in the selected Cloudflare zone, for example:

```text
https://www.example.com/
```

If Cloudflare does not serve the configured URL, the module reports that the
cache status header is unavailable instead of treating this as a module error.

To exclude URLs from cache, enter one absolute URL or trailing-path wildcard
per line, for example:

```text
https://www.example.com/mag/*
https://www.example.com/private-document.pdf
```

The host must match the configured zone or one of its subdomains. Query strings,
fragments, credentials, ports, and wildcard characters outside the final path
position are rejected.

The optional Back Office exclusion is only applied when the current PrestaShop
hostname belongs to the configured Cloudflare zone. This prevents a local or
staging store from changing rules for an unrelated production zone.
