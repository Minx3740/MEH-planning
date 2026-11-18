<form method="POST" action="{{ $action }}">
    @csrf
    @if ($method === 'PUT')
        @method('PUT')
    @endif

    <div class="app-form-grid">
        <x-form.field name="name" label="Nom du groupe" required>
            <input
                class="form-control"
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $group->name) }}"
                maxlength="100"
                required
                @if ($errors->has('name')) aria-invalid="true" aria-describedby="name-error" @endif
            >
        </x-form.field>

        <div class="form-field form-field--full">
            <x-form.field name="description" label="Description">
                <textarea
                    class="form-control"
                    id="description"
                    name="description"
                    rows="4"
                    @if ($errors->has('description')) aria-invalid="true" aria-describedby="description-error" @endif
                >{{ old('description', $group->description) }}</textarea>
            </x-form.field>
        </div>
    </div>

    <div class="app-form-actions">
        <button class="app-button app-button--primary" type="submit">
            {{ $method === 'PUT' ? 'Enregistrer les modifications' : 'Créer le groupe' }}
        </button>
        <a class="app-button app-button--secondary" href="{{ route('admin.groups.index') }}">Annuler</a>
    </div>
</form>
