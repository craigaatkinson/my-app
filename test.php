<?php

require_once __DIR__ . '/vendor/autoload.php';

use starfederation\datastar\ServerSentEventGenerator;

$sse = new ServerSentEventGenerator();

if (method_exists($sse, 'stream')) {
    echo "✅ Method stream() exists!\n";
} else {
    echo "❌ Method stream() does NOT exist.\n";
}
