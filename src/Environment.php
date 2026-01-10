<?php
namespace App;

use Dotenv\Dotenv;

class Environment
{
    public static function load()
    {
        // Adjust path if necessary; __DIR__ . '/../' goes one level up to the project root
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();
    }
}
