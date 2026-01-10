# Datastar Best Practices for PHP Backend

This document outlines best practices for implementing Datastar with PHP backends, specifically for the AEMS project.

## Overview

Datastar is a lightweight hypermedia framework that simplifies web development by:
- Allowing backend-driven reactivity 
- Providing frontend reactivity using HTML attributes
- Enabling interaction with backends through declarative methods
- Using Server-Sent Events (SSE) for real-time updates
- Supporting signals for reactive state management

**Current Version**: v1.0.0-RC.2  
**CDN**: `https://cdn.jsdelivr.net/gh/starfederation/datastar@main/bundles/datastar.js`

### Key Features
- **Hypermedia-first approach**: Drive frontend from backend using HTML attributes
- **No npm dependencies**: Simple script tag inclusion
- **Reactive signals**: Frontend state management with `$` prefix
- **Backend request handling**: Declarative actions like `@get()`, `@post()`
- **Server-Sent Events**: Real-time DOM updates via SSE

## Core Architecture

### 1. DatastarService Class

The `DatastarService` class provides utilities for:
- SSE response handling
- HTML fragment generation
- Signal management
- Validation and error handling

```php
use App\Services\DatastarService;

$datastar = new DatastarService();
$datastar->addFragment('#target', '<div>New content</div>')
         ->addSignal('status', 'updated')
         ->sendEvent();
```

### 2. Controller Structure

Controllers should follow these patterns:

```php
class DatastarController 
{
    private DatastarService $datastar;
    
    public function __construct()
    {
        $this->datastar = new DatastarService();
    }
    
    public function updateData(): void
    {
        try {
            // Process request
            $data = $this->processData();
            
            // Generate HTML
            $html = $this->generateHtml($data);
            
            // Send response
            $this->datastar
                ->addFragment('#content', $html)
                ->addSignal('updated', true)
                ->sendEvent();
        } catch (\Exception $e) {
            $this->datastar->sendError('#errors', $e->getMessage());
        }
    }
}
```

## Best Practices

### 1. Server-Sent Events (SSE)

**Always set proper headers:**
```php
public function stream(): void
{
    $this->datastar->setSSEHeaders();
    // Your SSE logic here
}
```

**Use proper event types:**
```php
// For DOM updates
$this->datastar->sendEvent('datastar-merge');

// For custom events
$this->datastar->sendEvent('custom-event');
```

### 2. Fragment Management

**Target specific elements:**
```php
// Good - specific selector
$this->datastar->addFragment('#user-table', $tableHtml);

// Avoid - too broad
$this->datastar->addFragment('body', $pageHtml);
```

**Use appropriate merge types:**
```php
// Replace content (default)
$this->datastar->addFragment('#content', $html, 'morph');

// Add to beginning
$this->datastar->addFragment('#notifications', $notification, 'prepend');

// Add to end
$this->datastar->addFragment('#log', $logEntry, 'append');
```

### 3. Signal Management

**Use descriptive signal names:**
```php
// Good
$this->datastar->addSignal('userLoggedIn', true);
$this->datastar->addSignal('totalRecords', count($data));

// Avoid
$this->datastar->addSignal('flag', true);
$this->datastar->addSignal('num', count($data));
```

**Keep signals atomic:**
```php
// Send related signals together
$this->datastar->addSignals([
    'loading' => false,
    'dataLoaded' => true,
    'recordCount' => count($records),
    'lastUpdated' => time()
]);
```

### 4. Error Handling

**Provide user-friendly error messages:**
```php
public function processForm(): void
{
    try {
        $this->validateInput($_POST);
        $this->saveData($_POST);
        $this->datastar->sendSuccess('#messages', 'Data saved successfully');
    } catch (ValidationException $e) {
        $this->datastar->sendError('#form-errors', $e->getMessage(), 'warning');
    } catch (\Exception $e) {
        error_log($e->getMessage());
        $this->datastar->sendError('#form-errors', 'An unexpected error occurred');
    }
}
```

**Use different error types:**
```php
// Error (red)
$this->datastar->sendError('#alerts', 'Failed to save', 'error');

// Warning (yellow)
$this->datastar->sendError('#alerts', 'Please check input', 'warning');

// Info (blue)
$this->datastar->sendError('#alerts', 'Processing...', 'info');
```

### 5. Validation

**Server-side validation is mandatory:**
```php
$rules = [
    'email' => ['required', 'email'],
    'name' => ['required', 'max:100'],
    'phone' => ['required']
];

$validation = $this->datastar->validateInput($_POST, $rules);

if (!$validation['valid']) {
    $errorHtml = $this->datastar->generateValidationErrorsHtml($validation['errors']);
    $this->datastar->addFragment('#form-errors', $errorHtml);
    return;
}
```

### 6. Loading States

**Show loading indicators:**
```php
public function loadData(): void
{
    // Show loading immediately
    $this->datastar
        ->addFragment('#content', $this->datastar->generateLoadingHtml())
        ->sendEvent();
    
    // Process data
    $data = $this->fetchData();
    
    // Show results
    $this->datastar
        ->addFragment('#content', $this->generateDataHtml($data))
        ->addSignal('loading', false)
        ->sendEvent();
}
```

### 7. Security Considerations

**Always escape output:**
```php
// Good
$html = '<div>' . htmlspecialchars($userInput) . '</div>';

// Dangerous
$html = '<div>' . $userInput . '</div>';
```

**Validate and sanitize input:**
```php
public function processInput(): void
{
    $data = filter_input_array(INPUT_POST, [
        'name' => FILTER_SANITIZE_STRING,
        'email' => FILTER_VALIDATE_EMAIL,
        'age' => FILTER_VALIDATE_INT
    ]);
    
    if (!$data || in_array(false, $data, true)) {
        $this->datastar->sendError('#errors', 'Invalid input data');
        return;
    }
    
    // Process validated data
}
```

**Implement CSRF protection:**
```php
public function submitForm(): void
{
    if (!$this->validateCSRFToken($_POST['csrf_token'])) {
        $this->datastar->sendError('#errors', 'Invalid request');
        return;
    }
    
    // Process form
}
```

## Frontend Integration

### 1. HTML Attributes

**Use semantic Datastar attributes with `data-*` pattern:**
```html
<!-- Data binding with signals -->
<input data-bind-userName type="text" placeholder="Username">
<div data-text="$userName">Default text</div>

<!-- Event handling with actions -->
<button data-on-click="@post('/api/submit')">Submit</button>
<form data-on-submit="@post('/api/form')">Submit Form</form>

<!-- Conditional display -->
<div data-show="$isVisible">Conditional content</div>

<!-- CSS classes -->
<div data-class-active="$isActive" data-class-hidden="!$isVisible">Content</div>

<!-- Attributes -->
<input data-attr-disabled="$isDisabled" type="text">

<!-- Signal storage -->
<div data-signals-userInfo='{"name": "", "email": ""}'>
```

**Core Datastar Attributes:**
- `data-bind-*`: Two-way data binding with signals
- `data-on-*`: Event listeners with action calls
- `data-text`: Set element text content from signal
- `data-show`: Toggle element visibility
- `data-class-*`: Toggle CSS classes
- `data-attr-*`: Set element attributes
- `data-signals`: Initialize or update signals

### 2. Signal Management

**Initialize signals using `data-signals` attribute:**
```html
<!-- Initialize signals in HTML -->
<div data-signals='{"userName": "", "isVisible": false, "counter": 0, "loading": false}'></div>

<!-- Or initialize individual signals -->
<input data-bind-userName value="">
<div data-bind-counter>0</div>

<!-- Computed signals -->
<div data-computed-fullName="$firstName + ' ' + $lastName"></div>
```

**Signal Patterns:**
- Signals are reactive and automatically update the DOM
- Use `$signalName` to reference signals in expressions  
- Signals can be primitives, objects, or arrays
- Changes to signals trigger re-evaluation of dependent expressions

### 3. Loading Indicators

**Use loading indicators with signals:**
```html
<!-- Button with loading state -->
<button data-on-click="@get('/api/data')" 
        data-class-opacity-50="$loading"
        data-attr-disabled="$loading">
    <span data-show="!$loading">Load Data</span>
    <span data-show="$loading">Loading...</span>
</button>

<!-- Global loading state -->
<div data-show="$loading" class="loading-spinner">
    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    Loading...
</div>

<!-- Using data-indicator attribute -->
<div data-indicator-loading>
    <div class="htmx-indicator">Processing...</div>
</div>
```

## Performance Optimization

### 1. Minimize DOM Updates

**Batch updates when possible:**
```php
// Good - single update
$this->datastar
    ->addFragment('#table', $tableHtml)
    ->addFragment('#summary', $summaryHtml)
    ->addSignals(['loaded' => true, 'count' => $count])
    ->sendEvent();

// Avoid - multiple events
$this->datastar->addFragment('#table', $tableHtml)->sendEvent();
$this->datastar->addFragment('#summary', $summaryHtml)->sendEvent();
```

### 2. Cache When Appropriate

**Cache expensive operations:**
```php
public function loadData(): void
{
    $cacheKey = 'load_data_' . md5(serialize($_GET));
    
    if ($cached = $this->cache->get($cacheKey)) {
        $this->datastar->addFragment('#content', $cached)->sendEvent();
        return;
    }
    
    $html = $this->generateExpensiveHtml();
    $this->cache->set($cacheKey, $html, 300); // 5 minutes
    
    $this->datastar->addFragment('#content', $html)->sendEvent();
}
```

### 3. Paginate Large Data Sets

**Use pagination for large datasets:**
```php
public function loadDataPage(): void
{
    $page = (int)($_GET['page'] ?? 1);
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    $data = $this->fetchData($limit, $offset);
    $total = $this->getDataCount();
    
    $html = $this->generateTableHtml($data);
    $paginationHtml = $this->generatePaginationHtml($page, $total, $limit);
    
    $this->datastar
        ->addFragment('#data-table', $html)
        ->addFragment('#pagination', $paginationHtml)
        ->addSignals([
            'currentPage' => $page,
            'totalPages' => ceil($total / $limit),
            'totalRecords' => $total
        ])
        ->sendEvent();
}
```

## Testing

### 1. Unit Testing Controllers

```php
public function testLoadDataTable(): void
{
    $controller = new DatastarController();
    
    ob_start();
    $controller->loadDataTable();
    $output = ob_get_clean();
    
    $this->assertStringContains('event: datastar-merge', $output);
    $this->assertStringContains('"fragments":', $output);
}
```

### 2. Integration Testing

```php
public function testDatastarEndpoint(): void
{
    $response = $this->get('/datastar/load-data-table');
    
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/event-stream');
}
```

## Debugging

### 1. Debug Mode

**Enable debug output:**
```php
class DatastarService 
{
    private bool $debug = false;
    
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }
    
    public function sendEvent(string $eventType = 'datastar-merge'): void
    {
        if ($this->debug) {
            error_log("Datastar Event: {$eventType}");
            error_log("Data: " . json_encode($this->getData()));
        }
        
        // Send event
    }
}
```

### 2. Client-side Debugging

**Use browser console:**
```html
<script>
// Log all signal changes
datastar.on('signal-changed', (signal, value) => {
    console.log(`Signal ${signal} changed to:`, value);
});

// Log all events
datastar.on('event-received', (event) => {
    console.log('Datastar event received:', event);
});
</script>
```

## Common Patterns

### 1. Search with Debounce

```html
<input type="text" 
       data-bind-searchTerm
       data-on-input="@get('/search?q=' + encodeURIComponent($searchTerm))"
       placeholder="Search...">
```

### 2. Auto-save Forms

```html
<form data-on-change="@post('/auto-save', {headers: {'Content-Type': 'application/x-www-form-urlencoded'}})">
    <input data-bind-title type="text" name="title">
    <textarea data-bind-content name="content"></textarea>
</form>
```

### 3. Real-time Updates

```php
public function liveUpdates(): void
{
    $this->datastar->setSSEHeaders();
    
    while (true) {
        $data = $this->getLatestData();
        
        $this->datastar
            ->addFragment('#live-data', $this->formatData($data))
            ->addSignal('lastUpdate', time())
            ->sendEvent();
        
        sleep(5); // Update every 5 seconds
        
        if (connection_aborted()) {
            break;
        }
    }
}
```

This implementation provides a robust foundation for Datastar-powered PHP applications with proper error handling, security, and performance considerations.

## Updated for Datastar v1.0.0-RC.2

### New Features and Changes

**Installation:**
```html
<script type="module" src="https://cdn.jsdelivr.net/gh/starfederation/datastar@main/bundles/datastar.js"></script>
```

**Signal Initialization (Recommended):**
```html
<!-- Use data-signals attribute instead of JavaScript -->
<div data-signals='{"userName": "", "loading": false}' style="display: none;"></div>
```

**Event Types:**
- `datastar-patch-elements` - For DOM updates
- `datastar-patch-signals` - For signal updates

**PHP Backend Updates:**
```php
// Updated sendEvent method automatically detects event type
$this->datastar
    ->addFragment('#content', $html)
    ->addSignal('loading', false)
    ->sendEvent(); // Auto-detects appropriate event type
```

### Migration Guide

1. **Update CDN link** to use latest version
2. **Replace script-based signal initialization** with `data-signals` attribute
3. **Update PHP service** to use new event format
4. **Test all Datastar interactions** with new version

### New Attribute Examples

```html
<!-- Computed signals -->
<div data-computed-fullName="$firstName + ' ' + $lastName"></div>

<!-- Effect (side effects) -->
<div data-effect="console.log('User changed:', $userName)"></div>

<!-- JSON signals display -->
<pre data-json-signals='{"include": /user/, "exclude": /private/}'></pre>

<!-- Signal references -->
<div data-ref-myElement></div> <!-- Sets $myElement to this element -->

<!-- Indicators -->
<div data-indicator-loading>Loading state managed automatically</div>
```

### Action Functions

**Available Actions:**
- `@get(url)` - GET request
- `@post(url, options)` - POST request
- `@put(url, options)` - PUT request
- `@patch(url, options)` - PATCH request
- `@delete(url, options)` - DELETE request

**Options Object:**
```javascript
{
    headers: {'Content-Type': 'application/json'},
    selector: '#target',
    contentType: 'json|form',
    filterSignals: {include: /.*/, exclude: /private/}
}
```

This comprehensive update ensures your AEMS application leverages the latest Datastar capabilities for optimal performance and developer experience.