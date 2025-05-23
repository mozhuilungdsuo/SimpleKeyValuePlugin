


````markdown
# Simple Key-Value Plugin

**Contributors:** Lungdsuo Mozhui  
**Plugin Name:** Simple Key-Value Plugin  
**Description:** A lightweight WordPress plugin to store and display key-value pairs using a shortcode or REST API.  
**Version:** 1.0.1  
**Author URI:** [https://nerdynaga.com](https://nerdynaga.com)  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  

---

## Description

This plugin allows administrators to define and manage key-value pairs directly from the WordPress admin dashboard or through a REST API. It also provides a shortcode to display values on the frontend.

---

## Features

- Stores key-value pairs in a custom table
- Loads default keys from a CSV file on plugin activation
- REST API endpoint for updating values (`/wp-json/kvp/v1/update`)
- Admin interface to view and update values
- Shortcode `[kvp key="example_key"]` to display a value
- API key authentication for secure external updates

---

## Installation

1. Upload the plugin files to the `/wp-content/plugins/simple-key-value-plugin/` directory, or install through the WordPress Plugins screen.
2. Activate the plugin through the 'Plugins' menu.
3. Create a `default_keys.csv` file inside the plugin directory. Each line should contain one key.
4. Set your API key under **Settings → Key-Value Pairs**.

---

## Usage

### Shortcode

To display a value on any page/post:
```php
[kvp key="your_key"]
````

### REST API

**Endpoint:**
`POST /wp-json/kvp/v1/update`

**Headers:**

```
X-API-Key: YOUR_API_KEY
Content-Type: application/json
```

**Body:**

```json
{
  "key": "your_key",
  "value": "new value"
}
```

---

## Admin Settings

Navigate to **Settings → Key-Value Pairs** to:

* Set or update the API Key
* Edit the current value of default keys
* Copy shortcodes for frontend use

---

## Security

* REST API requests require an API key that must be set and stored securely in WordPress settings.
* Admin page is only accessible to users with the `manage_options` capability.

---

## Changelog

### 1.0.1

* Initial release with admin panel, REST API support, and shortcode system.

---

## License

This plugin is licensed under the GPLv2 or later.

```

