# Frex CRUD Generator

`frex/crud-generator` is a Laravel CRUD generator with Yajra DataTables support, maintained by `freddyp19`.

It is based on `ibex/crud-generator`, extended to better support DataTables-based admin modules and reusable CRUD scaffolding across Laravel projects.

Generated artifacts:

- Eloquent model with inferred relations
- resource controller
- Bootstrap views
- Yajra DataTable class
- DataTable index view with SweetAlert delete flow

## Intended projects

## Usage

Generate a CRUD from an existing database table:

```bash
php artisan make:crud banks
```

Custom route:

```bash
php artisan make:crud banks --route=banks
```

Then register the resource route in the target Laravel app:

```php
Route::resource('banks', BankController::class);
```

In Laravel projects that still use `RouteServiceProvider::$namespace`, register the route with the legacy string syntax instead:

```php
Route::resource('banks', 'BankController');
```

## Requirements

This fork keeps the original generator strategy, so generated files assume:

- Laravel with MySQL or MariaDB
- `laravelcollective/html`
- `yajra/laravel-datatables-*`
- `realrashid/sweet-alert`

## Local installation

If the package lives in a sibling folder or monorepo, add a path repository in the Laravel application:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../frex-crud-generator"
    }
  ]
}
```

Install it:

```bash
composer require frex/crud-generator --dev
php artisan vendor:publish --tag=frex-crud-config
```

## Package source

- Repository: `https://github.com/freddyp19/frex-crud-generator`
- Composer package: `frex/crud-generator`

## Config

The package publishes `config/frex-crud.php`.

For migration safety, the generator also falls back to the legacy `config/crud.php` keys if they still exist in older projects.

## Notes

- Relation discovery is database-driven.
- Select fields for related models now try to use a readable column first and fall back safely when needed.
- The package remains close to your modified fork so replacement in current SaaS projects is straightforward.
"# frex-crud-generator" 
