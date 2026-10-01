# mssdocs

A web-based internal proofing system for organizing, uploading, and browsing manuscript images. Built with PHP and MySQL, featuring a file browser with gallery view, zip folder download with progress tracking, and a multi-user admin interface.

## Features

- Sign-in, a home page of module cards and user management (admin / standard roles), as one small Vue 3 shell
- File browser: folder tree, cards, list and thumbnail views, search across all folders, filters and sorting
- A details panel for a file or folder (title, description, rename, move), and for several selected items
- New folder, move, remove (administrators), undo for moves, drag-and-drop between folders, a right-click menu and keys
- Upload into the open folder, by button or by dropping files from the desktop, with a progress tray
- Zip a folder with a progress bar; administrators can edit the names, titles and descriptions of a folder's files together
- Image viewer with next/previous and rotate (for looking only)
- Optional modules: the HTML editor (`ips/html_editor/`), the TEI/XML editor, backup download

### The admin UI

No bundler: Vue 3 (the global build) and plain scripts in `ips/js/ui/`, styled by a Tailwind stylesheet compiled in advance
(`ips/css/ips-ui.css`). Nothing loads from a CDN: Vue, the Newsreader / IBM Plex fonts and the icons are in the tree.
Colours, fonts and the logo are CSS variables (`ips/css/ips-theme.css`); a site re-themes by adding `ips/css/site-theme.css`
and can add panels to the home page from `ips/js/site-home.js` (`IPS.registerHomePanel`). Rebuild the stylesheet with
`tools/build-ui-css.sh` after adding a Tailwind class, and refresh the vendored assets with `tools/vendor-ui-assets.sh`.
The file browser uses the existing `ips/json/*.php` endpoints and `upload/index.php`, so the server's path confinement and
ownership rules apply to everything it does: standard users may add, move and rename only inside `files/<their username>`.

## Requirements

- PHP 8.x with extensions: `mysqli`, `zip`, `session`
- MySQL 5.7+ or MariaDB 10.3+
- nginx (for zip download via X-Accel-Redirect)
- Sufficient disk space for uploads and temporary zip files

## Installation

### 1. Clone the repository

```bash
git clone git@github.com:RyanEiri/mssdocs.git
```

Place the repository root so that the `ips/` directory is accessible under your web root, e.g. `/var/www/myproject/`.

### 2. Configure the database

Create a MySQL database and user:

```sql
CREATE DATABASE your_db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'your_db_user'@'localhost' IDENTIFIED BY 'your_db_password';
GRANT ALL PRIVILEGES ON your_db_name.* TO 'your_db_user'@'localhost';
```

Import the schema:

```bash
mysql -u your_db_user -p your_db_name < ips/schema.sql
```

### 3. Configure the application

Copy the example config and fill in your values:

```bash
cp ips/php/classes/config.example.php ips/php/classes/config.php
```

Edit `config.php`:

```php
define("DB_HOST", "localhost");
define("DB_USER", "your_db_user");
define("DB_PASS", "your_db_password");
define("DB_NAME", "your_db_name");

define("SITE_NAME",        "Your Project Name");
define("SITE_ABBR",        "YPN");
define("SITE_DESCRIPTION", "A brief description of your project.");
define("COPYRIGHT_HOLDER", "Your Name or Organization");
define("COPYRIGHT_YEARS",  "2024");
```

### 4. Set directory permissions

The web server needs write access to the upload directories:

```bash
chown -R www-data:www-data ips/upload/files ips/upload/tmp ips/upload/archives ips/backups
```

### 5. Configure nginx

The zip download feature uses nginx's `X-Accel-Redirect` to serve files without loading them into PHP memory. Add the following to your nginx server block, adjusting the path to match your installation:

```nginx
# Internal location for zip download — served by nginx, not PHP
location /ips/upload/tmp/ {
    internal;
    alias /var/www/myproject/ips/upload/tmp/;
}
```

### 6. Create the first admin user

Insert an initial admin user directly into the database. Passwords are stored with PHP's `password_hash()` (bcrypt) and checked with `password_verify()`, so a plain SHA-512 hash will not log in. Generate the hash and the secretword:

```bash
php -r 'echo password_hash("yourpassword", PASSWORD_DEFAULT), "\n";'
openssl rand -hex 30      # 60 characters, for the secretword
```

```sql
INSERT INTO users (username, email, password, secretword, admin)
VALUES ('admin', 'admin@example.com', '<password_hash output>', '<60-char-random-string>', 1);
```

The `secretword` is a per-user random token that signs the login cookie; changing a user's password replaces it, which signs that user out.

## Security notes

- **Login cookie:** `login` is `HttpOnly`, `SameSite=Lax`, and `Secure` unless you define `SITE_ENV` (see `config.example.php`; only needed for a plain-HTTP local copy).
- **User management is CSRF-protected.** `json-add_user.php`, `json-change_user.php` and `json-remove_user.php` accept only same-origin POSTs that carry the signed-in user's token in an `X-CSRF` header; `users.php` sends it on every request (`$.ajaxSetup`). If you build your own client for those endpoints, read the token from `UserCookie::CsrfToken()` on a page the user is signed in to.
- **Guards:** an admin cannot delete their own account, the last administrator cannot be removed or demoted, and editing a user with the password fields left blank keeps their current password (and their session).

## Upgrade / update

When pulling updates from the repository, re-check `config.example.php` for any newly added constants and add them to your live `config.php`.

## License

GPL-3.0. See [LICENSE](LICENSE).

Third-party libraries included in `ips/js/` and `ips/css/` are MIT or Apache 2.0 licensed and retain their original copyright headers.
