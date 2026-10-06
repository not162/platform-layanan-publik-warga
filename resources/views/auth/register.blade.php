@extends('layouts.auth')

@section('title', 'Pendaftaran Warga')

@section('content')
<div x-data="registerForm()" :class="{ 'animate-shake': isShaking }">
    <h2 class="text-xl font-bold text-slate-800 mb-1">Pendaftaran Warga</h2>
    <p class="text-sm text-slate-500 mb-6">Masukkan NIK dan data diri Anda untuk pendaftaran mandiri layanan portal RT.</p>

    <!-- Animated Error/Success Notification Alert -->
    <template x-if="message">
        <div x-show="message" 
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="-translate-y-2 opacity-0 scale-95"
             x-transition:enter-end="translate-y-0 opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             :class="isError ? 'bg-red-50 text-red-700 border-red-200' : 'bg-green-50 text-green-700 border-green-200'" 
             class="p-3.5 mb-6 rounded-xl text-sm border flex items-start justify-between gap-3 shadow-sm animate-slide-down">
            <div class="flex items-start gap-2.5">
                <div :class="isError ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'" class="p-1 rounded-lg shrink-0 mt-0.5">
                    <i :data-lucide="isError ? 'shield-alert' : 'check-circle-2'" class="w-4 h-4"></i>
                </div>
                <div>
                    <h4 class="font-semibold text-xs uppercase tracking-wider mb-0.5" x-text="isError ? 'Verifikasi Gagal' : 'Registrasi Berhasil'"></h4>
                    <p x-text="message" class="text-xs leading-relaxed opacity-90"></p>
                </div>
            </div>
            <button type="button" @click="message = null" class="text-slate-400 hover:text-slate-600 transition-colors shrink-0 p-1">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
        </div>
    </template>

    <form @submit.prevent="submit" class="space-y-4">
        <!-- NIK -->
        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="block text-sm font-medium text-slate-700">Nomor Induk Kependudukan (NIK)</label>
                <span class="text-xs font-mono font-medium transition-colors"
                      :class="form.nik.length === 16 ? 'text-green-600 font-semibold' : 'text-slate-400'"
                      x-text="form.nik.length + ' / 16'"></span>
            </div>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <i data-lucide="credit-card" :class="errors.nik ? 'text-red-500' : (form.nik.length === 16 ? 'text-green-600' : 'text-slate-400')" class="h-4 w-4 transition-colors"></i>
                </div>
                <input type="text" 
                       x-ref="nikInput"
                       x-model="form.nik" 
                       @input="form.nik = form.nik.replace(/\D/g, '').slice(0, 16)" 
                       :class="errors.nik ? 'border-red-400 focus:border-red-500 focus:ring-red-200 ring-2 ring-red-50 animate-pulse-error' : (form.nik.length === 16 ? 'border-green-400 focus:border-green-500 focus:ring-green-100' : 'border-slate-300 focus:border-blue-500 focus:ring-blue-500')"
                       class="block w-full pl-10 pr-10 py-2.5 border rounded-lg focus:ring-2 sm:text-sm text-slate-900 transition-all placeholder:text-slate-400" 
                       placeholder="16 digit NIK tertera di KTP/KK" 
                       required>
                
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <template x-if="errors.nik">
                        <i data-lucide="alert-circle" class="h-4 w-4 text-red-500"></i>
                    </template>
                    <template x-if="!errors.nik && form.nik.length === 16">
                        <i data-lucide="check-circle-2" class="h-4 w-4 text-green-500"></i>
                    </template>
                </div>
            </div>
            <p class="mt-1 text-xs text-red-600 font-medium" x-show="errors.nik" x-text="errors.nik ? errors.nik[0] : ''"></p>
        </div>

        <!-- Nama -->
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <i data-lucide="user" :class="errors.name ? 'text-red-500' : 'text-slate-400'" class="h-4 w-4 transition-colors"></i>
                </div>
                <input type="text" 
                       x-ref="nameInput"
                       x-model="form.name" 
                       :class="errors.name ? 'border-red-400 focus:border-red-500 focus:ring-red-200 ring-2 ring-red-50 animate-pulse-error' : 'border-slate-300 focus:border-blue-500 focus:ring-blue-500'"
                       class="block w-full pl-10 pr-3 py-2.5 border rounded-lg focus:ring-2 sm:text-sm text-slate-900 transition-all placeholder:text-slate-400" 
                       placeholder="Nama sesuai KTP" 
                       required>
            </div>
            <p class="mt-1 text-xs text-red-600 font-medium" x-show="errors.name" x-text="errors.name ? errors.name[0] : ''"></p>
        </div>

        <!-- Email -->
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Alamat Email</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <i data-lucide="mail" :class="errors.email ? 'text-red-500' : 'text-slate-400'" class="h-4 w-4 transition-colors"></i>
                </div>
                <input type="email" 
                       x-ref="emailInput"
                       x-model="form.email" 
                       :class="errors.email ? 'border-red-400 focus:border-red-500 focus:ring-red-200 ring-2 ring-red-50 animate-pulse-error' : 'border-slate-300 focus:border-blue-500 focus:ring-blue-500'"
                       class="block w-full pl-10 pr-10 py-2.5 border rounded-lg focus:ring-2 sm:text-sm text-slate-900 transition-all placeholder:text-slate-400" 
                       placeholder="email@contoh.com" 
                       required>
                
                <template x-if="errors.email">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-red-500">
                        <i data-lucide="alert-circle" class="h-4 w-4"></i>
                    </div>
                </template>
            </div>
            <p class="mt-1 text-xs text-red-600 font-medium" x-show="errors.email" x-text="errors.email ? errors.email[0] : ''"></p>
        </div>

        <!-- Password -->
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Kata Sandi</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <i data-lucide="lock" :class="errors.password ? 'text-red-500' : 'text-slate-400'" class="h-4 w-4 transition-colors"></i>
                </div>
                <input :type="showPassword ? 'text' : 'password'" 
                       x-ref="passwordInput"
                       x-model="form.password" 
                       :class="errors.password ? 'border-red-400 focus:border-red-500 focus:ring-red-200 ring-2 ring-red-50 animate-pulse-error' : 'border-slate-300 focus:border-blue-500 focus:ring-blue-500'"
                       class="block w-full pl-10 pr-10 py-2.5 border rounded-lg focus:ring-2 sm:text-sm text-slate-900 transition-all placeholder:text-slate-400" 
                       placeholder="Minimal 8 karakter" 
                       required>
                
                <button type="button" 
                        @click="showPassword = !showPassword; setTimeout(() => lucide.createIcons(), 10);" 
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 transition-colors">
                    <i :data-lucide="showPassword ? 'eye-off' : 'eye'" class="h-4 w-4"></i>
                </button>
            </div>
            <p class="mt-1 text-xs text-red-600 font-medium" x-show="errors.password" x-text="errors.password ? errors.password[0] : ''"></p>
        </div>

        <!-- Konfirmasi Password -->
        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="block text-sm font-medium text-slate-700">Ulangi Kata Sandi</label>
                <template x-if="form.password_confirmation.length > 0">
                    <span class="text-xs font-medium"
                          :class="form.password === form.password_confirmation ? 'text-green-600' : 'text-amber-600'"
                          x-text="form.password === form.password_confirmation ? 'Kata sandi cocok' : 'Belum sesuai'"></span>
                </template>
            </div>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <i data-lucide="shield-check" 
                       :class="form.password_confirmation.length > 0 && form.password === form.password_confirmation ? 'text-green-600' : 'text-slate-400'" 
                       class="h-4 w-4 transition-colors"></i>
                </div>
                <input :type="showPasswordConfirmation ? 'text' : 'password'" 
                       x-ref="confirmPasswordInput"
                       x-model="form.password_confirmation" 
                       :class="form.password_confirmation.length > 0 && form.password !== form.password_confirmation ? 'border-amber-400 focus:border-amber-500 focus:ring-amber-100' : 'border-slate-300 focus:border-blue-500 focus:ring-blue-500'"
                       class="block w-full pl-10 pr-10 py-2.5 border rounded-lg focus:ring-2 sm:text-sm text-slate-900 transition-all placeholder:text-slate-400" 
                       placeholder="Ulangi kata sandi" 
                       required>

                <button type="button" 
                        @click="showPasswordConfirmation = !showPasswordConfirmation; setTimeout(() => lucide.createIcons(), 10);" 
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 transition-colors">
                    <i :data-lucide="showPasswordConfirmation ? 'eye-off' : 'eye'" class="h-4 w-4"></i>
                </button>
            </div>
        </div>

        <button type="submit" 
                :disabled="isLoading || form.nik.length !== 16 || (form.password && form.password_confirmation && form.password !== form.password_confirmation)" 
                class="w-full mt-6 bg-blue-600 hover:bg-blue-700 active:scale-[0.99] disabled:bg-blue-300 disabled:cursor-not-allowed text-white font-medium py-2.5 px-4 rounded-lg transition-all shadow-sm flex justify-center items-center gap-2">
            <template x-if="isLoading">
                <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
            </template>
            <span x-text="isLoading ? 'Memverifikasi data warga...' : 'Daftar & Verifikasi'"></span>
        </button>
    </form>
    
    <div class="mt-6 text-center text-sm text-slate-600">
        Sudah memiliki akun? <a href="/login" class="font-semibold text-blue-600 hover:text-blue-800 transition-colors">Masuk di sini</a>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('registerForm', () => ({
        form: {
            nik: '',
            name: '',
            email: '',
            password: '',
            password_confirmation: ''
        },
        errors: {},
        isLoading: false,
        isShaking: false,
        showPassword: false,
        showPasswordConfirmation: false,
        message: null,
        isError: false,
        
        triggerErrorAnimation(fieldToFocus = 'nik') {
            this.isShaking = true;
            setTimeout(() => {
                this.isShaking = false;
            }, 500);

            // Audio haptic feedback halus
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (AudioContext) {
                    const ctx = new AudioContext();
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(140, ctx.currentTime);
                    osc.frequency.exponentialRampToValueAtTime(70, ctx.currentTime + 0.15);
                    gain.gain.setValueAtTime(0.12, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.15);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.15);
                }
            } catch (e) {
                // Ignore audio failure
            }

            // Fokuskan ke kolom input yang bermasalah
            setTimeout(() => {
                if (fieldToFocus === 'nik' && this.$refs.nikInput) {
                    this.$refs.nikInput.focus();
                } else if (fieldToFocus === 'email' && this.$refs.emailInput) {
                    this.$refs.emailInput.focus();
                } else if (fieldToFocus === 'name' && this.$refs.nameInput) {
                    this.$refs.nameInput.focus();
                } else if (fieldToFocus === 'password' && this.$refs.passwordInput) {
                    this.$refs.passwordInput.focus();
                }
                lucide.createIcons();
            }, 50);
        },

        async submit() {
            // Validasi lokal sebelum kirim
            if (this.form.password !== this.form.password_confirmation) {
                this.isError = true;
                this.message = "Konfirmasi kata sandi tidak cocok. Mohon ketik ulang.";
                this.triggerErrorAnimation('password');
                return;
            }

            this.isLoading = true;
            this.errors = {};
            this.message = null;
            this.isError = false;

            try {
                const response = await fetch('/api/v1/register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.form)
                });
                
                const data = await response.json();

                if (!response.ok) {
                    this.isError = true;
                    if (response.status === 422) {
                        this.errors = data.errors || {};
                        this.message = data.message || "Data yang dimasukkan belum sesuai. Silakan periksa kembali.";
                    } else {
                        this.message = data.message || "Terjadi kesalahan pada sistem. Silakan coba kembali.";
                    }

                    // Tentukan field mana yang perlu difokuskan
                    let targetField = 'nik';
                    if (this.errors.email) targetField = 'email';
                    else if (this.errors.password) targetField = 'password';
                    else if (this.errors.name) targetField = 'name';

                    this.triggerErrorAnimation(targetField);
                } else {
                    this.isError = false;
                    this.message = "Pendaftaran Berhasil! Akun warga Anda telah aktif.";
                    
                    // Simpan token ke local storage
                    localStorage.setItem('auth_token', data.access_token);
                    
                    setTimeout(() => lucide.createIcons(), 10);
                    
                    // Redirect setelah 1.5 detik
                    setTimeout(() => {
                        window.location.href = '/dashboard';
                    }, 1500);
                }
            } catch (error) {
                this.isError = true;
                this.message = "Gagal terhubung ke server. Periksa jaringan internet Anda.";
                this.triggerErrorAnimation('nik');
            } finally {
                this.isLoading = false;
                setTimeout(() => lucide.createIcons(), 20);
            }
        },
        
        init() {
            this.$watch('form.nik', () => {
                setTimeout(() => lucide.createIcons(), 10);
            });
        }
    }))
})
</script>
@endpush
@endsection
