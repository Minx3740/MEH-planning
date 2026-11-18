@props([
    'name',
    'label',
    'required' => false,
])

<div class="form-field">
    <label for="{{ $name }}">
        {{ $label }}
        @if ($required)
            <span class="form-required" aria-hidden="true">*</span>
        @endif
    </label>
    {{ $slot }}
    @error($name)
        <p class="form-error" id="{{ $name }}-error" role="alert">{{ $message }}</p>
    @enderror
</div>
