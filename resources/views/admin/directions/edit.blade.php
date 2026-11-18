<x-layouts.app
    title="Modifier une direction"
    eyebrow="Directions"
    description="Modifiez les informations et le groupe de rattachement de la direction."
>
    <x-form.errors />

    <section class="app-form-card" aria-label="Modification de la direction">
        @include('admin.directions._form', [
            'direction' => $direction,
            'groups' => $groups,
            'action' => route('admin.directions.update', $direction),
            'method' => 'PUT',
        ])
    </section>
</x-layouts.app>
