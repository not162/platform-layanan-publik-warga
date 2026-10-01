@extends('layouts.auth')

@section('title', 'Masuk')

@section('content')
<div x-data="loginForm()">
    <h2 class="text-xl font-bold text-slate-800 mb-1">Selamat Datang Kembali</h2>
    <p class="text-sm text-slate-500 mb-6">Masuk ke akun Anda untuk melanjutkan.</p>

    <!-- Error/Success Alert -->
    <template x-if="message">
        <div :class="isError ? 'bg-red-50 text-red-600 border-red-200' : 'bg-green-50 text-green-600 border-green-200'" class="p-3 mb-6 rounded-lg text-sm border flex items-start gap-2">
            <i :data-lucide="isError ? 'alert-circle' : 'check-circle'" class="w-5 h-5 shrink-0 mt-0.5"></i>
            <span x-text="message" class="block leading-relaxed"></span>
        </div>
    </template>

    <form @submit.prevent="submit" class="space-y-4">
        <!-- Email -->
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Alamat Email</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i data-lucide="mail" class="h-4 w-4 text-slate-400"></i>
                </div>
                <input type="email" x-model="form.email" class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm text-slate-900 transition-colors" placeholder="email@contoh.com" required>
            </div>
            <p class="mt-1 text-xs text-red-500" x-show="errors.email" x-text="errors.email[0]"></p>
        </div>

        <!-- Password -->
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Kata Sandi</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i data-lucide="lock" class="h-4 w-4 text-slate-400"></i>
                </div>
                <input type="password" x-model="form.password" class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm text-slate-900 transition-colors" placeholder="••••••••" required>
            </div>
            <p class="mt-1 text-xs text-red-500" x-show="errors.password" x-text="errors.password[0]"></p>
        </div>

        <div class="flex items-center justify-between mt-4">
            <div class="flex items-center">
                <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded">
                <label for="remember-me" class="ml-2 block text-sm text-slate-900">
                    Ingat saya
                </label>
            </div>

            <div class="text-sm">
                <a href="#" class="font-medium text-blue-600 hover:text-blue-800 transition-colors">Lupa sandi?</a>
            </div>
        </div>

        <button type="submit" :disabled="isLoading" class="w-full mt-6 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 disabled:cursor-not-allowed text-white font-medium py-2.5 px-4 rounded-lg transition-colors flex justify-center items-center gap-2">
            <template x-if="isLoading">
                <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
            </template>
            <span x-text="isLoading ? 'Memeriksa...' : 'Masuk'"></span>
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
        message: null,
        isError: false,
        
        async submit() {
            this.isLoading = true;
            this.errors = {};
            this.message = null;
            this.isError = false;

            try {
                const response = await fetch('/api/v1/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.form)
                });
                
                const data = await response.json();

                if (!response.ok) {
                    if (response.status === 422) {
                        this.errors = data.errors || {};
                        this.message = data.message || "Kredensial tidak valid.";
                    } else {
                        this.message = data.message || "Terjadi kesalahan sistem.";
                    }
                    this.isError = true;
                } else {
                    this.isError = false;
                    this.message = "Berhasil masuk, mengalihkan...";
                    
                    // Simpan token
                    localStorage.setItem('auth_token', data.access_token);
                    
                    setTimeout(() => lucide.createIcons(), 10);
                    
                    setTimeout(() => {
                        window.location.href = '/dashboard';
                    }, 1000);
                }
            } catch (error) {
                this.isError = true;
                this.message = "Terjadi masalah koneksi.";
            } finally {
                this.isLoading = false;
                setTimeout(() => lucide.createIcons(), 10);
            }
        }
    }))
})
</script>
@endpush
@endsection
