<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

Approach steps:
- composer require laravel/sail --dev
- php artisan sail:install
- cp .env.example .env
- php artisan key:generate
- php artisan storage:link
- composer require laravel/sanctum
- composer install
- composer require -W --dev laravel-shift/blueprint
- composer require guzzlehttp/guzzle
- composer require pusher/pusher-php-server
- npm install --save laravel-echo pusher-js
- npm install axios
- npm install
- 
- php artisan install:broadcasting
