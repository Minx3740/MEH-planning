<x-layouts.public title="Demande de compte">
    <section class="public-card public-card--wide" aria-labelledby="account-request-title">
        <h1 id="account-request-title">Demande de création de compte</h1>
        <x-ui.status-message />
        <x-form.errors />

        @if ($directions->isEmpty())
            <p class="form-information" role="status">
                Aucune direction n'est actuellement disponible. Les demandes de DIRECTEUR sont temporairement impossibles ; les demandes SG restent possibles.
            </p>
        @endif

        <form method="POST" action="{{ route('account-requests.store') }}">
            @csrf

            <div class="form-grid">
                <x-form.field name="name" label="Nom" required>
                    <input class="form-control" id="name" name="name" type="text" value="{{ old('name') }}" required @if ($errors->has('name')) aria-invalid="true" aria-describedby="name-error" @endif>
                </x-form.field>

                <x-form.field name="firstname" label="Prénom" required>
                    <input class="form-control" id="firstname" name="firstname" type="text" value="{{ old('firstname') }}" required @if ($errors->has('firstname')) aria-invalid="true" aria-describedby="firstname-error" @endif>
                </x-form.field>

                <x-form.field name="email" label="E-mail" required>
                    <input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif>
                </x-form.field>

                <x-form.field name="phone" label="Téléphone">
                    <input class="form-control" id="phone" name="phone" type="text" value="{{ old('phone') }}" autocomplete="tel" @if ($errors->has('phone')) aria-invalid="true" aria-describedby="phone-error" @endif>
                </x-form.field>

                <x-form.field name="password" label="Mot de passe" required>
                    <input class="form-control" id="password" name="password" type="password" required autocomplete="new-password" @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
                </x-form.field>

                <x-form.field name="password_confirmation" label="Confirmation du mot de passe" required>
                    <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" @if ($errors->has('password_confirmation')) aria-invalid="true" aria-describedby="password_confirmation-error" @endif>
                </x-form.field>

                <x-form.field name="requested_role_id" label="Rôle demandé" required>
                    <select class="form-control" id="requested_role_id" name="requested_role_id" required @if ($errors->has('requested_role_id')) aria-invalid="true" aria-describedby="requested_role_id-error" @endif>
                        <option value="">Choisir un rôle</option>
                        @foreach ($roles as $role)
                            <option
                                value="{{ $role->id }}"
                                @if ($role->code === 'DIRECTEUR' && $directions->isEmpty()) disabled @endif
                                {{ old('requested_role_id') == $role->id ? 'selected' : '' }}
                            >{{ $role->name }}</option>
                        @endforeach
                    </select>
                </x-form.field>

                <x-form.field name="direction_id" label="Direction (obligatoire pour DIRECTEUR)">
                    <select
                        class="form-control"
                        id="direction_id"
                        name="direction_id"
                        @disabled($directions->isEmpty())
                        @if ($errors->has('direction_id')) aria-invalid="true" aria-describedby="direction_id-error" @endif
                    >
                        <option value="">Sélectionner une direction</option>
                        @foreach ($directions as $direction)
                            <option value="{{ $direction->id }}" {{ old('direction_id') == $direction->id ? 'selected' : '' }}>{{ $direction->name }}</option>
                        @endforeach
                    </select>
                </x-form.field>
            </div>

            <div class="public-actions">
                <button class="public-button" type="submit">Soumettre la demande</button>
                <a class="public-secondary-link" href="{{ route('login') }}">Retour à la connexion</a>
            </div>
        </form>
    </section>

    <script>
        const roleSelect = document.getElementById('requested_role_id');
        const directionSelect = document.getElementById('direction_id');
        const directorRoleId = @json($directorRoleId);

        const updateDirectionRequirement = () => {
            directionSelect.required = directorRoleId !== null
                && roleSelect.value === String(directorRoleId)
                && !directionSelect.disabled;
        };

        roleSelect.addEventListener('change', updateDirectionRequirement);
        updateDirectionRequirement();
    </script>
</x-layouts.public>
