<x-layouts.app
    title="Créer un compte"
    eyebrow="Gestion des utilisateurs"
    description="Créez un compte en sélectionnant l'un des rôles autorisés."
>
    <x-ui.status-message />
    <x-form.errors />

    <div class="app-page-actions">
        <a class="app-button app-button--secondary" href="{{ route('admin.users.index') }}">Retour aux utilisateurs</a>
    </div>

    <section class="app-form-card" aria-label="Nouveau compte">
        @include('admin.users._form', [
            'user' => null,
            'roles' => $roles,
            'directions' => $directions,
            'action' => route('admin.users.store'),
            'method' => 'POST',
            'submitLabel' => 'Créer le compte',
        ])
    </section>
</x-layouts.app>
