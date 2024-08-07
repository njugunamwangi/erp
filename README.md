## About Project

A simple ERP project built with Laravel & FilamentPHP

## Tech Stack

- [Laravel](https://laravel.com).
- [Livewire](https://livewire.laravel.com).
- [TailwindCSS](https://tailwindcss.com).
- [FilamentPHP V3](https://filamentphp.com).

## Installation guide

- Clone the repository

```bash
git clone https://github.com/njugunamwangi/erp.git
```
- On the root of the directory, copy and paste .env.example onto .env and configure the database accordingly
 ```bash
copy .env.example .env
```

- Run migrations and seed the database
```bash
php artisan migrate --seed
```

- Install composer dependencies by running composer install
 ```bash
composer install
```

- Install npm dependencies
```bash
npm install
```

- Generate laravel application key using 
```bash
php artisan key:generate
```

- Don't forget to run the application
```bash
npm run dev
```

- Start reverb for the newly installed FilaChat
```bash
php artisan reverb:start
```

## Routes

- [Admin Panel](https://admin.filament-multitenancy.test.com).
- [App Panel](https://app.filament-multitenancy.test.com).

## Prerequisites

- Admin panel credentials

```bash
email: info@ndachi.dev
password: Password
```

- Exchange Rate API Key

Get the key here

```bash
https://app.exchangerate-api.com/dashboard
```

And paste it here

```bash
https://admin.erp.test/defaults/profile
```
