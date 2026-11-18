<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes sessions - MEH</title>
</head>
<body>
    <main>
        <h1>Mes sessions</h1>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

        @if ($sessions->isEmpty())
            <p>Aucune session active.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Appareil</th>
                        <th>Système</th>
                        <th>IP</th>
                        <th>Connexion</th>
                        <th>Dernière activité</th>
                        <th>État</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sessions as $session)
                        <tr>
                            <td>{{ $session->device_type ?? 'Inconnu' }}</td>
                            <td>{{ $session->operating_system ?? 'Inconnu' }}</td>
                            <td>{{ $session->ip_address ?? '-' }}</td>
                            <td>{{ $session->connected_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td>{{ $session->last_activity_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td>
                                @if ($session->session_key_hash === $currentSessionHash)
                                    Session courante
                                @elseif ($session->status === 'ACTIVE')
                                    Active
                                @else
                                    Révoked
                                @endif
                            </td>
                            <td>
                                @if ($session->status === 'ACTIVE' && $session->session_key_hash !== $currentSessionHash)
                                    <form method="POST" action="{{ route('sessions.revoke', $session) }}">
                                        @csrf
                                        <button type="submit">Révoquer</button>
                                    </form>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <p><a href="{{ route('dashboard') }}">Retour au tableau de bord</a></p>
    </main>
</body>
</html>
