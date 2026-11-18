<x-layouts.app
    title="Créer une direction"
    eyebrow="Directions"
    description="Chaque direction doit être rattachée à un groupe existant."
>
    <x-form.errors />

    <section class="app-form-card" aria-label="Informations de la direction">
        @include('admin.directions._form', [
            'direction' => $direction,
            'groups' => $groups,
            'action' => route('admin.directions.store'),
            'method' => 'POST',
        ])
    </section>
</x-layouts.app>
