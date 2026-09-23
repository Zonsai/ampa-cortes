@php
    $fieldId = $field->id;
    $inputName = "fields[$fieldId]";
    $errorKey = "field_$fieldId";
    $currentValue = old("fields.$fieldId", $value ?? null);
    $hasError = $errors->has($errorKey);
@endphp

<div class="family-field">
    @if($field->type !== \App\Enums\FormFieldType::InfoText)
        <label class="family-label">
            {{ $field->label }}@if($field->is_required)<span class="req">*</span>@endif
        </label>
    @endif

    @if($field->description && $field->type !== \App\Enums\FormFieldType::InfoText)
        <p class="fam-muted" style="font-size: .8rem; margin: 0 0 8px;">{{ $field->description }}</p>
    @endif

    @switch($field->type)
        @case(\App\Enums\FormFieldType::TextShort)
            <input type="text" name="{{ $inputName }}" value="{{ $currentValue }}"
                   class="family-input @if($hasError) family-input--error @endif">
            @break

        @case(\App\Enums\FormFieldType::TextLong)
            <textarea name="{{ $inputName }}" rows="4"
                      class="family-input @if($hasError) family-input--error @endif">{{ $currentValue }}</textarea>
            @break

        @case(\App\Enums\FormFieldType::Number)
            <input type="number" name="{{ $inputName }}" value="{{ $currentValue }}"
                   class="family-input @if($hasError) family-input--error @endif" style="max-width: 220px;">
            @break

        @case(\App\Enums\FormFieldType::Date)
            <input type="date" name="{{ $inputName }}" value="{{ $currentValue }}"
                   class="family-input @if($hasError) family-input--error @endif" style="max-width: 220px;">
            @break

        @case(\App\Enums\FormFieldType::Email)
            <input type="email" name="{{ $inputName }}" value="{{ $currentValue }}"
                   class="family-input @if($hasError) family-input--error @endif">
            @break

        @case(\App\Enums\FormFieldType::Phone)
            <input type="tel" name="{{ $inputName }}" value="{{ $currentValue }}"
                   class="family-input @if($hasError) family-input--error @endif" style="max-width: 220px;">
            @break

        @case(\App\Enums\FormFieldType::Select)
            <select name="{{ $inputName }}" class="family-input @if($hasError) family-input--error @endif">
                <option value="">— Selecciona una opción —</option>
                @foreach($field->options ?? [] as $option)
                    <option value="{{ $option }}" @selected($currentValue === $option)>{{ $option }}</option>
                @endforeach
            </select>
            @break

        @case(\App\Enums\FormFieldType::Radio)
            <div style="display: grid; gap: 8px;">
                @foreach($field->options ?? [] as $option)
                    <label class="family-check">
                        <input type="radio" name="{{ $inputName }}" value="{{ $option }}"
                               @checked($currentValue === $option)>
                        {{ $option }}
                    </label>
                @endforeach
            </div>
            @break

        @case(\App\Enums\FormFieldType::Checkbox)
            <input type="hidden" name="{{ $inputName }}" value="0">
            <label class="family-check">
                <input type="checkbox" name="{{ $inputName }}" value="1"
                       @checked($currentValue === '1' || $currentValue === 1 || $currentValue === true)>
                Sí
            </label>
            @break

        @case(\App\Enums\FormFieldType::Checkboxes)
            @php
                if (is_array($currentValue)) {
                    $selectedOptions = $currentValue;
                } elseif (is_string($currentValue) && $currentValue !== '') {
                    $selectedOptions = json_decode($currentValue, true) ?? [];
                } else {
                    $selectedOptions = [];
                }
            @endphp
            <div style="display: grid; gap: 8px;">
                @foreach($field->options ?? [] as $option)
                    <label class="family-check">
                        <input type="checkbox" name="{{ $inputName }}[]" value="{{ $option }}"
                               @checked(in_array($option, $selectedOptions, true))>
                        {{ $option }}
                    </label>
                @endforeach
            </div>
            @break

        @case(\App\Enums\FormFieldType::YesNo)
            <div style="display: flex; gap: 20px;">
                <label class="family-check">
                    <input type="radio" name="{{ $inputName }}" value="1"
                           @checked($currentValue === '1' || $currentValue === 1 || $currentValue === true)>
                    Sí
                </label>
                <label class="family-check">
                    <input type="radio" name="{{ $inputName }}" value="0"
                           @checked($currentValue === '0' || $currentValue === 0)>
                    No
                </label>
            </div>
            @break

        @case(\App\Enums\FormFieldType::InfoText)
            <div class="family-alert family-alert--info">
                <span>
                    {{ $field->label }}
                    @if($field->description)
                        <span class="fam-muted" style="display: block; margin-top: 4px; font-size: .8rem;">{{ $field->description }}</span>
                    @endif
                </span>
            </div>
            @break
    @endswitch

    @error($errorKey)
        <p class="family-field-error">{{ $message }}</p>
    @enderror
</div>
