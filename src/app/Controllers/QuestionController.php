<?php

namespace App\Controllers;

use starfederation\datastar\ServerSentEventGenerator;

class QuestionController
{
    public function stream()
    {
        // Set headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        // Initialize the SSE generator
        $sse = new ServerSentEventGenerator();

        // Merge an HTML fragment
        $sse->mergeFragments('<div id="question">What do you put in a toaster?</div>');

        // Merge signals
        $sse->mergeSignals([
            'response' => '',
            'answer' => 'bread',
        ]);

        // Output the stream line by line
        // This triggers output and flushing without using stream()
        ob_start();
        $sse->mergeFragments('<div id="question">What do you put in a toaster?</div>');
        $sse->mergeSignals(['response' => '', 'answer' => 'bread']);
        ob_end_flush();
        flush();

            }
}
