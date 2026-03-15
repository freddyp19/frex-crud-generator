# Frex CRUD Generator

`frex/crud-generator` is your internal fork of `ibex/crud-generator`, adapted to generate CRUD modules with DataTables for your Laravel SaaS projects.

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

## Config

The package publishes `config/frex-crud.php`.

For migration safety, the generator also falls back to the legacy `config/crud.php` keys if they still exist in older projects.

## Notes

- Relation discovery is database-driven.
- Select fields for related models now try to use a readable column first and fall back safely when needed.
- The package remains close to your modified fork so replacement in current SaaS projects is straightforward.
"# frex-crud-generator" 
