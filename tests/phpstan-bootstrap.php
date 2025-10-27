<?php

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/../vendor/autoload.php';

if (file_exists(__DIR__.'/../config/bootstrap.php')) {
    require __DIR__.'/../config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(__DIR__.'/../.env');
}

// Détecte si on est sur GitHub Actions
$isGithub = getenv('GITHUB_ACTIONS') === 'true';

if ($isGithub) {
    // Override DATABASE_URL pour utiliser SQLite en mémoire
    $_ENV['DATABASE_URL'] = 'sqlite:///:memory:';
}

// Crée et boot le kernel
$kernel = new Kernel('test', true);
$kernel->boot();

// Retourne l’instance de l’application directement
return new Application($kernel);
