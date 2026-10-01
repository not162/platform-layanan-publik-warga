@extends('layouts.auth')

@section('title', 'Masuk')

@section('content')
<div x-data="loginForm()" :class="{ 'animate-shake': isShaking }">
    <h2 class="text-xl font-bold text-slate-800 mb-1">Selamat Datang Kembali</h2>
    <p class="text-sm text-slate-500 mb-6">Masuk ke akun Anda untuk melanjutkan.</p>

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
                    <h4 class="font-semibold text-xs uppercase tracking-wider mb-0.5" x-text="isError ? 'Autentikasi Gagal' : 'Berhasil'"></h4>
                    <p x-text="message" class="text-xs leading-relaxed opacity-90"></p>
                </div>
            </div>
            <button type="button" @click="message = null" class="text-slate-400 hover:text-slate-600 transition-colors shrink-0 p-1">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
        </div>
    </template>

    <form method="POST" action="{{ route('login.post') }}" @submit.prevent="submit" class="space-y-4">
        @csrf
        <!-- Email / No Telepon -->
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Alamat Email / No. Telepon</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <i data-lucide="mail" :class="(errors.email || (isError && !errors.password)) ? 'text-red-500' : 'text-slate-400'" class="h-4 w-4 transition-colors"></i>
                </div>
                <input type="text" 
                       name="email"
                       id="email"
                       x-ref="emailInput"
                       x-model="form.email" 
                       value="{{ old('email') }}"
                       :class="(errors.email || (isError && !errors.password)) ? 'border-red-400 focus:border-red-500 focus:ring-red-200 ring-2 ring-red-50 animate-pulse-error' : 'border-slate-300 focus:border-blue-500 focus:ring-blue-500'"
                       class="block w-full pl-10 pr-10 py-2.5 border rounded-lg focus:ring-2 sm:text-sm text-slate-900 transition-all placeholder:text-slate-400" 
                       placeholder="email@contoh.com atau 081234567890" 
                       required>
                
                <template x-if="errors.email || (isError && !errors.password)">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-red-500">
                        <i data-lucide="alert-circle" class="h-4 w-4"></i>
                    </div>
                </template>
            </div>
            <p class="mt-1 text-xs text-red-600 flex items-center gap-1 font-medium" x-show="errors.email">
                <span x-text="errors.email ? errors.email[0] : ''"></span>
            </p>
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Kata Sandi</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <i data-lucide="lock" :class="(errors.password || isError) ? 'text-red-500' : 'text-slate-400'" class="h-4 w-4 transition-colors"></i>
                </div>
                <input :type="showPassword ? 'text' : 'password'" 
                       name="password"
                       id="password"
                       x-ref="passwordInput"
                       x-model="form.password" 
                       :class="(errors.password || isError) ? 'border-red-400 focus:border-red-500 focus:ring-red-200 ring-2 ring-red-50 animate-pulse-error' : 'border-slate-300 focus:border-blue-500 focus:ring-blue-500'"
                       class="block w-full pl-10 pr-10 py-2.5 border rounded-lg focus:ring-2 sm:text-sm text-slate-900 transition-all placeholder:text-slate-400" 
                       placeholder="••••••••" 
                       required>
                
                <button type="button" 
                        @click="showPassword = !showPassword; setTimeout(() => lucide.createIcons(), 10);" 
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 transition-colors">
                    <i :data-lucide="showPassword ? 'eye-off' : 'eye'" class="h-4 w-4"></i>
                </button>
            </div>
            <p class="mt-1 text-xs text-red-600 font-medium" x-show="errors.password" x-text="errors.password ? errors.password[0] : ''"></p>
        </div>

        <div class="flex items-center justify-between pt-1">
            <div class="flex items-center">
                <input id="remember-me" name="remember" type="checkbox" value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded cursor-pointer">
                <label for="remember-me" class="ml-2 block text-sm text-slate-600 cursor-pointer">
                    Ingat saya
                </label>
            </div>

            <div class="text-sm">
                <a href="#" class="font-medium text-blue-600 hover:text-blue-800 transition-colors">Lupa sandi?</a>
            </div>
        </div>

        <button type="submit" 
                :disabled="isLoading" 
                class="w-full mt-6 bg-blue-600 hover:bg-blue-700 active:scale-[0.99] disabled:bg-blue-300 disabled:cursor-not-allowed text-white font-medium py-2.5 px-4 rounded-lg transition-all shadow-sm flex justify-center items-center gap-2">
            <template x-if="isLoading">
                <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
            </template>
            <span x-text="isLoading ? 'Memeriksa kredensial...' : 'Masuk'"></span>
        </button>
    </form>
    
    <div class="mt-6 text-center text-sm text-slate-600">
        Belum mendaftar? <a href="/register" class="font-semibold text-blue-600 hover:text-blue-800 transition-colors">Daftar sekarang</a>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('loginForm', () => ({
        form: {
            email: '',
            password: '',
        },
        errors: {},
        isLoading: false,
        isShaking: false,
        showPassword: false,
        message: null,
        isError: false,
        
        triggerErrorAnimation(fieldToFocus = 'password') {
            this.isShaking = true;
            setTimeout(() => {
                this.isShaking = false;
            }, 500);

            // Audio haptic feedback halus (Web Audio API)
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

            // Fokuskan ke kolom input yang salah
            setTimeout(() => {
                if (fieldToFocus === 'password' && this.$refs.passwordInput) {
                    this.form.password = '';
                    this.$refs.passwordInput.focus();
                } else if (fieldToFocus === 'email' && this.$refs.emailInput) {
                    this.$refs.emailInput.focus();
                }
                lucide.createIcons();
            }, 50);
        },

        async submit() {
            this.isLoading = true;
            this.errors = {};
            this.message = null;
            this.isError = false;

            // Pastikan nilai tersinkronisasi jika browser melakukan autofill
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            if (emailInput && (!this.form.email || this.form.email.trim() === '')) {
                this.form.email = emailInput.value.trim();
            }
            if (passwordInput && (!this.form.password || this.form.password.trim() === '')) {
                this.form.password = passwordInput.value;
            }

            try {
                const response = await fetch('/api/v1/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value || ''
                    },
                    body: JSON.stringify(this.form)
                });
                
                const data = await response.json();

                if (!response.ok) {
                    this.isError = true;
                    if (response.status === 422) {
                        this.errors = data.errors || {};
                        this.message = data.message || "Email atau kata sandi tidak cocok.";
                    } else if (response.status === 401) {
                        this.message = "Email atau kata sandi salah. Silakan coba lagi.";
                    } else {
                        this.message = data.message || "Terjadi kesalahan pada sistem. Silakan coba lagi.";
                    }

                    // Jalankan efek getar (shake) dan highlight input
                    this.triggerErrorAnimation(this.errors.email ? 'email' : 'password');
                } else {
                    this.isError = false;
                    this.message = "Kredensial sesuai! Mengalihkan ke portal...";
                    
                    // Simpan token autentikasi Sanctum dan user profile
                    if (data.access_token) {
                        localStorage.setItem('auth_token', data.access_token);
                    }
                    if (data.user) {
                        localStorage.setItem('user_profile', JSON.stringify(data.user));
                    }
                    
                    setTimeout(() => lucide.createIcons(), 10);
                    
                    setTimeout(() => {
                        window.location.href = data.redirect_url || '/dashboard';
                    }, 800);
                }
            } catch (error) {
                this.isError = true;
                this.message = "Gagal terhubung ke server. Periksa koneksi internet Anda.";
                this.triggerErrorAnimation('password');
            } finally {
                this.isLoading = false;
                setTimeout(() => lucide.createIcons(), 20);
            }
        }
    }))
})
</script>
@endpush
@endsection
