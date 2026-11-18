<x-layouts.app
    title="Modifier un compte"
    eyebrow="Gestion des utilisateurs"
    description="Modifiez les informations du compte. Les changements de rôle ou de direction restent soumis aux règles métier."
>
    <x-ui.status-message />
    <x-form.errors />

    <div class="app-page-actions">
        <a class="app-button app-button--secondary" href="{{ route('admin.users.show', $user) }}">Retour au compte</a>
    </div>

    <section class="app-form-card" aria-label="Modification du compte">
        @include('admin.users._form', [
            'user' => $user,
            'roles' => $roles,
            'directions' => $directions,
            'action' => route('admin.users.update', $user),
            'method' => 'PUT',
            'submitLabel' => 'Enregistrer les modifications',
        ])
    </section>
</x-layouts.app>
