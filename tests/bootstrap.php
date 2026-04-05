<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}
<<<<<<< HEAD

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
=======
>>>>>>> eabe32bd1d39cea1a5a722f0ae36928346b48e11
