<?php

namespace Tests\Concerns;

trait ExtractsPdfText
{
    private function teksPdf(string $bytes): string
    {
        preg_match_all('/stream\r?\n(.*?)endstream/s', $bytes, $streams);
        $content = '';
        foreach ($streams[1] as $stream) {
            $decoded = @gzuncompress(rtrim($stream, "\r\n"));
            if ($decoded !== false) {
                $content .= $decoded;
            }
        }

        preg_match_all('/\((?:[^()\\\\]|\\\\.)*\)/', $content, $literals);
        $text = '';
        foreach ($literals[0] as $literal) {
            $text .= str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], substr($literal, 1, -1));
        }

        return $text;
    }
}
