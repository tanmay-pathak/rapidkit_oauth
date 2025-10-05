# Google OAuth Recipe for Drupal

This recipe sets up **Google OAuth login** for your Drupal site using the [OpenID Connect](https://www.drupal.org/project/openid_connect) module and the Google client plugin.

All required modules and configurations are automatically handled during recipe installation.
You only need to provide the necessary **environment variables**.

---
## Installation

Add the following under repositories in `composer.json`:

```json
{
  "type": "vcs",
  "url": "https://github.com/tanmay-pathak/rapidkit_oauth.git"
}
```

Install the recipe:

```shell
lando composer require drupal/rapidkit_oauth
```

Unpack the recipe (optional - uses [module](https://github.com/woredeyonas/Drupal-Recipe-Unpack)):

```shell
lando composer unpack drupal/rapidkit_oauth
```

Apply the recipe:

```shell
lando drush recipe ../recipes/rapidkit_oauth
```

## 🧩 Environment Variables

Add the following variables to your project’s `.env` file:

```bash
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_ALLOWED_DOMAINS=
```

### Description

* **GOOGLE_CLIENT_ID** → Your OAuth 2.0 Client ID from Google Cloud Console
* **GOOGLE_CLIENT_SECRET** → Your OAuth 2.0 Client Secret
* **GOOGLE_ALLOWED_DOMAINS** → Comma-separated list of allowed email domains (e.g. `example.com,zu.com`)

---

## ⚙️ Drupal Settings

Add the following snippet to your `settings.php` file to load the environment variables into Drupal:

```php
/**
 * OpenID Connect
 */
$config['openid_connect.client.google']['settings']['client_id'] = $_ENV['GOOGLE_CLIENT_ID'];
$config['openid_connect.client.google']['settings']['client_secret'] = $_ENV['GOOGLE_CLIENT_SECRET'];
$config['openid_connect.client.google']['settings']['iss_allowed_domains'] = $_ENV['GOOGLE_ALLOWED_DOMAINS'];
```
