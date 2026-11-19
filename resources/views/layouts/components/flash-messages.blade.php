@if (session('status'))
    <div hidden data-flash-notification data-type="success">{{ session('status') }}</div>
@endif

@if (session('error'))
    <div hidden data-flash-notification data-type="error">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <x-states.error-state title="Periksa kembali isian berikut.">
        <ul class="mt-2 list-inside list-disc">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-states.error-state>
@endif
