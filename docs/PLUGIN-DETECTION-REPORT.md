# Plugin Detection Report — SATORI Forms

WordPress detects SATORI Forms via the plugin bootstrap file `satori-forms.php` located in the repository root. The file contains the required plugin header fields (`Plugin Name`, `Text Domain`, `Version`, etc.), so WordPress automatically lists it under **Plugins → Installed Plugins** when the directory is placed inside `wp-content/plugins/`.

When activated, WordPress loads `satori-forms.php`, which defines core constants, loads the PSR-4 autoloader from `includes/autoloader.php`, and initialises the main `Satori\Forms\Plugin` singleton. All other components are lazy-loaded through that autoloader, ensuring WordPress can find and execute the plugin without additional bootstrap steps.
