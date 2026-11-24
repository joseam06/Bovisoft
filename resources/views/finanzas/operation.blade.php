<input type="hidden" name="operation_uuid" value="{{ old('operation_uuid', (string) \Illuminate\Support\Str::uuid()) }}">
<input type="hidden" name="record_uuid" value="{{ $movement->client_uuid ?? old('record_uuid', (string) \Illuminate\Support\Str::uuid()) }}">
<input type="hidden" name="device_uuid" value="{{ old('device_uuid', (string) \Illuminate\Support\Str::uuid()) }}">
@isset($movement)<input type="hidden" name="base_version" value="{{ $movement->version }}">@endisset
