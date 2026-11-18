<x-layouts.app
    title="Détail de la demande"
    eyebrow="Demandes de comptes"
    description="Consultez les informations transmises et l'historique de décision disponible."
>
    <x-ui.status-message />
    <x-form.errors />

    <div class="app-page-actions">
        <a class="app-button app-button--secondary" href="{{ route('admin.account-requests.index') }}">Retour aux demandes</a>
    </div>

    @php
        $statusLabels = [
            'PENDING' => 'En attente',
            'APPROVED' => 'Approuvée',
            'REJECTED' => 'Refusée',
        ];
        $statusClass = match ($accountRequest->status) {
            'PENDING' => 'pending',
            'APPROVED' => 'approved',
            'REJECTED' => 'rejected',
            default => 'unknown',
        };
    @endphp

    <section class="app-detail-card" aria-label="Informations sur la demande">
        <dl class="app-detail-grid">
            <div>
                <dt>Nom</dt>
                <dd>{{ $accountRequest->name }}</dd>
            </div>
            <div>
                <dt>Prénom</dt>
                <dd>{{ $accountRequest->firstname }}</dd>
            </div>
            <div>
                <dt>E-mail</dt>
                <dd><a href="mailto:{{ $accountRequest->email }}">{{ $accountRequest->email }}</a></dd>
            </div>
            <div>
                <dt>Rôle demandé</dt>
                <dd>{{ $accountRequest->requestedRole?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt>Direction</dt>
                <dd>
                    @if ($accountRequest->requestedRole?->isDirector())
                        {{ $accountRequest->direction?->name ?? '—' }}
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div>
                <dt>Téléphone</dt>
                <dd>{{ $accountRequest->phone ?: '—' }}</dd>
            </div>
            <div>
                <dt>Statut</dt>
                <dd>
                    <span class="app-status-badge app-status-badge--{{ $statusClass }}">
                        {{ $statusLabels[$accountRequest->status] ?? $accountRequest->status }}
                    </span>
                </dd>
            </div>
            <div>
                <dt>Date de soumission</dt>
                <dd>{{ $accountRequest->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>
            </div>
            @if ($accountRequest->reviewer)
                <div>
                    <dt>Traitée par</dt>
                    <dd>{{ $accountRequest->reviewer->firstname }} {{ $accountRequest->reviewer->name }}</dd>
                </div>
                <div>
                    <dt>Date de décision</dt>
                    <dd>{{ $accountRequest->reviewed_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                </div>
            @endif
            @if ($accountRequest->decision_reason)
                <div class="app-detail-item--wide">
                    <dt>Motif de la décision</dt>
                    <dd>{{ $accountRequest->decision_reason }}</dd>
                </div>
            @endif
            @if ($accountRequest->createdUser)
                <div class="app-detail-item--wide">
                    <dt>Compte créé</dt>
                    <dd>
                        <a href="{{ route('admin.users.show', $accountRequest->createdUser) }}">
                            {{ $accountRequest->createdUser->firstname }} {{ $accountRequest->createdUser->name }}
                        </a>
                    </dd>
                </div>
            @endif
        </dl>
    </section>

    @if ($accountRequest->status === 'PENDING')
        <section class="app-decision-section" aria-labelledby="decision-title">
            <h2 class="app-section-heading" id="decision-title">Décision</h2>
            <div class="app-decision-actions">
                <form
                    method="POST"
                    action="{{ route('admin.account-requests.approve', $accountRequest) }}"
                    onsubmit="return confirm('Confirmer l’approbation de cette demande ?')"
                >
                    @csrf
                    <button class="app-button app-button--primary" type="submit">Approuver la demande</button>
                </form>

                <form
                    class="app-form-card app-decision-form"
                    method="POST"
                    action="{{ route('admin.account-requests.reject', $accountRequest) }}"
                    onsubmit="return confirm('Confirmer le refus de cette demande ?')"
                >
                    @csrf
                    <div class="form-field">
                        <label for="decision_reason">Motif du refus (facultatif)</label>
                        <textarea
                            class="form-control"
                            id="decision_reason"
                            name="decision_reason"
                            rows="3"
                        >{{ old('decision_reason') }}</textarea>
                    </div>
                    <button class="app-button app-button--secondary" type="submit">Refuser la demande</button>
                </form>
            </div>
        </section>
    @endif
</x-layouts.app>
