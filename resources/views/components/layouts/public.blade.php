@props(['title' => 'MEH'])

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>{{ $title }} - MEH</title>
    @vite('resources/css/meh.css')
</head>
<body class="public-body">
    <div class="public-shell">
        <header class="public-header">
            <a class="public-brand" href="{{ route('public.home') }}">MEH</a>
            <nav class="public-navigation" aria-label="Navigation publique">
                <a href="{{ route('login') }}">Connexion</a>
                <a href="{{ route('account-requests.create') }}">Demande de compte</a>
            </nav>
        </header>

        <main class="public-main">
            {{ $slot }}
        </main>
    </div>
</body>
</html>
