<?php

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use App\Kernel;

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../config/bootstrap.php')) {
    require __DIR__ . '/../config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(__DIR__ . '/../.env');
}

// Crée et boot le kernel
$kernel = new Kernel('test', true);
$kernel->boot();

// Retourne l’instance de l’application directement
return new Application($kernel);
