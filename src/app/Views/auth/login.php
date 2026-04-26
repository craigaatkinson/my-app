<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AEMS - Sign In</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full">
    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex justify-center">
                <svg class="h-12 w-12 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                </svg>
            </div>
            <h2 class="mt-4 text-center text-3xl font-bold text-gray-900">Sign in to AEMS</h2>
            <p class="mt-2 text-center text-sm text-gray-600">A-Transport Enterprise Management System</p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                <?php if (!empty($error)): ?>
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-md text-sm">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                    <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-md text-sm">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <form action="/login" method="POST" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                        <input id="email" name="email" type="email" autocomplete="email" required
                               class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm
                                      focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                               class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm
                                      focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2">
                    </div>

                    <div>
                        <button type="submit"
                                class="w-full flex justify-center py-2 px-4 border border-transparent
                                       rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600
                                       hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2
                                       focus:ring-indigo-500">
                            Sign in
                        </button>
                    </div>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">
                        Don't have an account?
                        <a href="/register" class="font-medium text-indigo-600 hover:text-indigo-500">Create one</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
