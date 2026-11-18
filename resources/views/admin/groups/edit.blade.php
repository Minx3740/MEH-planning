<x-layouts.app
    title="Modifier un groupe"
    eyebrow="Groupes"
    description="Les directions rattachées à ce groupe seront conservées."
>
    <x-form.errors />

    <section class="app-form-card" aria-label="Modification du groupe">
        @include('admin.groups._form', [
            'group' => $group,
            'action' => route('admin.groups.update', $group),
            'method' => 'PUT',
        ])
    </section>
</x-layouts.app>
