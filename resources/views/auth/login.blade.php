
<x-layouts.public title="Connexion">
    <section class="public-card" aria-labelledby="login-title">
        <h1 id="login-title">Connexion</h1>
        <p>Connectez-vous pour accéder à votre espace MEH.</p>

        <form method="POST" action="{{ route('login.store') }}">
            @csrf

            <x-form.field name="email" label="Adresse e-mail" required>
                <input
                    class="form-control"
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="username"
                    @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif
                >
            </x-form.field>

            <x-form.field name="password" label="Mot de passe" required>
                <input
                    class="form-control"
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif
                >
            </x-form.field>

            <label class="remember-field">
                <input type="checkbox" name="remember" value="1">
                Se souvenir de moi
            </label>

            <button class="public-button" type="submit">Se connecter</button>
        </form>

        <p><a class="public-secondary-link" href="{{ route('account-requests.create') }}">Demander la création d'un compte</a></p>
    </section>
</x-layouts.public>
