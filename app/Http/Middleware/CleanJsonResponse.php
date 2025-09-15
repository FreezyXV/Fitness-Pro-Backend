<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CleanJsonResponse
{
    /**
     * Handle an incoming request and clean the response from PHP notices
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only process API requests
        if (!$request->is('api/*')) {
            return $next($request);
        }

        // Capture any output and suppress errors
        ob_start();

        // Store original error settings
        $originalDisplayErrors = ini_get('display_errors');
        $originalErrorReporting = error_reporting();

        // Suppress all error output for clean JSON
        ini_set('display_errors', 0);
        error_reporting(0);

        try {
            $response = $next($request);

            // Clean any buffered output
            $buffer = ob_get_contents();
            ob_end_clean();

            // Restore error settings
            ini_set('display_errors', $originalDisplayErrors);
            error_reporting($originalErrorReporting);

            // Clean the response content
            $content = $response->getContent();
            $cleanedContent = $this->cleanJsonContent($content);

            if ($cleanedContent !== $content) {
                $response->setContent($cleanedContent);

                // Ensure proper JSON headers
                if (!$response->headers->has('Content-Type')) {
                    $response->headers->set('Content-Type', 'application/json');
                }

                // Update content length
                $response->headers->set('Content-Length', strlen($cleanedContent));
            }

            return $response;

        } catch (\Exception $e) {
            // Clean up and restore settings on error
            if (ob_get_level()) {
                ob_end_clean();
            }
            ini_set('display_errors', $originalDisplayErrors);
            error_reporting($originalErrorReporting);
            throw $e;
        }
    }

    /**
     * Clean PHP notices and warnings from JSON content
     */
    private function cleanJsonContent(string $content): string
    {
        // If content doesn't start with PHP error tags, return as is
        if (!str_starts_with($content, '<br />') && !str_starts_with($content, '<b>')) {
            return $content;
        }

        // Find the start of actual JSON content
        $jsonStart = false;
        $patterns = ['{', '[', '"'];

        foreach ($patterns as $pattern) {
            $pos = strpos($content, $pattern);
            if ($pos !== false) {
                if ($jsonStart === false || $pos < $jsonStart) {
                    $jsonStart = $pos;
                }
            }
        }

        // If we found JSON start, extract clean content
        if ($jsonStart !== false) {
            $cleanContent = substr($content, $jsonStart);

            // Validate that it's actually valid JSON
            if ($this->isValidJson($cleanContent)) {
                return $cleanContent;
            }
        }

        // Fallback: try to remove HTML tags and get everything after them
        $cleanContent = preg_replace('/<br\s*\/?>/i', '', $content);
        $cleanContent = preg_replace('/<\/?b>/i', '', $cleanContent);
        $cleanContent = preg_replace('/^Notice:.*?on line \d+/im', '', $cleanContent);
        $cleanContent = trim($cleanContent);

        if ($this->isValidJson($cleanContent)) {
            return $cleanContent;
        }

        // If all else fails, return original content
        return $content;
    }

    /**
     * Check if string is valid JSON
     */
    private function isValidJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}