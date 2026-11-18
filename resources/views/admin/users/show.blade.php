<x-layouts.app
    title="Compte utilisateur"
    eyebrow="Gestion des utilisateurs"
    description="Consultez les informations du compte et les actions disponibles."
>
    <x-ui.status-message />
    <x-form.errors />

    <div class="app-page-actions">
        <a class="app-button app-button--secondary" href="{{ route('admin.users.index') }}">Retour aux utilisateurs</a>
        @can('update', $user)
            <a class="app-button app-button--primary" href="{{ route('admin.users.edit', $user) }}">Modifier le compte</a>
        @endcan
    </div>

    <section class="app-detail-card" aria-label="Informations sur le compte">
        <dl class="app-detail-grid">
            <div>
                <dt>Nom</dt>
                <dd>{{ $user->name }}</dd>
            </div>
            <div>
                <dt>Prénom</dt>
                <dd>{{ $user->firstname }}</dd>
            </div>
            <div>
                <dt>E-mail</dt>
                <dd><a href="mailto:{{ $user->email }}">{{ $user->email }}</a></dd>
            </div>
            <div>
                <dt>Téléphone</dt>
                <dd>{{ $user->phone ?: '—' }}</dd>
            </div>
            <div>
                <dt>Rôle</dt>
                <dd>{{ $user->role?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt>Direction</dt>
                <dd>{{ $user->direction?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt>Statut</dt>
                <dd>
                    @if ($user->status === 'ACTIVE')
                        <span class="app-status-badge app-status-badge--active">Actif</span>
                    @else
                        <span class="app-status-badge app-status-badge--inactive">Désactivé</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt>Date de création</dt>
                <dd>{{ $user->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>
            </div>
            <div>
                <dt>Sessions actives</dt>
                <dd>{{ $user->active_sessions_count }}</dd>
            </div>
        </dl>
    </section>

    <section class="app-decision-section" aria-labelledby="account-actions-title">
        <h2 class="app-section-heading" id="account-actions-title">Actions sur le compte</h2>
        <div class="app-decision-actions">
            @if ($user->status === 'ACTIVE')
                @can('deactivate', $user)
                    <form
                        method="POST"
                        action="{{ route('admin.users.deactivate', $user) }}"
                        onsubmit="return confirm('Confirmer la désactivation de ce compte ? Ses sessions actives seront révoquées.')"
                    >
                        @csrf
                        @method('PATCH')
                        <button class="app-button app-button--secondary" type="submit">Désactiver le compte</button>
                    </form>
                @endcan
            @else
                @can('activate', $user)
                    <form
                        method="POST"
                        action="{{ route('admin.users.activate', $user) }}"
                        onsubmit="return confirm('Confirmer la réactivation de ce compte ? Les anciennes sessions ne seront pas restaurées.')"
                    >
                        @csrf
                        @method('PATCH')
                        <button class="app-button app-button--primary" type="submit">Activer le compte</button>
                    </form>
                @endcan
            @endif

            @if ($user->active_sessions_count > 0)
                @can('revokeSessions', $user)
                    <form
                        method="POST"
                        action="{{ route('admin.users.revoke-sessions', $user) }}"
                        onsubmit="return confirm('Révoquer toutes les sessions actives de ce compte ? Le compte ne sera pas désactivé.')"
                    >
                        @csrf
                        @method('PATCH')
                        <button class="app-button app-button--secondary" type="submit">Forcer la déconnexion de toutes les sessions</button>
                    </form>
                @else
                    <p class="app-empty-state">La déconnexion forcée de votre propre compte n'est pas disponible ici.</p>
                @endcan
            @else
                <p class="app-empty-state">Aucune session active à révoquer.</p>
            @endif
        </div>
    </section>
</x-layouts.app>
