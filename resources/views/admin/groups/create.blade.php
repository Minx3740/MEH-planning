<x-layouts.app
    title="Créer un groupe"
    eyebrow="Groupes"
    description="Renseignez le nom et, si nécessaire, la description du groupe."
>
    <x-form.errors />

    <section class="app-form-card" aria-label="Informations du groupe">
        @include('admin.groups._form', [
            'group' => $group,
            'action' => route('admin.groups.store'),
            'method' => 'POST',
        ])
    </section>
</x-layouts.app>
