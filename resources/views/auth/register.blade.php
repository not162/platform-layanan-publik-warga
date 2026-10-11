@extends('layouts.auth')

@section('title', 'Pendaftaran Warga')

@section('content')
<div x-data="registerForm()" :class="{ 'animate-shake': isShaking }">
    <div class="mb-5 text-center">
        <h2 class="text-xl font-bold text-slate-800 tracking-tight">Pendaftaran Mandiri Warga</h2>
        <p class="text-xs text-slate-500 mt-1">Verifikasi NIK dan validasi data kependudukan RT 04 Kemayoran</p>
    </div>

    <!-- Animated Error/Success Notification Alert -->
    <template x-if="message">
        <div x-show="message" 
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="-translate-y-2 opacity-0 scale-95"
             x-transition:enter-end="translate-y-0 opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             :class="isError ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'" 
             class="p-3 mb-5 rounded-xl text-xs border flex items-start justify-between gap-3 shadow-sm animate-slide-down">
            <div class="flex items-start gap-2.5">
                <div :class="isError ? 'bg-rose-100 text-rose-600' : 'bg-emerald-100 text-emerald-600'" class="p-1 rounded-lg shrink-0 mt-0.5">
                    <i :data-lucide="isError ? 'shield-alert' : 'check-circle-2'" class="w-4 h-4"></i>
                </div>
                <div>
                    <h4 class="font-bold uppercase tracking-wider text-[11px]" x-text="isError ? 'Verifikasi Gagal' : 'Pendaftaran Berhasil'"></h4>
                    <p x-text="message" class="text-xs leading-relaxed opacity-90 mt-0.5"></p>
                </div>
            </div>
            <button type="button" @click="message = null" class="text-slate-400 hover:text-slate-600 transition-colors shrink-0 p-1">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
        </div>
    </template>

    <!-- REAL-TIME UI ALERTS: STATUS NIK DI SISTEM DATABASE -->
    <!-- Alert 1: NIK Sudah Terdaftar & Sudah Memiliki Akun (TIDAK MUNCUL TOMBOL AJUKAN WARGA BARU) -->
    <template x-if="citizenCheck.status === 'ALREADY_REGISTERED'">
        <div class="p-3.5 mb-4 rounded-xl border border-rose-200 bg-rose-50 text-rose-800 text-xs shadow-sm flex flex-col gap-2 animate-slide-down">
            <div class="flex items-start gap-2.5">
                <div class="p-1 bg-rose-100 text-rose-700 rounded-lg shrink-0 mt-0.5">
                    <i data-lucide="alert-octagon" class="w-4 h-4"></i>
                </div>
                <div>
                    <strong class="font-bold uppercase tracking-wide block mb-0.5 text-[11px]">Data NIK Sudah Memiliki Akun Aktif</strong>
                    <span x-text="citizenCheck.message" class="leading-relaxed"></span>
                </div>
            </div>
            <div class="mt-1 flex items-center justify-end">
                <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-600 text-white font-medium text-xs hover:bg-rose-700 transition-colors shadow-sm">
                    <i data-lucide="log-in" class="w-3.5 h-3.5"></i>
                    <span>Masuk ke Akun Anda</span>
                </a>
            </div>
        </div>
    </template>

    <!-- Alert 2: NIK Terdata di Master RT (Aktivasi Akun Biasa - TIDAK MUNCUL TOMBOL AJUKAN WARGA BARU) -->
    <template x-if="citizenCheck.status === 'PRE_REGISTERED_RT'">
        <div class="p-3.5 mb-4 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 text-xs shadow-sm flex items-start gap-2.5 animate-slide-down">
            <div class="p-1 bg-emerald-100 text-emerald-700 rounded-lg shrink-0 mt-0.5">
                <i data-lucide="check-check" class="w-4 h-4"></i>
            </div>
            <div>
                <strong class="font-bold uppercase tracking-wide block mb-0.5 text-[11px]">NIK Terverifikasi di Master Sensus RT 04</strong>
                <span x-text="citizenCheck.message" class="leading-relaxed"></span>
            </div>
        </div>
    </template>

    <!-- Alert 3: NIK Sedang Dalam Antrean Verifikasi Pengurus RT -->
    <template x-if="citizenCheck.status === 'PENDING_VERIFICATION'">
        <div class="p-3.5 mb-4 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 text-xs shadow-sm flex items-start gap-2.5 animate-slide-down">
            <div class="p-1 bg-amber-100 text-amber-700 rounded-lg shrink-0 mt-0.5">
                <i data-lucide="clock" class="w-4 h-4"></i>
            </div>
            <div>
                <strong class="font-bold uppercase tracking-wide block mb-0.5 text-[11px]">Pengajuan Sedang Diverifikasi Pengurus</strong>
                <span x-text="citizenCheck.message" class="leading-relaxed"></span>
            </div>
        </div>
    </template>

    <!-- Alert 4: NIK Belum Ada di Master RT (MUNCUL JALUR AJUKAN WARGA BARU) -->
    <template x-if="citizenCheck.status === 'NOT_FOUND'">
        <div class="p-3.5 mb-4 rounded-xl border border-blue-200 bg-blue-50/80 text-blue-900 text-xs shadow-sm flex items-start gap-2.5 animate-slide-down">
            <div class="p-1 bg-blue-100 text-blue-700 rounded-lg shrink-0 mt-0.5">
                <i data-lucide="info" class="w-4 h-4"></i>
            </div>
            <div>
                <strong class="font-bold uppercase tracking-wide block mb-0.5 text-[11px]">Jalur Pengajuan Warga Baru Terbuka</strong>
                <span x-text="citizenCheck.message" class="leading-relaxed"></span>
            </div>
        </div>
    </template>

    <!-- Alert 5: Peringatan Nama Serupa di Sistem Database RT -->
    <template x-if="citizenCheck.similar_citizens && citizenCheck.similar_citizens.length > 0">
        <div class="p-3.5 mb-4 rounded-xl border border-purple-200 bg-purple-50 text-purple-900 text-xs shadow-sm space-y-2 animate-slide-down">
            <div class="flex items-center gap-2">
                <i data-lucide="users" class="w-4 h-4 text-purple-600"></i>
                <span class="font-bold text-[11px] uppercase tracking-wider">Perhatian: Ditemukan Nama Warga Serupa di RT 04</span>
            </div>
            <p class="text-purple-700 leading-relaxed text-[11px]">
                Pastikan Anda tidak mendaftar ganda jika sudah pernah tercatat pada data berikut:
            </p>
            <div class="space-y-1 mt-1">
                <template x-for="sim in citizenCheck.similar_citizens" :key="sim.name">
                    <div class="flex items-center justify-between bg-white/80 p-2 rounded-lg border border-purple-100">
                        <div>
                            <span class="font-semibold text-slate-800" x-text="sim.name"></span>
                            <span class="text-slate-500 text-[10px] block" x-text="'NIK: ' + sim.masked_nik"></span>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] font-medium rounded-full" 
                              :class="sim.has_account ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'"
                              x-text="sim.has_account ? 'Sudah Ada Akun' : 'Warga Sensus'"></span>
                    </div>
                </template>
            </div>
        </div>
    </template>

    <form method="POST" action="/api/v1/register" @submit.prevent="submit" class="space-y-4">
        @csrf

        <!-- NIK Input -->
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="nik" class="block text-xs font-semibold text-slate-700">Nomor Induk Kependudukan (NIK)</label>
                <span class="text-[11px] text-slate-400 font-mono" x-text="form.nik.length + '/16 digit'"></span>
            </div>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <i data-lucide="credit-card" :class="errors.nik ? 'text-rose-500' : 'text-slate-400'" class="h-4 w-4 transition-colors"></i>
                </div>
                <input type="text" 
                       id="nik"
                       name="nik"
                       x-ref="nikInput"
                       x-model="form.nik" 
                       @input="handleNikInput"
                       @blur="checkCitizenData"
                       maxlength="16"
                       :class="errors.nik ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200 ring-2 ring-rose-50 animate-pulse-error' : 'border-slate-300 focus:border-blue-500 focus:ring-blue-500'"
                       class="block w-full pl-10 pr-10 py-2.5 border rounded-xl focus:ring-2 sm:text-xs text-slate-900 transition-all font-mono placeholder:text-slate-400 tracking-wider" 
                       placeholder="16 digit angka NIK KTP" 
                       required>
                
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <template x-if="citizenCheck.isChecking">
                        <i data-lucide="loader-2" class="h-4 w-4 text-blue-600 animate-spin"></i>
                    </template>
                    <template x-if="!citizenCheck.isChecking && errors.nik">
                        <i data-lucide="alert-circle" class="h-4 w-4 text-rose-500"></i>
                    </template>
                    <template x-if="!citizenCheck.isChecking && !errors.nik && form.nik.length === 16">
                        <i data-lucide="check-circle-2" class="h-4 w-4 text-emerald-500"></i>
                    </template>
                </div>
            </div>
            <p class="mt-1 text-xs text-rose-600 font-medium" x-show="errors.nik" x-text="errors.nik ? errors.nik[0] : ''"></p>
        </div>

        <!-- Nama Lengkap -->
        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Lengkap</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <i data-lucide="user" :class="errors.name ? 'text-rose-500' : 'text-slate-400'" class="h-4 w-4 transition-colors"></i>
                </div>
                <input type="text" 
                       x-ref="nameInput"
                       x-model="form.name" 
                       @input="handleNameInput"
                       @blur="checkCitizenData"
                       :readonly="citizenCheck.status === 'PRE_REGISTERED_RT'"
                       :class="errors.name ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200 ring-2 ring-rose-50 animate-pulse-error' : (citizenCheck.status === 'PRE_REGISTERED_RT' ? 'bg-slate-100 border-slate-200 text-slate-600 cursor-not-allowed' : 'border-slate-300 focus:border-blue-500 focus:ring-blue-500')"
                       class="block w-full pl-10 pr-3 py-2.5 border rounded-xl focus:ring-2 sm:text-xs text-slate-900 transition-all placeholder:text-slate-400" 
                       placeholder="Nama lengkap sesuai KTP" 
                       required>
            </div>
            <template x-if="citizenCheck.status === 'PRE_REGISTERED_RT'">
                <p class="mt-1 text-[11px] text-emerald-600 font-medium">Nama terkunci sesuai data resmi sensus RT 04.</p>
            </template>
            <p class="mt-1 text-xs text-rose-600 font-medium" x-show="errors.name" x-text="errors.name ? errors.name[0] : ''"></p>
        </div>

        <!-- Email -->
        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Alamat Email Aktif</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <i data-lucide="mail" :class="errors.email ? 'text-rose-500' : 'text-slate-400'" class="h-4 w-4 transition-colors"></i>
                </div>
                <input type="email" 
                       x-ref="emailInput"
                       x-model="form.email" 
                       :class="errors.email ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200 ring-2 ring-rose-50 animate-pulse-error' : 'border-slate-300 focus:border-blue-500 focus:ring-blue-500'"
                       class="block w-full pl-10 pr-10 py-2.5 border rounded-xl focus:ring-2 sm:text-xs text-slate-900 transition-all placeholder:text-slate-400" 
                       placeholder="nama@email.com" 
                       required>
                
                <template x-if="errors.email">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-rose-500">
                        <i data-lucide="alert-circle" class="h-4 w-4"></i>
                    </div>
                </template>
            </div>
            <p class="mt-1 text-xs text-rose-600 font-medium" x-show="errors.email" x-text="errors.email ? errors.email[0] : ''"></p>
        </div>

        <!-- Berkas Pendukung KTP / KK (HANYA PADA JALUR WARGA BARU / NIK NOT_FOUND) -->
        <template x-if="citizenCheck.allow_new_application">
            <div class="p-3.5 rounded-xl border border-blue-200 bg-blue-50/70 space-y-2.5 animate-slide-down">
                <div class="flex items-center justify-between">
                    <label class="block text-[11px] font-bold text-blue-950 uppercase tracking-wider">
                        Unggah Bukti KTP / KK (Disarankan)
                    </label>
                    <span class="text-[10px] text-blue-600">JPG, PNG, PDF (Maks 5MB)</span>
                </div>
                <div>
                    <input type="file" 
                           @change="handleFileUpload" 
                           accept=".jpg,.jpeg,.png,.pdf"
                           class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer border border-slate-200 bg-white rounded-lg p-1">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Domisili / Alamat Rumah</label>
                    <input type="text" 
                           x-model="form.notes"
                           class="block w-full py-1.5 px-3 border border-slate-300 rounded-lg text-xs placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500"
                           placeholder="Contoh: Warga baru Gg. Bugis No. 95A, RT 04">
                </div>
            </div>
        </template>

        <!-- Kata Sandi -->
        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kata Sandi Baru</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <i data-lucide="lock" :class="errors.password ? 'text-rose-500' : 'text-slate-400'" class="h-4 w-4 transition-colors"></i>
                </div>
                <input :type="showPassword ? 'text' : 'password'" 
                       x-ref="passwordInput"
                       x-model="form.password" 
                       :class="errors.password ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200 ring-2 ring-rose-50 animate-pulse-error' : 'border-slate-300 focus:border-blue-500 focus:ring-blue-500'"
                       class="block w-full pl-10 pr-10 py-2.5 border rounded-xl focus:ring-2 sm:text-xs text-slate-900 transition-all placeholder:text-slate-400" 
                       placeholder="Minimal 8 karakter" 
                       required>
                
                <button type="button" 
                        @click="showPassword = !showPassword; setTimeout(() => lucide.createIcons(), 10);" 
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 transition-colors">
                    <i :data-lucide="showPassword ? 'eye-off' : 'eye'" class="h-4 w-4"></i>
                </button>
            </div>
            <p class="mt-1 text-xs text-rose-600 font-medium" x-show="errors.password" x-text="errors.password ? errors.password[0] : ''"></p>
        </div>

        <!-- Konfirmasi Kata Sandi -->
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label class="block text-xs font-semibold text-slate-700">Ulangi Kata Sandi</label>
                <template x-if="form.password_confirmation.length > 0">
                    <span class="text-[11px] font-semibold"
                          :class="form.password === form.password_confirmation ? 'text-emerald-600' : 'text-amber-600'"
                          x-text="form.password === form.password_confirmation ? 'Kata sandi cocok' : 'Belum sesuai'"></span>
                </template>
            </div>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <i data-lucide="shield-check" 
                       :class="form.password_confirmation.length > 0 && form.password === form.password_confirmation ? 'text-emerald-600' : 'text-slate-400'" 
                       class="h-4 w-4 transition-colors"></i>
                </div>
                <input :type="showPasswordConfirmation ? 'text' : 'password'" 
                       x-ref="confirmPasswordInput"
                       x-model="form.password_confirmation" 
                       :class="form.password_confirmation.length > 0 && form.password !== form.password_confirmation ? 'border-amber-400 focus:border-amber-500 focus:ring-amber-100' : 'border-slate-300 focus:border-blue-500 focus:ring-blue-500'"
                       class="block w-full pl-10 pr-10 py-2.5 border rounded-xl focus:ring-2 sm:text-xs text-slate-900 transition-all placeholder:text-slate-400" 
                       placeholder="Ulangi kata sandi" 
                       required>

                <button type="button" 
                        @click="showPasswordConfirmation = !showPasswordConfirmation; setTimeout(() => lucide.createIcons(), 10);" 
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 transition-colors">
                    <i :data-lucide="showPasswordConfirmation ? 'eye-off' : 'eye'" class="h-4 w-4"></i>
                </button>
            </div>
        </div>

        <!-- LOGIKA TOMBOL SUBMIT DINAMIS:
             1. Jika NIK sudah terdaftar & punya akun (ALREADY_REGISTERED): Button "Ajukan Warga Baru" TIDAK MUNCUL, muncul link login.
             2. Jika NIK ada di master sensus RT (PRE_REGISTERED_RT): Button "Aktivasi Akun Warga Terdata".
             3. Jika NIK belum ada (NOT_FOUND): Button "Ajukan Pendaftaran Warga Baru".
        -->
        <div class="pt-2">
            <!-- Kasus: NIK Sudah Terdaftar (Button Ajukan Warga Baru TIDAK MUNCUL) -->
            <template x-if="citizenCheck.status === 'ALREADY_REGISTERED'">
                <a href="{{ route('login') }}" 
                   class="w-full bg-slate-800 hover:bg-slate-900 text-white font-semibold py-2.5 px-4 rounded-xl transition-all shadow-sm flex justify-center items-center gap-2 text-xs">
                    <i data-lucide="log-in" class="w-4 h-4"></i>
                    <span>NIK Sudah Terdaftar — Masuk ke Portal</span>
                </a>
            </template>

            <!-- Kasus: NIK Belum Ada di Master RT (MUNCUL BUTTON "AJUKAN PENDAFTARAN WARGA BARU") -->
            <template x-if="citizenCheck.allow_new_application">
                <button type="submit" 
                        :disabled="isLoading || form.nik.length !== 16 || (form.password && form.password_confirmation && form.password !== form.password_confirmation)" 
                        class="w-full bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-700 hover:to-amber-800 active:scale-[0.99] disabled:from-amber-300 disabled:to-amber-300 disabled:cursor-not-allowed text-white font-semibold py-2.5 px-4 rounded-xl transition-all shadow-md shadow-amber-600/20 flex justify-center items-center gap-2 text-xs">
                    <template x-if="isLoading">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                    </template>
                    <template x-if="!isLoading">
                        <i data-lucide="user-plus" class="w-4 h-4"></i>
                    </template>
                    <span x-text="isLoading ? 'Mengirim pengajuan warga baru...' : 'Ajukan Pendaftaran Warga Baru'"></span>
                </button>
            </template>

            <!-- Kasus: NIK Ada di Master RT (Aktivasi Akun Warga — BUTTON AJUKAN WARGA BARU TIDAK MUNCUL) -->
            <template x-if="!citizenCheck.allow_new_application && citizenCheck.status !== 'ALREADY_REGISTERED'">
                <button type="submit" 
                        :disabled="isLoading || form.nik.length !== 16 || (form.password && form.password_confirmation && form.password !== form.password_confirmation)" 
                        class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 active:scale-[0.99] disabled:from-blue-300 disabled:to-indigo-300 disabled:cursor-not-allowed text-white font-semibold py-2.5 px-4 rounded-xl transition-all shadow-md shadow-blue-500/20 flex justify-center items-center gap-2 text-xs">
                    <template x-if="isLoading">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                    </template>
                    <template x-if="!isLoading">
                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                    </template>
                    <span x-text="isLoading ? 'Memproses aktivasi akun...' : (citizenCheck.status === 'PRE_REGISTERED_RT' ? 'Aktivasi Akun Warga Terdata' : 'Daftar & Verifikasi')"></span>
                </button>
            </template>
        </div>
    </form>
    
    <div class="mt-6 pt-5 border-t border-slate-100 text-center text-xs text-slate-600">
        Sudah memiliki akun warga? 
        <a href="{{ route('login') }}" class="font-bold text-blue-600 hover:text-blue-800 transition-colors inline-flex items-center gap-1">
            <span>Masuk di sini</span>
            <i data-lucide="arrow-right" class="w-3 h-3"></i>
        </a>
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
            password_confirmation: '',
            notes: ''
        },
        ktpFile: null,
        citizenCheck: {
            status: null,
            message: null,
            allow_new_application: false,
            registered_name: null,
            registered_status: null,
            similar_citizens: [],
            isChecking: false
        },
        errors: {},
        isLoading: false,
        isShaking: false,
        showPassword: false,
        showPasswordConfirmation: false,
        message: null,
        isError: false,
        checkTimeout: null,
        
        handleNikInput() {
            this.form.nik = this.form.nik.replace(/\D/g, '').slice(0, 16);
            if (this.form.nik.length === 16) {
                this.checkCitizenData();
            } else {
                this.citizenCheck.status = null;
                this.citizenCheck.allow_new_application = false;
                this.citizenCheck.similar_citizens = [];
            }
        },

        handleNameInput() {
            if (this.checkTimeout) clearTimeout(this.checkTimeout);
            this.checkTimeout = setTimeout(() => {
                if (this.form.name.length >= 3) {
                    this.checkCitizenData();
                }
            }, 400);
        },

        handleFileUpload(event) {
            const file = event.target.files[0];
            if (file) {
                this.ktpFile = file;
            }
        },

        async checkCitizenData() {
            if (this.form.nik.length !== 16 && this.form.name.length < 3) {
                return;
            }

            this.citizenCheck.isChecking = true;
            try {
                const response = await fetch('/api/v1/auth/check-citizen', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        nik: this.form.nik,
                        name: this.form.name
                    })
                });

                if (response.ok) {
                    const data = await response.json();
                    this.citizenCheck.status = data.status;
                    this.citizenCheck.message = data.message;
                    this.citizenCheck.allow_new_application = data.allow_new_application;
                    this.citizenCheck.registered_name = data.registered_name;
                    this.citizenCheck.similar_citizens = data.similar_citizens || [];

                    if (data.status === 'PRE_REGISTERED_RT' && data.registered_name && !this.form.name) {
                        this.form.name = data.registered_name;
                    }
                }
            } catch (e) {
                console.error("Gagal melakukan verifikasi real-time data warga:", e);
            } finally {
                this.citizenCheck.isChecking = false;
                setTimeout(() => {
                    if (window.lucide) lucide.createIcons();
                }, 20);
            }
        },

        triggerErrorAnimation(fieldToFocus = 'nik') {
            this.isShaking = true;
            setTimeout(() => {
                this.isShaking = false;
            }, 500);

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
                if (window.lucide) lucide.createIcons();
            }, 50);
        },

        async submit() {
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
                const formData = new FormData();
                formData.append('nik', this.form.nik);
                formData.append('name', this.form.name);
                formData.append('email', this.form.email);
                formData.append('password', this.form.password);
                formData.append('password_confirmation', this.form.password_confirmation);
                if (this.form.notes) formData.append('notes', this.form.notes);
                if (this.ktpFile) formData.append('ktp_file', this.ktpFile);

                const response = await fetch('/api/v1/register', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json'
                    },
                    body: formData
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

                    let targetField = 'nik';
                    if (this.errors.email) targetField = 'email';
                    else if (this.errors.password) targetField = 'password';
                    else if (this.errors.name) targetField = 'name';

                    this.triggerErrorAnimation(targetField);
                } else {
                    this.isError = false;
                    this.message = data.message || "Pendaftaran Berhasil! Akun warga Anda telah diproses.";
                    
                    if (data.access_token) {
                        localStorage.setItem('auth_token', data.access_token);
                    }
                    
                    setTimeout(() => {
                        if (window.lucide) lucide.createIcons();
                    }, 10);
                    
                    setTimeout(() => {
                        window.location.href = '/dashboard';
                    }, 1500);
                }
            } catch (error) {
                this.isError = true;
                this.message = "Gagal terhubung ke server. Periksa koneksi internet Anda.";
                this.triggerErrorAnimation('nik');
            } finally {
                this.isLoading = false;
                setTimeout(() => {
                    if (window.lucide) lucide.createIcons();
                }, 20);
            }
        },
        
        init() {
            setTimeout(() => {
                if (window.lucide) lucide.createIcons();
            }, 50);
            this.$watch('form.nik', () => {
                setTimeout(() => {
                    if (window.lucide) lucide.createIcons();
                }, 10);
            });
        }
    }))
})
</script>
@endpush
@endsection
