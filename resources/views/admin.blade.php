<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'BotPress Publisher') }} — Admin</title>
    @vite(['resources/js/css/admin.css', 'resources/js/admin/main.ts'])
</head>
<body>
    <div id="admin-app"></div>
</body>
</html>
