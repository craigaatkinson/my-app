<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AEMS - Datastar Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="module" src="/Datastar v1.0.0-RC.2.js"></script>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-semibold text-gray-900">AEMS Dashboard</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600" data-text="'Last updated: ' + new Date().toLocaleTimeString()"></span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Signals Display -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Dashboard Signals</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 p-4 rounded">
                    <div class="text-sm text-blue-600">Data Loaded</div>
                    <div class="text-lg font-medium" data-text="$dataLoaded ? 'Yes' : 'No'">No</div>
                </div>
                <div class="bg-green-50 p-4 rounded">
                    <div class="text-sm text-green-600">Total Records</div>
                    <div class="text-lg font-medium" data-text="$totalRecords || 0">0</div>
                </div>
                <div class="bg-purple-50 p-4 rounded">
                    <div class="text-sm text-purple-600">Search Results</div>
                    <div class="text-lg font-medium" data-text="$searchResults || 0">0</div>
                </div>
                <div class="bg-yellow-50 p-4 rounded">
                    <div class="text-sm text-yellow-600">Form Status</div>
                    <div class="text-lg font-medium" data-text="$formSubmitted ? 'Submitted' : 'Ready'">Ready</div>
                </div>
            </div>
        </div>

        <!-- Data Table Section -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-medium text-gray-900">Load Data</h2>
                <button 
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors"
                    data-on-click="@get('/datastar/load-data-table')"
                    data-indicator-loading-text="Loading...">
                    Load Data
                </button>
            </div>
            <div id="data-table" class="overflow-x-auto">
                <div class="text-center p-8 text-gray-500">
                    Click "Load Data" to fetch records
                </div>
            </div>
        </div>

        <!-- Customer Search Section -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Customer Search</h2>
            <div class="mb-4">
                <input 
                    type="text" 
                    placeholder="Search customers..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    data-bind-searchTerm
                    data-on-input="@get('/datastar/search-customers?search=' + encodeURIComponent($searchTerm))">
            </div>
            <div id="customer-results" class="min-h-[100px]">
                <div class="text-center p-4 text-gray-500">
                    Start typing to search customers
                </div>
            </div>
        </div>

        <!-- Dynamic Form Section -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Dynamic Form</h2>
            
            <!-- Form Type Selector -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Form Type</label>
                <select 
                    class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    data-bind-formType
                    data-on-change="@get('/datastar/form-fields?type=' + $formType)">
                    <option value="basic">Basic Form</option>
                    <option value="customer">Customer Form</option>
                    <option value="shipment">Shipment Form</option>
                </select>
            </div>

            <!-- Dynamic Form Fields -->
            <div id="dynamic-fields">
                <div class="text-center p-4 text-gray-500">
                    Select a form type to see dynamic fields
                </div>
            </div>

            <!-- Form Errors -->
            <div id="form-errors"></div>

            <!-- Submit Button -->
            <div class="mt-6">
                <button 
                    class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition-colors disabled:opacity-50"
                    data-on-click="@post('/datastar/submit-form', {headers: {'Content-Type': 'application/x-www-form-urlencoded'}})"
                    data-indicator-loading-text="Submitting...">
                    Submit Form
                </button>
            </div>
        </div>

        <!-- Real-time Notifications Section -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-medium text-gray-900">Live Notifications</h2>
                <button 
                    class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition-colors"
                    data-on-click="@get('/datastar/notifications')"
                    data-indicator-loading-text="Connecting...">
                    Start Live Updates
                </button>
            </div>
            <div id="notifications" class="space-y-2 max-h-64 overflow-y-auto">
                <div class="text-center p-4 text-gray-500">
                    Click "Start Live Updates" to see real-time notifications
                </div>
            </div>
        </div>

        <!-- Interactive Examples Section -->
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Interactive Examples</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Counter Example -->
                <div class="border rounded p-4">
                    <h3 class="font-medium mb-3">Counter Example</h3>
                    <div class="flex items-center space-x-4">
                        <button 
                            class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600"
                            data-on-click="$counter = ($counter || 0) - 1">
                            -
                        </button>
                        <span class="text-xl font-medium" data-text="$counter || 0">0</span>
                        <button 
                            class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600"
                            data-on-click="$counter = ($counter || 0) + 1">
                            +
                        </button>
                    </div>
                </div>

                <!-- Toggle Example -->
                <div class="border rounded p-4">
                    <h3 class="font-medium mb-3">Toggle Example</h3>
                    <button 
                        class="px-4 py-2 rounded transition-colors"
                        data-class-bg-blue-600="$toggleState"
                        data-class-text-white="$toggleState"
                        data-class-bg-gray-300="!$toggleState"
                        data-class-text-gray-700="!$toggleState"
                        data-on-click="$toggleState = !$toggleState"
                        data-text="$toggleState ? 'ON' : 'OFF'">
                        OFF
                    </button>
                </div>

                <!-- Input Example -->
                <div class="border rounded p-4">
                    <h3 class="font-medium mb-3">Reactive Input</h3>
                    <input 
                        type="text" 
                        placeholder="Type something..."
                        class="w-full px-3 py-2 border rounded mb-2"
                        data-bind-userInput>
                    <p class="text-sm text-gray-600">
                        You typed: <span data-text="$userInput || 'nothing yet'">nothing yet</span>
                    </p>
                </div>

                <!-- Conditional Display -->
                <div class="border rounded p-4">
                    <h3 class="font-medium mb-3">Conditional Display</h3>
                    <label class="flex items-center space-x-2">
                        <input 
                            type="checkbox" 
                            data-bind-showMessage>
                        <span>Show message</span>
                    </label>
                    <div 
                        class="mt-2 p-2 bg-blue-100 text-blue-800 rounded"
                        data-show="$showMessage">
                        This message is conditionally displayed!
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Global Loading Indicator -->
    <div 
        class="fixed top-4 right-4 bg-blue-600 text-white px-4 py-2 rounded shadow-lg"
        data-show="$loading">
        Loading...
    </div>

    <!-- Debug Panel (for development) -->
    <div class="fixed bottom-4 left-4 bg-gray-900 text-white p-4 rounded shadow-lg max-w-sm" data-show="$showDebug">
        <div class="flex justify-between items-center mb-2">
            <h4 class="font-medium">Debug Signals</h4>
            <button 
                class="text-gray-400 hover:text-white"
                data-on-click="$showDebug = false">
                ×
            </button>
        </div>
        <pre class="text-xs overflow-auto max-h-32" data-text="JSON.stringify(datastar.signals, null, 2)">
        </pre>
    </div>

    <!-- Debug Toggle Button -->
    <button 
        class="fixed bottom-4 left-4 bg-gray-700 text-white p-2 rounded shadow-lg"
        data-show="!$showDebug"
        data-on-click="$showDebug = true">
        Debug
    </button>

    <!-- Initialize signals using data-signals attribute -->
    <div data-signals='{
        "counter": 0,
        "toggleState": false,
        "userInput": "",
        "showMessage": false,
        "showDebug": false,
        "dataLoaded": false,
        "totalRecords": 0,
        "searchResults": 0,
        "formSubmitted": false,
        "searchTerm": "",
        "formType": "basic",
        "loading": false
    }' style="display: none;"></div>
</body>
</html>