<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Accueil - Exercice</title>
</head>
<body>
    <h1>Bienvenue, {{$user->nom}}!</h1>
    <p>Vous êtes maintenant connecté à l'application.<br> Ton email est {{ $user->email }}</p>

    <form action="{{ route('exercice.deconnexion') }}" method="post">
        @csrf
        <button type="submit">Se déconnecter</button>
    </form>
</body>
</html>