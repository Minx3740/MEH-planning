@if ($errors->any())
    <div class="form-error-summary" role="alert" aria-labelledby="form-errors-title">
        <p id="form-errors-title">Certains champs nécessitent votre attention.</p>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
