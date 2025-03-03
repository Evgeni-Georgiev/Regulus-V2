<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regulus</title>
    <link rel="canonical" href="{{ config('app.url') }}" />
    
    <!-- Initialize dark mode to prevent flash -->
    <script>
        // On page load, default to dark mode unless explicitly set to light
        if (localStorage.theme === 'light') {
            document.documentElement.classList.remove('dark')
        } else {
            document.documentElement.classList.add('dark')
            localStorage.setItem('theme', 'dark')
        }
    </script>
    
    @vite(['resources/js/app.js'])
</head>
<body class="antialiased">
    <div id="app"></div>
</body>
</html>
