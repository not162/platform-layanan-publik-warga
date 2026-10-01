@extends('layouts.auth')

@section('title', 'Pendaftaran Warga')

@section('content')
<div x-data="registerForm()">
    <h2 class="text-xl font-bold text-slate-800 mb-1">Pendaftaran Warga</h2>
    <p class="text-sm text-slate-500 mb-6">Verifikasi NIK Anda untuk mengakses layanan portal.</p>

    <!-- Error/Success Alert -->
    <template x-if="message">
        <div :class="isError ? 'bg-red-50 text-red-600 border-red-200' : 'bg-green-50 text-green-600 border-green-200'" class="p-3 mb-6 rounded-lg text-sm border flex items-start gap-2">
            <i :data-lucide="isError ? 'alert-circle' : 'check-circle'" class="w-5 h-5 shrink-0 mt-0.5"></i>
            <span x-text="message" class="block leading-relaxed"></span>
        </div>
    </template>

    <form @submit.prevent="submit" class="space-y-4">
        <!-- NIK -->
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nomor Induk Kependudukan (NIK)</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i data-lucide="credit-card" class="h-4 w-4 text-slate-400"></i>
                </div>
                <input type="text" x-model="form.nik" @input="form.nik = form.nik.replace(/\D/g, '').slice(0, 16)" class="block w-full pl-10 pr-10 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm text-slate-900 transition-colors" placeholder="16 digit NIK Anda" required>
                
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <template x-if="form.nik.length === 16">
                        <i data-lucide="check-circle-2" class="h-4 w-4 text-green-500"></i>
                    </template>
                </div>
            </div>
            <p class="mt-1 text-xs text-red-500" x-show="errors.nik" x-text="errors.nik[0]"></p>
        </div>

        <!-- Nama -->
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i data-lucide="user" class="h-4 w-4 text-slate-400"></i>
                </div>
                <input type="text" x-model="form.name" class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm text-slate-900 transition-colors" placeholder="Sesuai KTP" required>
            </div>
            <p class="mt-1 text-xs text-red-500" x-show="errors.name" x-text="errors.name[0]"></p>
        </div>

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
                <input type="password" x-model="form.password" class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm text-slate-900 transition-colors" placeholder="Minimal 8 karakter" required>
            </div>
            <p class="mt-1 text-xs text-red-500" x-show="errors.password" x-text="errors.password[0]"></p>
        </div>

        <!-- Konfirmasi Password -->
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Ulangi Kata Sandi</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i data-lucide="shield-check" class="h-4 w-4 text-slate-400"></i>
                </div>
                <input type="password" x-model="form.password_confirmation" class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm text-slate-900 transition-colors" placeholder="Ulangi kata sandi" required>
            </div>
        </div>

        <button type="submit" :disabled="isLoading || form.nik.length !== 16" class="w-full mt-6 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 disabled:cursor-not-allowed text-white font-medium py-2.5 px-4 rounded-lg transition-colors flex justify-center items-center gap-2">
            <template x-if="isLoading">
                <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
            </template>
            <span x-text="isLoading ? 'Memverifikasi...' : 'Daftar & Verifikasi'"></span>
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
        message: null,
        isError: false,
        
        async submit() {
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
                    if (response.status === 422) {
                        this.errors = data.errors || {};
                        this.message = data.message || "Mohon periksa kembali form anda.";
                    } else {
                        this.message = data.message || "Terjadi kesalahan sistem.";
                    }
                    this.isError = true;
                } else {
                    this.isError = false;
                    this.message = "Registrasi Berhasil! Anda telah terverifikasi sebagai warga.";
                    
                    // Simpan token ke local storage
                    localStorage.setItem('auth_token', data.access_token);
                    
                    // Re-render icon for success state
                    setTimeout(() => lucide.createIcons(), 10);
                    
                    // Redirect setelah 2 detik
                    setTimeout(() => {
                        window.location.href = '/dashboard'; // Ubah sesuai rute sesungguhnya
                    }, 2000);
                }
            } catch (error) {
                this.isError = true;
                this.message = "Terjadi masalah koneksi.";
            } finally {
                this.isLoading = false;
                setTimeout(() => lucide.createIcons(), 10);
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
