<form class="app-form" method="POST" action="{{ $action }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="app-form-grid">
        <x-form.field name="name" label="Nom" :required="true">
            <input
                class="form-control"
                id="name"
                name="name"
                maxlength="100"
                value="{{ old('name', $user?->name) }}"
                autocomplete="family-name"
                required
            >
        </x-form.field>

        <x-form.field name="firstname" label="Prénom" :required="true">
            <input
                class="form-control"
                id="firstname"
                name="firstname"
                maxlength="100"
                value="{{ old('firstname', $user?->firstname) }}"
                autocomplete="given-name"
                required
            >
        </x-form.field>

        <x-form.field name="email" label="E-mail" :required="true">
            <input
                class="form-control"
                id="email"
                name="email"
                type="email"
                maxlength="191"
                value="{{ old('email', $user?->email) }}"
                autocomplete="email"
                required
            >
        </x-form.field>

        <x-form.field name="phone" label="Téléphone">
            <input
                class="form-control"
                id="phone"
                name="phone"
                type="tel"
                maxlength="30"
                value="{{ old('phone', $user?->phone) }}"
                autocomplete="tel"
            >
        </x-form.field>

        <x-form.field name="role_id" label="Rôle" :required="true">
            <select class="form-control" id="role_id" name="role_id" required>
                <option value="">Choisir un rôle</option>
                @foreach ($roles as $role)
                    <option
                        value="{{ $role->id }}"
                        data-role-code="{{ strtoupper($role->code) }}"
                        @selected((string) old('role_id', $user?->role_id) === (string) $role->id)
                        @disabled(strtoupper($role->code) === 'DIRECTEUR' && $directions->isEmpty())
                    >
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>
        </x-form.field>

        <x-form.field name="direction_id" label="Direction (obligatoire pour DIRECTEUR)">
            <select class="form-control" id="direction_id" name="direction_id">
                <option value="">Aucune direction</option>
                @foreach ($directions as $direction)
                    <option
                        value="{{ $direction->id }}"
                        @selected((string) old('direction_id', $user?->direction_id) === (string) $direction->id)
                    >
                        {{ $direction->name }}
                    </option>
                @endforeach
            </select>
        </x-form.field>

        <x-form.field name="password" :label="$user ? 'Nouveau mot de passe' : 'Mot de passe initial'" :required="! $user">
            <input
                class="form-control"
                id="password"
                name="password"
                type="password"
                minlength="8"
                autocomplete="new-password"
                @required(! $user)
            >
        </x-form.field>

        <x-form.field
            name="password_confirmation"
            label="Confirmation du mot de passe"
            :required="! $user"
        >
            <input
                class="form-control"
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                minlength="8"
                autocomplete="new-password"
                @required(! $user)
            >
        </x-form.field>
    </div>

    @if ($directions->isEmpty())
        <p class="form-information">
            Aucune direction n'est enregistrée. Un compte DIRECTEUR ne peut pas être créé tant qu'une direction n'est pas disponible.
        </p>
    @endif

    @if ($user)
        <p class="form-information">Laissez les deux champs de mot de passe vides pour conserver le mot de passe actuel.</p>
    @else
        <p class="form-information">Le mot de passe est traité par le service de gestion des comptes et n'est jamais réaffiché.</p>
    @endif

    <div class="app-form-actions">
        <button class="app-button app-button--primary" type="submit">{{ $submitLabel }}</button>
        @if ($user)
            <a class="app-button app-button--secondary" href="{{ route('admin.users.show', $user) }}">Annuler</a>
        @else
            <a class="app-button app-button--secondary" href="{{ route('admin.users.index') }}">Annuler</a>
        @endif
    </div>
</form>

<script>
    (() => {
        const roleSelect = document.getElementById('role_id');
        const directionSelect = document.getElementById('direction_id');

        if (!roleSelect || !directionSelect) {
            return;
        }

        const updateDirectionRequirement = () => {
            const selectedOption = roleSelect.options[roleSelect.selectedIndex];
            const requiresDirection = selectedOption?.dataset.roleCode === 'DIRECTEUR';

            directionSelect.required = requiresDirection;
            directionSelect.setAttribute('aria-required', String(requiresDirection));
        };

        roleSelect.addEventListener('change', updateDirectionRequirement);
        updateDirectionRequirement();
    })();
</script>
