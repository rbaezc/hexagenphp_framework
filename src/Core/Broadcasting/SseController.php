<?php
namespace HexaGen\Core\Broadcasting;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SseController
{
    public function __invoke(Request $request, string $channel): StreamedResponse
    {
        $file = \HexaGen\Core\Application::storagePath("framework/sse/{$channel}.json");

        return new StreamedResponse(function () use ($file) {
            $lastPos = 0;

            // SSE loop — runs until client disconnects
            while (true) {
                if (connection_aborted()) {
                    break;
                }

                if (file_exists($file)) {
                    $content = file_get_contents($file);
                    $lines   = array_filter(explode("\n", $content));
                    $newLines = array_slice($lines, $lastPos);
                    $lastPos  = count($lines);

                    foreach ($newLines as $line) {
                        $payload = json_decode($line, true);
                        if ($payload) {
                            echo "event: " . ($payload['event'] ?? 'message') . "\n";
                            echo "data: " . json_encode($payload['data'] ?? $payload) . "\n\n";
                        }
                    }
                }

                echo ": heartbeat\n\n";
                ob_flush();
                flush();
                sleep(1);
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }
}
