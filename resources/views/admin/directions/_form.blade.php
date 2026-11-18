<form method="POST" action="{{ $action }}">
    @csrf
    @if ($method === 'PUT')
        @method('PUT')
    @endif

    @if ($groups->isEmpty())
        <p class="form-information">
            Aucun groupe n'est enregistré. <a href="{{ route('admin.groups.create') }}">Créer un groupe</a> avant d'ajouter une direction.
        </p>
    @endif

    <div class="app-form-grid">
        <x-form.field name="group_id" label="Groupe de rattachement" required>
            <select
                class="form-control"
                id="group_id"
                name="group_id"
                required
                @disabled($groups->isEmpty())
                @if ($errors->has('group_id')) aria-invalid="true" aria-describedby="group_id-error" @endif
            >
                <option value="">Choisir un groupe</option>
                @foreach ($groups as $group)
                    <option
                        value="{{ $group->id }}"
                        {{ old('group_id', $direction->group_id) == $group->id ? 'selected' : '' }}
                    >{{ $group->name }}</option>
                @endforeach
            </select>
        </x-form.field>

        <x-form.field name="name" label="Nom de la direction" required>
            <input
                class="form-control"
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $direction->name) }}"
                maxlength="150"
                required
                @if ($errors->has('name')) aria-invalid="true" aria-describedby="name-error" @endif
            >
        </x-form.field>

        <x-form.field name="code" label="Code">
            <input
                class="form-control"
                id="code"
                name="code"
                type="text"
                value="{{ old('code', $direction->code) }}"
                maxlength="50"
                @if ($errors->has('code')) aria-invalid="true" aria-describedby="code-error" @endif
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
                >{{ old('description', $direction->description) }}</textarea>
            </x-form.field>
        </div>
    </div>

    <div class="app-form-actions">
        <button class="app-button app-button--primary" type="submit" @disabled($groups->isEmpty())>
            {{ $method === 'PUT' ? 'Enregistrer les modifications' : 'Créer la direction' }}
        </button>
        <a class="app-button app-button--secondary" href="{{ route('admin.directions.index') }}">Annuler</a>
    </div>
</form>
