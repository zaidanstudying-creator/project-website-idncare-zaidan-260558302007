@if (session('status'))
    <x-states.success-state>{{ session('status') }}</x-states.success-state>
@endif

@if (session('error'))
    <x-states.error-state>{{ session('error') }}</x-states.error-state>
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
