# Google OAuth Recipe for Drupal

This recipe sets up **Google OAuth login** for your Drupal site using the [OpenID Connect](https://www.drupal.org/project/openid_connect) module and the Google client plugin.

All required modules and configurations are automatically handled during recipe installation.
You only need to provide the necessary **environment variables**.

Note: The custom code part of this recipe gives the admin role to all *zu.com* email addresses.

---
## Installation

Add the following under repositories in `composer.json`:

```json
{
  "type": "vcs",
  "url": "https://github.com/tanmay-pathak/rapidkit_oauth.git"
}
```

Install & enable the recipe code installer module:

```shell
lando composer require drupal/recipe_code_installer --ignore-platform-req=php && lando drush en recipe_code_installer
```

Install the recipe:

```shell
lando composer require drupal/rapidkit_oauth --ignore-platform-req=php
```

Unpack the recipe (optional - uses [module](https://github.com/woredeyonas/Drupal-Recipe-Unpack)):

```shell
lando composer unpack drupal/rapidkit_oauth
```

Enable `openid_connect`:

```shell
lando drush en openid_connect -y
```

Apply the recipe:

```shell
lando drush recipe ../recipes/rapidkit_oauth
```

Remove the recipe code installed:

```shell
lando drush pmu recipe_code_installer -y && lando composer remove drupal/recipe_code_installer
```

## 🧩 Environment Variables

Add the following variables to your project’s `.env` file:

```bash
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
```

### Description

* **GOOGLE_CLIENT_ID** → Your OAuth 2.0 Client ID from Google Cloud Console
* **GOOGLE_CLIENT_SECRET** → Your OAuth 2.0 Client Secret

---

## ⚙️ Drupal Settings

Add the following snippet to your `settings.php` file to load the environment variables into Drupal:

```php
/**
 * OpenID Connect
 */
$config['openid_connect.client.google']['settings']['client_id'] = $_ENV['GOOGLE_CLIENT_ID'];
$config['openid_connect.client.google']['settings']['client_secret'] = $_ENV['GOOGLE_CLIENT_SECRET'];
```

## 🚀 Custom Login Route

* **`/user/zu`** → Initiates the Google OAuth login flow directly.
