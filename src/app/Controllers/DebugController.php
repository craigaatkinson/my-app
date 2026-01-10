<?php

namespace App\Controllers;

class DebugController
{
    public function testSSE(): void
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        // Simple SSE test
        echo "event: datastar-patch-elements\n";
        echo "data: elements <h1 class='text-2xl font-bold text-green-600'>SSE Working!</h1>\n";
        echo "data: selector #test-result\n";
        echo "data: mode morph\n";
        echo "\n";

        echo "event: datastar-patch-signals\n";
        echo "data: signals " . json_encode(['testSignal' => 'SSE is working!']) . "\n";
        echo "\n";

        flush();
    }
}
