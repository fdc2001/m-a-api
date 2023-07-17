# Micro API to sync data from orbit

## Installation


### Install dependencies
```bash
composer install
```

### Create .env file
```bash
cp .env.example .env
```

### Generate application key
```bash
    php -r "echo bin2hex(random_bytes(16));"
```
and paste it in .env file in APP_KEY variable

### Run migrations
```bash
php artisan migrate
```
Utils commands
```bash
php artisan wordpress:key 
```
This command will generate a key for WordPress to use in the api

```bash
php artisan orbit:key 
```
This command will generate a key for orbit to use in the api
