<?php

namespace App\ProCalendarBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ProCalendarBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__) . '/ProCalendarBundle';
    }
}
