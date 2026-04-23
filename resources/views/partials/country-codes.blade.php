@php
    $countryCodes = \App\Modules\Referentiel\Models\ReferenceData::forCategory('country_code');
@endphp
@foreach($countryCodes as $cc)
<option value="{{ $cc->code }}" {{ old('country_code') === $cc->code ? 'selected' : '' }}>{{ $cc->label_fr }} ({{ $cc->code }})</option>
@endforeach
