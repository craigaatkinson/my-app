<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/css/tailwind.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- <script src="/public/htmx.min.js"></script> -->
    <script type="module" src="https://cdn.jsdelivr.net/gh/starfederation/datastar@main/bundles/datastar.js"></script>
    <!-- <script src="/public/datastar-1-0-0-beta-11-451cf4728ff6863d.js"></script> -->
    <!-- <script type="module" src="https://cdn.jsdelivr.net/gh/starfederation/datastar@1.0.0-beta.9/bundles/datastar.js"></script> -->
    <script src="/js/load-data.js"></script>

    <title>Home</title>
    <style>
        .wider-column {
            width: 200px; /* Adjust the width as needed */
        }
    </style>
    
</head>

<body>

<div class="container mx-auto mt-4">
    <button id="showActive" class="bg-blue-500 text-white px-3 py-1 rounded shadow hover:bg-blue-600">Show Active</button>
    <button id="showAll" class="bg-gray-500 text-white px-3 py-1 rounded shadow hover:bg-gray-600">Show All</button>
    <div class="overflow-x-auto mt-4">
        <datastar-table id="tableBody" data-source="activeData" data-columns="columns" data-actions="actions"></datastar-table>
        <datastar-modal id="editModal" data-on-close="updateRecord"></datastar-modal>
        
    </div>
</div>

</body>
</html>