@php
    $fieldId = $field->id;
    $inputName = "fields[$fieldId]";
    $errorKey = "field_$fieldId";
    $currentValue = old("fields.$fieldId", $value ?? null);
    $hasError = $errors->has($errorKey);
@endphp

<div class="mb-5">
    @if($field->type !== \App\Enums\FormFieldType::InfoText)
        <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ $field->label }}@if($field->is_required)<span class="text-red-500 ml-0.5">*</span>@endif
        </label>
    @endif

    @if($field->description && $field->type !== \App\Enums\FormFieldType::InfoText)
        <p class="text-xs text-gray-500 mb-1.5">{{ $field->description }}</p>
    @endif

    @switch($field->type)
        @case(\App\Enums\FormFieldType::TextShort)
            <input type="text" name="{{ $inputName }}" value="{{ $currentValue }}"
                   class="w-full rounded-lg border {{ $hasError ? 'border-red-400' : 'border-gray-300' }} px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @break

        @case(\App\Enums\FormFieldType::TextLong)
            <textarea name="{{ $inputName }}" rows="4"
                      class="w-full rounded-lg border {{ $hasError ? 'border-red-400' : 'border-gray-300' }} px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ $currentValue }}</textarea>
            @break

        @case(\App\Enums\FormFieldType::Number)
            <input type="number" name="{{ $inputName }}" value="{{ $currentValue }}"
                   class="w-48 rounded-lg border {{ $hasError ? 'border-red-400' : 'border-gray-300' }} px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @break

        @case(\App\Enums\FormFieldType::Date)
            <input type="date" name="{{ $inputName }}" value="{{ $currentValue }}"
                   class="w-48 rounded-lg border {{ $hasError ? 'border-red-400' : 'border-gray-300' }} px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @break

        @case(\App\Enums\FormFieldType::Email)
            <input type="email" name="{{ $inputName }}" value="{{ $currentValue }}"
                   class="w-full rounded-lg border {{ $hasError ? 'border-red-400' : 'border-gray-300' }} px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @break

        @case(\App\Enums\FormFieldType::Phone)
            <input type="tel" name="{{ $inputName }}" value="{{ $currentValue }}"
                   class="w-48 rounded-lg border {{ $hasError ? 'border-red-400' : 'border-gray-300' }} px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @break

        @case(\App\Enums\FormFieldType::Select)
            <select name="{{ $inputName }}"
                    class="w-full rounded-lg border {{ $hasError ? 'border-red-400' : 'border-gray-300' }} px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">— Selecciona una opción —</option>
                @foreach($field->options ?? [] as $option)
                    <option value="{{ $option }}" @selected($currentValue === $option)>{{ $option }}</option>
                @endforeach
            </select>
            @break

        @case(\App\Enums\FormFieldType::Radio)
            <div class="space-y-1.5">
                @foreach($field->options ?? [] as $option)
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="{{ $inputName }}" value="{{ $option }}"
                               @checked($currentValue === $option) class="text-indigo-600">
                        <span class="text-sm text-gray-700">{{ $option }}</span>
                    </label>
                @endforeach
            </div>
            @break

        @case(\App\Enums\FormFieldType::Checkbox)
            <input type="hidden" name="{{ $inputName }}" value="0">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="{{ $inputName }}" value="1"
                       @checked($currentValue === '1' || $currentValue === 1 || $currentValue === true)
                       class="text-indigo-600 rounded">
                <span class="text-sm text-gray-700">Sí</span>
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
            <div class="space-y-1.5">
                @foreach($field->options ?? [] as $option)
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="{{ $inputName }}[]" value="{{ $option }}"
                               @checked(in_array($option, $selectedOptions, true))
                               class="text-indigo-600 rounded">
                        <span class="text-sm text-gray-700">{{ $option }}</span>
                    </label>
                @endforeach
            </div>
            @break

        @case(\App\Enums\FormFieldType::YesNo)
            <div class="flex gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="{{ $inputName }}" value="1"
                           @checked($currentValue === '1' || $currentValue === 1 || $currentValue === true)
                           class="text-indigo-600">
                    <span class="text-sm text-gray-700">Sí</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="{{ $inputName }}" value="0"
                           @checked($currentValue === '0' || $currentValue === 0)
                           class="text-indigo-600">
                    <span class="text-sm text-gray-700">No</span>
                </label>
            </div>
            @break

        @case(\App\Enums\FormFieldType::InfoText)
            <div class="rounded-lg bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-800">
                {{ $field->label }}
                @if($field->description)
                    <p class="mt-1 text-xs text-blue-600">{{ $field->description }}</p>
                @endif
            </div>
            @break
    @endswitch

    @error($errorKey)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
