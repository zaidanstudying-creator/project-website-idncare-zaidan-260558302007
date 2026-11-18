<div class="contents">
<section class="password-change-page">
    <header class="password-change-banner"><span class="password-change-icon" aria-hidden="true">✓</span><div><p class="password-eyebrow">KEAMANAN AKUN</p><h1>Amankan akunmu</h1><p>Password awal atau hasil reset perlu diganti sebelum kamu melanjutkan.</p></div></header>
    <div class="password-change-card">
    <div class="password-card-heading"><h2>Buat password baru</h2><p>Gunakan password yang berbeda dari password sementara dan minimal 8 karakter.</p></div>
    @if ($errors->any())<div role="alert" class="portal-alert error">{{ $errors->first() }}</div>@endif
    <form wire:submit="savePassword" class="password-change-form">
        @foreach ([['current_password','Password saat ini','current-password'],['password','Password baru','new-password'],['password_confirmation','Ulangi password baru','new-password']] as [$field,$label,$autocomplete])
        <div><label for="{{ $field }}" class="mb-1.5 block text-sm font-semibold">{{ $label }}</label><div class="password-input-wrap"><input id="{{ $field }}" wire:model="{{ $field }}" type="password" required autocomplete="{{ $autocomplete }}" class="portal-input" @if ($errors->has($field)) aria-invalid="true" aria-describedby="{{ $field }}-error" @endif><x-password-visibility-toggle :target="$field" /></div>@error($field)<small id="{{ $field }}-error" class="field-error">{{ $message }}</small>@enderror</div>
        @endforeach
        <button type="submit" wire:loading.attr="disabled" wire:target="savePassword" class="button button-primary password-save-button"><span wire:loading.remove wire:target="savePassword">Simpan password baru</span><span wire:loading wire:target="savePassword">Menyimpan…</span></button>
    </form>
    <p class="password-change-footnote">Password baru hanya digunakan untuk akun IDN CARE ini.</p>
    </div>
</section>
</div>
