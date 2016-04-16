![Lexiglot](/logo.png?raw=true)

A translation platform for PHP projects developed in PHP.

## Secure your installation

* The application should not run on a shared server were users have shell access, to prevent SVN credentials leak from `ps` command
* You must disable remote access to `api/update.php` file, this file must only called from trusted sources (Cron task, SVN hook, etc.)
* You should disable remote access to `local/` folder and `update.log` file if you host private projects
