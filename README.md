# Copy Image URL for Piwigo

Small Piwigo plugin that adds copy-to-clipboard buttons on the public photo page for:

- the direct URL of the original image
- the XL derivative URL, when available

It is useful when another application (GIS, asset management, intranet, etc.) needs a public image URL rather than a link to the Piwigo photo page.

## Compatibility

- Piwigo 16.x
- PHP 8.x

Test target: Piwigo 16.4.0 / PHP 8.4.

## Installation

Copy the plugin directory to Piwigo's `plugins/piwigo-copy-image-url/` directory, then activate **Copy Image URL** in **Administration > Plugins**.

## Usage

Open a photo in the public gallery. The plugin displays buttons to copy the original image URL and, when available, the XL derivative URL.

## Notes

- The plugin does not modify Piwigo core files.
- The plugin does not create or modify database tables.
- On HTTPS galleries it uses the modern Clipboard API.
- On HTTP galleries it falls back to the legacy browser copy mechanism when possible.

## License

MIT
