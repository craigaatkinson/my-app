<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AEMS - Load Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="module" src="/datastar-1.0.0-RC.2.js"></script>
    <style>
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .slide-in {
            animation: slideIn 0.3s ease-out;
        }
    </style>
</head>
<body class="h-full">
    <div class="min-h-full">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 flex items-center">
                            <svg class="h-8 w-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                            </svg>
                            <h1 class="ml-3 text-2xl font-bold text-gray-900">AEMS</h1>
                        </div>
                        <div class="hidden md:ml-10 md:flex md:space-x-8">
                            <a href="/dashboard" class="border-indigo-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Dashboard
                            </a>
                            <a href="/loads" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Loads
                            </a>
                            <a href="/customer-list" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Customers
                            </a>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600">
                            Last updated: <span data-text="$lastUpdate || new Date().toLocaleTimeString()"></span>
                        </span>
                        <button class="p-2 rounded-full text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="md:flex md:items-center md:justify-between mb-6">
                <div class="flex-1 min-w-0">
                    <h2 class="text-3xl font-bold leading-7 text-gray-900 sm:text-4xl sm:truncate">
                        Load Dashboard
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Manage and track all transportation loads
                    </p>
                </div>
                <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
                    <button
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        data-on-click="@get('/dashboard/statistics')">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Refresh
                    </button>
                    <a href="/loads/new" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        New Load
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div id="statistics-cards" class="mb-8" data-on-load="@get('/dashboard/statistics')">
                <div class="flex items-center justify-center py-12">
                    <div class="text-center">
                        <div class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-sm shadow rounded-md text-white bg-indigo-500">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Loading statistics...
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Grid Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Main Content Area (3 columns) -->
                <div class="lg:col-span-3 space-y-6">
                    <!-- Search and Filters -->
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Search Input -->
                            <div class="md:col-span-2">
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search Loads</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </div>
                                    <input
                                        type="text"
                                        id="search"
                                        placeholder="Search by invoice, company, or driver..."
                                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        data-bind-searchQuery
                                        data-on-input="$loading = true; @get('/dashboard/search?q=' + encodeURIComponent($searchQuery))">
                                </div>
                            </div>

                            <!-- Status Filter -->
                            <div>
                                <label for="status-filter" class="block text-sm font-medium text-gray-700 mb-2">Status Filter</label>
                                <select
                                    id="status-filter"
                                    class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                                    data-bind-statusFilter
                                    data-on-change="$loading = true; @get('/dashboard/load-table?status=' + $statusFilter)">
                                    <option value="">All Status</option>
                                    <option value="completed">Completed</option>
                                    <option value="pending">Pending</option>
                                    <option value="active">Active</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>

                        <!-- Quick Stats Row -->
                        <div class="mt-4 pt-4 border-t border-gray-200 flex items-center justify-between text-sm">
                            <div class="text-gray-600">
                                Showing <span class="font-semibold" data-text="$displayedRecords || 0">0</span> of <span class="font-semibold" data-text="$totalRecords || 0">0</span> loads
                            </div>
                            <div class="flex items-center space-x-4">
                                <span class="text-gray-600">
                                    Page: <span class="font-semibold" data-text="$currentPage || 1">1</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Load Table -->
                    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Loads</h3>
                        </div>
                        <div id="load-table" class="min-h-[400px]" data-on-load="@get('/dashboard/load-table?limit=20&offset=0')">
                            <!-- Loading State -->
                            <div class="flex items-center justify-center py-12">
                                <div class="text-center">
                                    <div class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-sm shadow rounded-md text-white bg-indigo-500">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Loading loads...
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6" data-show="$tableLoaded && $totalRecords > 20">
                            <div class="flex items-center justify-between">
                                <button
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                    data-attr-disabled="$currentPage <= 1"
                                    data-on-click="@get('/dashboard/load-table?offset=' + (($currentPage - 2) * 20))">
                                    Previous
                                </button>
                                <span class="text-sm text-gray-700">
                                    Page <span data-text="$currentPage">1</span>
                                </span>
                                <button
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                    data-on-click="@get('/dashboard/load-table?offset=' + ($currentPage * 20))">
                                    Next
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar (1 column) -->
                <div class="space-y-6">
                    <!-- Recent Loads Widget -->
                    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Loads</h3>
                        </div>
                        <div id="recent-loads" class="px-6 py-4" data-on-load="@get('/dashboard/recent-loads')">
                            <div class="animate-pulse space-y-3">
                                <div class="h-16 bg-gray-200 rounded"></div>
                                <div class="h-16 bg-gray-200 rounded"></div>
                                <div class="h-16 bg-gray-200 rounded"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Customers Widget -->
                    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Top Customers</h3>
                        </div>
                        <div id="top-customers" class="px-6 py-4" data-on-load="@get('/dashboard/top-customers')">
                            <div class="animate-pulse space-y-4">
                                <div class="h-12 bg-gray-200 rounded"></div>
                                <div class="h-12 bg-gray-200 rounded"></div>
                                <div class="h-12 bg-gray-200 rounded"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Load Details Modal -->
    <div
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
        data-show="$showDetails"
        data-on-click="$showDetails = false">
        <div
            class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-lg bg-white slide-in"
            data-on-click="event.stopPropagation()">
            <div id="load-details">
                <!-- Details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Global Loading Indicator -->
    <div
        class="fixed top-4 right-4 z-50"
        data-show="$loading">
        <div class="bg-indigo-600 text-white px-4 py-2 rounded-lg shadow-lg flex items-center">
            <svg class="animate-spin h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Loading...
        </div>
    </div>

    <!-- Initialize Signals -->
    <div data-signals='{
        "searchQuery": "",
        "statusFilter": "",
        "totalRecords": 0,
        "displayedRecords": 0,
        "currentPage": 1,
        "tableLoaded": false,
        "statsLoaded": false,
        "recentLoaded": false,
        "loading": false,
        "showDetails": false,
        "selectedLoad": null,
        "totalLoads": 0,
        "completedLoads": 0,
        "pendingLoads": 0,
        "totalRevenue": 0,
        "lastUpdate": ""
    }' style="display: none;"></div>

</body>
</html>
