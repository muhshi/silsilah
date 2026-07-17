<x-layouts::auth title="Lupa Kata Sandi">
    <div class="flex flex-col gap-6">
        <x-auth-header title="Lupa kata sandi?" description="Masukkan email Anda dan kami akan kirimkan tautan untuk mengatur ulang kata sandi" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                label="Alamat email"
                type="email"
                required
                autofocus
                placeholder="nama@email.com"
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="email-password-reset-link-button">
                Kirim tautan reset
            </flux:button>
        </form>

        <div class="text-center text-sm text-on-surface-variant">
            <span>Atau, kembali ke</span>
            <flux:link :href="route('login')" wire:navigate>halaman masuk</flux:link>
        </div>
    </div>
</x-layouts::auth>
