<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

<<<<<<< HEAD
return static function (array $context) {
=======
return function (array $context) {
>>>>>>> eabe32bd1d39cea1a5a722f0ae36928346b48e11
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
