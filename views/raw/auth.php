<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Card Container -->
        <div class="bg-gray-800 rounded-2xl shadow-2xl p-8 space-y-6 border border-gray-700">
            <!-- Logo/Brand Area -->
            <div class="text-center space-y-2">
                <h1 class="text-3xl font-bold text-white">Авторизация</h1>
                <?php
                    if (isset($error)) {
                        echo "<p class='text-red-500'>{$error}</p>";
                    }
                ?>
            </div>

            <!-- Form -->
            <form class="space-y-6" action="/raw/login" method="post">
                <!-- Email Input -->
                <div class="space-y-2">
                    <label for="email" class="text-sm font-medium text-gray-300 block">Электронная почта</label>
                    <div class="relative">
                        <input 
                            type="email" 
                            name="email"
                            id="email" 
                            required
                            class="w-full px-4 py-3 rounded-lg bg-gray-700 border border-gray-600 text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 ease-in-out"
                            placeholder="name@example.com"
                        >
                    </div>
                </div>

                <!-- Password Input -->
                <div class="space-y-2">
                    <label for="password" class="text-sm font-medium text-gray-300 block">Пароль</label>
                    <div class="relative">
                        <input 
                            type="password" 
                            name="password"
                            id="password" 
                            required
                            class="w-full px-4 py-3 rounded-lg bg-gray-700 border border-gray-600 text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 ease-in-out"
                            placeholder="••••••••"
                        >
                    </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <!-- <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            id="remember" 
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded cursor-pointer"
                        >
                        <label for="remember" class="ml-2 block text-sm text-gray-700 cursor-pointer">Запомнить меня</label>
                    </div>
                    <a href="#" class="text-sm font-medium text-blue-600 hover:text-blue-500 transition-colors duration-200">Забыли пароль?</a>
                </div> -->

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="w-full bg-blue-600 text-white rounded-lg px-4 py-3 font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-800 transition-colors duration-200 ease-in-out"
                >
                    Войти
                </button>
            </form>

            <!-- Sign Up Link -->
            <!-- <div class="text-center text-sm text-gray-600">
                Нет аккаунта? 
                <a href="#" class="font-medium text-blue-600 hover:text-blue-500 transition-colors duration-200">Зарегистрироваться</a>
            </div> -->
        </div>
    </div>
</body>
</html>