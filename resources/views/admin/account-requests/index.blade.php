<x-layouts.app
    title="Demandes de comptes"
    eyebrow="Administration"
    description="Consultez les demandes et suivez leur état de traitement."
>
    <x-ui.status-message />
    <x-form.errors />

    <div class="app-page-actions">
        <a class="app-button app-button--secondary" href="{{ route('admin.users.index') }}">Gestion des utilisateurs</a>
    </div>

    <form class="app-filter-form" method="GET" action="{{ route('admin.account-requests.index') }}">
        <div class="form-field">
            <label for="request-search">Recherche</label>
            <input
                class="form-control"
                id="request-search"
                type="search"
                name="search"
                value="{{ request('search') }}"
                placeholder="Nom, prénom ou e-mail"
            >
        </div>

        <div class="form-field">
            <label for="request-status">Statut</label>
            <select class="form-control" id="request-status" name="status">
                <option value="">Tous les statuts</option>
                <option value="PENDING" @selected(request('status') === 'PENDING')>En attente</option>
                <option value="APPROVED" @selected(request('status') === 'APPROVED')>Approuvée</option>
                <option value="REJECTED" @selected(request('status') === 'REJECTED')>Refusée</option>
            </select>
        </div>

        <div class="app-filter-actions">
            <button class="app-button app-button--primary" type="submit">Filtrer</button>
            @if (request()->filled('search') || request()->filled('status'))
                <a class="app-button app-button--secondary" href="{{ route('admin.account-requests.index') }}">Effacer les filtres</a>
            @endif
        </div>
    </form>

    @if ($requests->isEmpty())
        <p class="app-empty-state">Aucune demande ne correspond aux critères sélectionnés.</p>
    @else
        @php
            $statusLabels = [
                'PENDING' => 'En attente',
                'APPROVED' => 'Approuvée',
                'REJECTED' => 'Refusée',
            ];
        @endphp

        <div class="app-table-scroll">
            <table class="app-table app-table--wide">
                <thead>
                    <tr>
                        <th scope="col">Candidat</th>
                        <th scope="col">E-mail</th>
                        <th scope="col">Rôle demandé</th>
                        <th scope="col">Direction</th>
                        <th scope="col">Statut</th>
                        <th scope="col">Soumise le</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requests as $requestItem)
                        @php
                            $statusClass = match ($requestItem->status) {
                                'PENDING' => 'pending',
                                'APPROVED' => 'approved',
                                'REJECTED' => 'rejected',
                                default => 'unknown',
                            };
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('admin.account-requests.show', $requestItem) }}">
                                    {{ $requestItem->firstname }} {{ $requestItem->name }}
                                </a>
                            </td>
                            <td>{{ $requestItem->email }}</td>
                            <td>{{ $requestItem->requestedRole?->name ?? '—' }}</td>
                            <td>
                                @if ($requestItem->requestedRole?->isDirector())
                                    {{ $requestItem->direction?->name ?? '—' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <span class="app-status-badge app-status-badge--{{ $statusClass }}">
                                    {{ $statusLabels[$requestItem->status] ?? $requestItem->status }}
                                </span>
                            </td>
                            <td>{{ $requestItem->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="app-pagination">
            {{ $requests->links() }}
        </div>
    @endif
</x-layouts.app>
