<?php

namespace App\Services;

/**
 * DatastarService - PHP SDK for Datastar v1.0.0-RC.2
 * 
 * This service provides utilities for:
 * - Server-Sent Events (SSE) responses
 * - HTML fragment generation with morphing
 * - Signal management and reactive state
 * - Datastar response formatting
 * - Content-Type detection and response handling
 * - JavaScript execution via script injection
 */
class DatastarService
{
    private array $fragments = [];
    private array $signals = [];
    private array $redirects = [];
    private array $scripts = [];
    private bool $headers_sent = false;
    private string $version = '1.0.0-RC.2';
    /**
     * Set SSE headers for Datastar responses
     */
    public function setSSEHeaders(): void
    {
        if (!$this->headers_sent) {
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Headers: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            $this->headers_sent = true;
        }
    }
    /**
     * Add HTML fragment for merging
     *
     * @param string $selector CSS selector or ID for the target element
     * @param string $html HTML content to merge
     * @param string $merge_type Type of merge (inner, outer, remove, replace, prepend, append, before, after)
     */
    public function addFragment(string $selector, string $html, string $merge_type = 'inner'): self
    {
        $this->fragments[] = [
            'selector' => $selector,
            'html' => $html,
            'merge' => $merge_type
        ];
        return $this;
    }

    /**
     * Add signal data for client-side state management
     * 
     * @param array $signals Associative array of signal names and values
     */
    public function addSignals(array $signals): self
    {
        $this->signals = array_merge($this->signals, $signals);
        return $this;
    }

    /**
     * Add single signal
     * 
     * @param string $name Signal name
     * @param mixed $value Signal value
     */
    public function addSignal(string $name, $value): self
    {
        $this->signals[$name] = $value;
        return $this;
    }

    /**
     * Add redirect instruction
     * 
     * @param string $url URL to redirect to
     */
    public function addRedirect(string $url): self
    {
        $this->redirects[] = $url;
        return $this;
    }

    /**
     * Add JavaScript code to execute on client
     * 
     * @param string $script JavaScript code to execute
     * @param array $attributes Optional script attributes
     */
    public function addScript(string $script, array $attributes = []): self
    {
        $this->scripts[] = [
            'content' => $script,
            'attributes' => $attributes
        ];
        return $this;
    }

    /**
     * Get current Datastar version
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Send HTML response (non-SSE)
     * For cases where direct HTML response is needed
     */
    public function sendHtmlResponse(string $html, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
    }

    /**
     * Send JSON response (non-SSE)
     * For API endpoints that need JSON responses
     */
    public function sendJsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
    }

    /**
     * Send JavaScript response
     * For sending JavaScript code to execute
     */
    public function sendScriptResponse(string $script, array $attributes = []): void
    {
        header('Content-Type: text/javascript; charset=utf-8');
        if (!empty($attributes)) {
            header('Datastar-Script-Attributes: ' . json_encode($attributes));
        }
        echo $script;
    }

    /**
     * Generate and output SSE event for Datastar v1.0.0-RC.2
     * 
     * @param string $event_type Event type (defaults to 'datastar-patch-elements' or 'datastar-patch-signals')
     */
    public function sendEvent(string $event_type = null): void
    {
        $this->setSSEHeaders();

        // Auto-determine event type based on content
        if ($event_type === null) {
            if (!empty($this->fragments)) {
                $event_type = 'datastar-patch-elements';
            } elseif (!empty($this->signals)) {
                $event_type = 'datastar-patch-signals';
            } else {
                $event_type = 'datastar-patch-elements';
            }
        }

        // Handle different response types for Datastar v1.0.0-RC.2
        // Send fragments event if present
        if (!empty($this->fragments)) {
            $elements = '';
            $selector = '#app'; // Default selector
            $mode = 'inner';    // Default mode (valid modes: inner, outer, remove, replace, prepend, append, before, after)

            foreach ($this->fragments as $fragment) {
                $elements .= $fragment['html'];
                $selector = $fragment['selector'];
                $mode = $fragment['merge'];
            }

            // Minify HTML by removing extra whitespace and newlines for SSE transmission
            $elements = preg_replace('/\s+/', ' ', $elements);
            $elements = trim($elements);

            echo "event: datastar-patch-elements\n";
            echo "data: elements {$elements}\n";
            echo "data: selector {$selector}\n";
            echo "data: mode {$mode}\n";

            // Add view transition if supported
            if (!empty($this->signals['useViewTransition'])) {
                echo "data: useViewTransition true\n";
            }
            echo "\n";
        }

        // Send signals event if present (separate event, can coexist with fragments)
        if (!empty($this->signals)) {
            // Filter out internal signals
            $signalsToSend = $this->signals;
            unset($signalsToSend['useViewTransition']);

            if (!empty($signalsToSend)) {
                echo "event: datastar-patch-signals\n";
                echo "data: signals " . json_encode($signalsToSend) . "\n";

                // Add onlyIfMissing flag if needed
                if (!empty($this->signals['_onlyIfMissing'])) {
                    echo "data: onlyIfMissing true\n";
                }
                echo "\n";
            }
        }

        // Handle scripts if present
        if (!empty($this->scripts)) {
            // Handle JavaScript responses
            foreach ($this->scripts as $script) {
                header('Content-Type: text/javascript; charset=utf-8');
                if (!empty($script['attributes'])) {
                    header('Datastar-Script-Attributes: ' . json_encode($script['attributes']));
                }
                echo $script['content'];
                return; // Exit after first script
            }
        }

        // Flush output
        if (ob_get_level()) {
            ob_end_flush();
        }
        flush();

        // Clear data for next event
        $this->fragments = [];
        $this->signals = [];
        $this->redirects = [];
        $this->scripts = [];
    }

    /**
     * Send error fragment
     * 
     * @param string $selector Target selector for error display
     * @param string $message Error message
     * @param string $type Error type (error, warning, info)
     */
    public function sendError(string $selector, string $message, string $type = 'error'): void
    {
        $errorHtml = $this->generateErrorHtml($message, $type);
        $this->addFragment($selector, $errorHtml);
        $this->sendEvent();
    }

    /**
     * Send success message
     * 
     * @param string $selector Target selector for success display
     * @param string $message Success message
     */
    public function sendSuccess(string $selector, string $message): void
    {
        $successHtml = $this->generateSuccessHtml($message);
        $this->addFragment($selector, $successHtml);
        $this->sendEvent();
    }

    /**
     * Generate error HTML with Tailwind classes
     * 
     * @param string $message Error message
     * @param string $type Error type
     * @return string HTML string
     */
    private function generateErrorHtml(string $message, string $type): string
    {
        $classes = match($type) {
            'error' => 'bg-red-100 border-red-400 text-red-700',
            'warning' => 'bg-yellow-100 border-yellow-400 text-yellow-700',
            'info' => 'bg-blue-100 border-blue-400 text-blue-700',
            default => 'bg-gray-100 border-gray-400 text-gray-700'
        };

        return "<div class=\"{$classes} px-4 py-3 rounded border\" role=\"alert\">
                    <span class=\"block sm:inline\">" . htmlspecialchars($message) . "</span>
                </div>";
    }

    /**
     * Generate success HTML with Tailwind classes
     * 
     * @param string $message Success message
     * @return string HTML string
     */
    private function generateSuccessHtml(string $message): string
    {
        return "<div class=\"bg-green-100 border-green-400 text-green-700 px-4 py-3 rounded border\" role=\"alert\">
                    <span class=\"block sm:inline\">" . htmlspecialchars($message) . "</span>
                </div>";
    }

    /**
     * Generate loading indicator HTML
     * 
     * @param string $message Loading message
     * @return string HTML string
     */
    public function generateLoadingHtml(string $message = 'Loading...'): string
    {
        return "<div class=\"flex items-center justify-center p-4\">
                    <div class=\"animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600\"></div>
                    <span class=\"ml-2 text-gray-600\">{$message}</span>
                </div>";
    }

    /**
     * Generate table HTML from data array
     * 
     * @param array $data Data array
     * @param array $columns Column configuration
     * @param string $tableClasses CSS classes for table
     * @return string HTML string
     */
    public function generateTableHtml(array $data, array $columns, string $tableClasses = 'min-w-full divide-y divide-gray-200'): string
    {
        if (empty($data)) {
            return "<div class=\"text-center p-4 text-gray-500\">No data available</div>";
        }

        $html = "<table class=\"{$tableClasses}\">";
        
        // Generate header
        $html .= "<thead class=\"bg-gray-50\">";
        $html .= "<tr>";
        foreach ($columns as $column) {
            $html .= "<th class=\"px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">";
            $html .= htmlspecialchars($column['label'] ?? $column['key']);
            $html .= "</th>";
        }
        $html .= "</tr>";
        $html .= "</thead>";

        // Generate body
        $html .= "<tbody class=\"bg-white divide-y divide-gray-200\">";
        foreach ($data as $row) {
            $html .= "<tr class=\"hover:bg-gray-50\">";
            foreach ($columns as $column) {
                $value = $row[$column['key']] ?? '';
                if (isset($column['format'])) {
                    $value = call_user_func($column['format'], $value, $row);
                }
                $html .= "<td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900\">";
                $html .= htmlspecialchars($value);
                $html .= "</td>";
            }
            $html .= "</tr>";
        }
        $html .= "</tbody>";
        $html .= "</table>";

        return $html;
    }

    /**
     * Generate form field HTML with Datastar attributes
     * 
     * @param string $type Input type
     * @param string $name Field name
     * @param string $label Field label
     * @param mixed $value Current value
     * @param array $attributes Additional attributes
     * @return string HTML string
     */
    public function generateFormField(string $type, string $name, string $label, $value = '', array $attributes = []): string
    {
        $inputClasses = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm';
        $labelClasses = 'block text-sm font-medium text-gray-700';

        $attributeString = '';
        foreach ($attributes as $attr => $val) {
            $attributeString .= " {$attr}=\"" . htmlspecialchars($val) . "\"";
        }

        $html = "<div class=\"mb-4\">";
        $html .= "<label for=\"{$name}\" class=\"{$labelClasses}\">{$label}</label>";
        
        if ($type === 'textarea') {
            $html .= "<textarea id=\"{$name}\" name=\"{$name}\" class=\"{$inputClasses}\" data-bind-{$name}{$attributeString}>";
            $html .= htmlspecialchars($value);
            $html .= "</textarea>";
        } elseif ($type === 'select') {
            $options = $attributes['options'] ?? [];
            unset($attributes['options']);
            
            $html .= "<select id=\"{$name}\" name=\"{$name}\" class=\"{$inputClasses}\" data-bind-{$name}{$attributeString}>";
            foreach ($options as $optValue => $optLabel) {
                $selected = ($value == $optValue) ? ' selected' : '';
                $html .= "<option value=\"" . htmlspecialchars($optValue) . "\"{$selected}>";
                $html .= htmlspecialchars($optLabel);
                $html .= "</option>";
            }
            $html .= "</select>";
        } else {
            $html .= "<input type=\"{$type}\" id=\"{$name}\" name=\"{$name}\" value=\"" . htmlspecialchars($value) . "\" class=\"{$inputClasses}\" data-bind-{$name}{$attributeString}>";
        }
        
        $html .= "</div>";

        return $html;
    }

    /**
     * Validate and sanitize input data
     * 
     * @param array $data Input data
     * @param array $rules Validation rules
     * @return array Array with 'valid' boolean and 'errors' array
     */
    public function validateInput(array $data, array $rules): array
    {
        $errors = [];
        $valid = true;

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                switch ($rule) {
                    case 'required':
                        if (empty($value)) {
                            $errors[$field][] = "{$field} is required";
                            $valid = false;
                        }
                        break;
                    case 'email':
                        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = "{$field} must be a valid email";
                            $valid = false;
                        }
                        break;
                    default:
                        if (is_string($rule) && strpos($rule, 'max:') === 0) {
                            $max = (int)substr($rule, 4);
                            if (!empty($value) && strlen($value) > $max) {
                                $errors[$field][] = "{$field} must not exceed {$max} characters";
                                $valid = false;
                            }
                        }
                        break;
                }
            }
        }

        return ['valid' => $valid, 'errors' => $errors];
    }

    /**
     * Generate validation error HTML
     * 
     * @param array $errors Validation errors array
     * @return string HTML string
     */
    public function generateValidationErrorsHtml(array $errors): string
    {
        if (empty($errors)) {
            return '';
        }

        $html = "<div class=\"bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4\">";
        $html .= "<ul class=\"list-disc list-inside\">";
        foreach ($errors as $field => $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $html .= "<li>" . htmlspecialchars($error) . "</li>";
            }
        }
        $html .= "</ul>";
        $html .= "</div>";

        return $html;
    }

    /**
     * Clear all pending data
     */
    public function clear(): self
    {
        $this->fragments = [];
        $this->signals = [];
        $this->redirects = [];
        $this->scripts = [];
        return $this;
    }

    /**
     * Check if request is from Datastar
     */
    public function isDatastarRequest(): bool
    {
        return isset($_SERVER['HTTP_DATASTAR_REQUEST']) || 
               isset($_SERVER['HTTP_HX_REQUEST']); // Backward compatibility
    }

    /**
     * Get request headers for debugging
     */
    public function getRequestInfo(): array
    {
        return [
            'version' => $this->version,
            'is_datastar_request' => $this->isDatastarRequest(),
            'headers' => getallheaders(),
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET'
        ];
    }

    /**
     * Create a quick response for simple text updates
     */
    public function quickText(string $selector, string $text): self
    {
        return $this->addFragment($selector, htmlspecialchars($text));
    }

    /**
     * Create a quick response for simple HTML updates
     */
    public function quickHtml(string $selector, string $html): self
    {
        return $this->addFragment($selector, $html);
    }

    /**
     * Merge multiple signals at once with optional ifMissing flag
     */
    public function mergeSignals(array $signals, bool $ifMissing = false): self
    {
        if ($ifMissing) {
            $signals['_onlyIfMissing'] = true;
        }
        return $this->addSignals($signals);
    }

    /**
     * Add view transition support
     */
    public function withViewTransition(): self
    {
        return $this->addSignal('useViewTransition', true);
    }
}