<x-layouts.app
    title="Utilisateurs"
    eyebrow="Administration"
    description="Consultez les comptes et filtrez la liste selon les critères disponibles."
>
    <x-ui.status-message />
    <x-form.errors />

    <div class="app-page-actions">
        <a class="app-button app-button--primary" href="{{ route('admin.users.create') }}">Créer un compte</a>
        <a class="app-button app-button--secondary" href="{{ route('admin.account-requests.index') }}">Demandes de comptes</a>
    </div>

    <form class="app-filter-form app-filter-form--users" method="GET" action="{{ route('admin.users.index') }}">
        <div class="form-field">
            <label for="user-search">Recherche</label>
            <input
                class="form-control"
                id="user-search"
                type="search"
                name="search"
                value="{{ request('search') }}"
                placeholder="Nom, prénom ou e-mail"
            >
        </div>

        <div class="form-field">
            <label for="user-role">Rôle</label>
            <select class="form-control" id="user-role" name="role_id">
                <option value="">Tous les rôles</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" @selected((string) request('role_id') === (string) $role->id)>
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-field">
            <label for="user-direction">Direction</label>
            <select class="form-control" id="user-direction" name="direction_id">
                <option value="">Toutes les directions</option>
                @foreach ($directions as $direction)
                    <option value="{{ $direction->id }}" @selected((string) request('direction_id') === (string) $direction->id)>
                        {{ $direction->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-field">
            <label for="user-status">Statut</label>
            <select class="form-control" id="user-status" name="status">
                <option value="">Tous les statuts</option>
                <option value="ACTIVE" @selected(strtoupper((string) request('status')) === 'ACTIVE')>Actif</option>
                <option value="INACTIVE" @selected(strtoupper((string) request('status')) === 'INACTIVE')>Désactivé</option>
            </select>
        </div>

        <div class="app-filter-actions">
            <button class="app-button app-button--primary" type="submit">Filtrer</button>
            @if (request()->query())
                <a class="app-button app-button--secondary" href="{{ route('admin.users.index') }}">Effacer les filtres</a>
            @endif
        </div>
    </form>

    @if ($users->isEmpty())
        <p class="app-empty-state">Aucun utilisateur ne correspond aux critères sélectionnés.</p>
    @else
        <div class="app-table-scroll app-table-scroll--users">
            <table class="app-table app-table--wide app-table--users">
                <thead>
                    <tr>
                        <th scope="col">Utilisateur</th>
                        <th scope="col">E-mail</th>
                        <th scope="col">Rôle</th>
                        <th scope="col">Direction</th>
                        <th scope="col">Statut</th>
                        <th scope="col">Créé le</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>
                                <a href="{{ route('admin.users.show', $user) }}">
                                    {{ $user->firstname }} {{ $user->name }}
                                </a>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->role?->name ?? '—' }}</td>
                            <td>{{ $user->direction?->name ?? '—' }}</td>
                            <td>
                                @if ($user->status === 'ACTIVE')
                                    <span class="app-status-badge app-status-badge--active">Actif</span>
                                @else
                                    <span class="app-status-badge app-status-badge--inactive">Désactivé</span>
                                @endif
                            </td>
                            <td>{{ $user->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>
                                <div class="app-user-row-actions">
                                    <a
                                        class="app-button app-button--secondary"
                                        href="{{ route('admin.users.show', $user) }}"
                                        aria-label="Consulter le compte de {{ $user->firstname }} {{ $user->name }}"
                                    >Consulter</a>

                                    @can('update', $user)
                                        <a
                                            class="app-button app-button--secondary"
                                            href="{{ route('admin.users.edit', $user) }}"
                                            aria-label="Modifier le compte de {{ $user->firstname }} {{ $user->name }}"
                                        >Modifier</a>
                                    @endcan

                                    @if ($user->status === 'ACTIVE')
                                        @can('deactivate', $user)
                                            <form
                                                class="app-inline-action"
                                                method="POST"
                                                action="{{ route('admin.users.deactivate', $user) }}"
                                                onsubmit="return confirm('Désactiver ce compte et révoquer ses sessions actives ?')"
                                            >
                                                @csrf
                                                @method('PATCH')
                                                <button
                                                    class="app-button app-button--secondary"
                                                    type="submit"
                                                    aria-label="Désactiver le compte de {{ $user->firstname }} {{ $user->name }}"
                                                >Désactiver</button>
                                            </form>
                                        @endcan
                                    @else
                                        @can('activate', $user)
                                            <form
                                                class="app-inline-action"
                                                method="POST"
                                                action="{{ route('admin.users.activate', $user) }}"
                                                onsubmit="return confirm('Réactiver ce compte ? Les anciennes sessions ne seront pas restaurées.')"
                                            >
                                                @csrf
                                                @method('PATCH')
                                                <button
                                                    class="app-button app-button--primary"
                                                    type="submit"
                                                    aria-label="Réactiver le compte de {{ $user->firstname }} {{ $user->name }}"
                                                >Réactiver</button>
                                            </form>
                                        @endcan
                                    @endif

                                    @if ($user->active_sessions_count > 0)
                                        @can('revokeSessions', $user)
                                            <form
                                                class="app-inline-action"
                                                method="POST"
                                                action="{{ route('admin.users.revoke-sessions', $user) }}"
                                                onsubmit="return confirm('Révoquer toutes les sessions actives de ce compte ? Le compte ne sera pas désactivé.')"
                                            >
                                                @csrf
                                                @method('PATCH')
                                                <button
                                                    class="app-button app-button--secondary"
                                                    type="submit"
                                                    aria-label="Forcer la déconnexion de toutes les sessions de {{ $user->firstname }} {{ $user->name }}"
                                                >Déconnexion ({{ $user->active_sessions_count }})</button>
                                            </form>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="app-pagination">
            {{ $users->links() }}
        </div>
    @endif
</x-layouts.app>
