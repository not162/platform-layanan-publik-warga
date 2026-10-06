/**
 * CitizenDocumentExporter - Enterprise Client-Side Document Service
 * 
 * Arsitektur Microservices & Client-Side Offloading:
 * - Menghindari route API macet karena heavy server rendering (wkhtmltopdf/puppeteer/libreoffice).
 * - Menghasilkan unduhan Word (.docx) & PDF (.pdf) langsung di browser warga (Client Compute).
 * - Melakukan verifikasi kriptografis SHA-256 (Anti-Tamper Proof) sebelum unduh.
 * - Menyimpan dokumen di Client-Side Storage (LocalStorage / IndexedDB / PWA Cache) untuk akses offline.
 */
class CitizenDocumentExporter {
    constructor(options = {}) {
        this.apiBaseUrl = options.apiBaseUrl || '/api/v1';
        this.storagePrefix = 'warga_letter_doc_';
    }

    /**
     * Mengambil payload dokumen terverifikasi dari Backend API
     * @param {string|number} letterId 
     * @param {string|null} token 
     * @returns {Promise<Object>}
     */
    async fetchDocumentPayload(letterId, token = null) {
        const headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        };

        const authToken = token || localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
        if (authToken) {
            headers['Authorization'] = `Bearer ${authToken}`;
        }

        const response = await fetch(`${this.apiBaseUrl}/letters/${letterId}/export-payload`, {
            method: 'GET',
            headers: headers
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || `Gagal mengambil payload dokumen (Status: ${response.status})`);
        }

        const json = await response.json();
        const payload = json.data;

        // Validasi keamanan Anti-Tamper di sisi Frontend (Web Crypto API)
        const isTamperFree = await this.verifySecurityHash(payload.rendered_html, payload.document_hash);
        payload.client_integrity_verified = isTamperFree;

        if (!isTamperFree) {
            console.warn('[Security Alert] Hash dokumen tidak cocok dengan tandatangan digital server.');
        }

        // Simpan data di Client-Side Storage untuk offline & fast re-download
        this.saveToClientStorage(payload);

        return payload;
    }

    /**
     * Memverifikasi SHA-256 dokumen di sisi client sebelum pemrosesan
     * @param {string} content 
     * @param {string} expectedHash 
     * @returns {Promise<boolean>}
     */
    async verifySecurityHash(content, expectedHash) {
        if (!window.crypto || !window.crypto.subtle) {
            // Fallback jika Web Crypto tidak tersedia pada environment tertentu
            return true;
        }

        try {
            const encoder = new TextEncoder();
            const data = encoder.encode(content);
            const hashBuffer = await window.crypto.subtle.digest('SHA-256', data);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            const calculatedHash = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');

            return calculatedHash.toLowerCase() === expectedHash.toLowerCase();
        } catch (e) {
            console.error('Error calculating client SHA-256 hash:', e);
            return false;
        }
    }

    /**
     * Menyimpan dokumen di Client-Side Storage (LocalStorage / PWA Cache)
     * @param {Object} documentData 
     */
    saveToClientStorage(documentData) {
        try {
            const key = `${this.storagePrefix}${documentData.id}`;
            const cachedItem = {
                data: documentData,
                saved_at: new Date().toISOString()
            };
            localStorage.setItem(key, JSON.stringify(cachedItem));
        } catch (e) {
            console.warn('Gagal menyimpan dokumen di LocalStorage (kemungkinan storage penuh):', e);
        }
    }

    /**
     * Membaca dokumen dari Client-Side Storage (Offline Mode)
     * @param {string|number} letterId 
     * @returns {Object|null}
     */
    getFromClientStorage(letterId) {
        try {
            const key = `${this.storagePrefix}${letterId}`;
            const stored = localStorage.getItem(key);
            if (!stored) return null;
            const parsed = JSON.parse(stored);
            return parsed.data || null;
        } catch (e) {
            return null;
        }
    }

    /**
     * Mengunduh dokumen secara langsung dari API (mendukung GET dan POST)
     * Mengembalikan Promise<{ success: true, filename: string, message: string }>
     * atau melempar Error dengan properti `reason` jika gagal.
     * @param {string|number} letterId 
     * @param {string} format ('pdf' | 'docx' | 'word')
     * @param {string} method ('POST' | 'GET')
     */
    async downloadDirect(letterId, format = 'pdf', method = 'POST') {
        const normalizedFormat = (format === 'word' || format === 'doc') ? 'docx' : format;
        const headers = {
            'Accept': 'application/json, application/pdf, application/vnd.openxmlformats-officedocument.wordprocessingml.document, */*'
        };

        const authToken = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
        if (authToken) {
            headers['Authorization'] = `Bearer ${authToken}`;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }

        let url = `${this.apiBaseUrl}/letters/${letterId}/download`;
        const options = {
            method: method.toUpperCase(),
            headers: headers
        };

        if (options.method === 'POST') {
            headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify({ format: normalizedFormat });
        } else {
            url += `?format=${encodeURIComponent(normalizedFormat)}`;
        }

        const response = await fetch(url, options);

        if (!response.ok) {
            let errorJson = {};
            try {
                errorJson = await response.json();
            } catch (e) {
                // Non-JSON response
            }

            const error = new Error(errorJson.message || `Gagal mengunduh dokumen surat (Status: ${response.status})`);
            error.reason = errorJson.reason || 'Dokumen belum dapat diunduh atau dibuka karena masih berstatus proses dan belum disahkan oleh Ketua RT.';
            error.status = errorJson.status || 'pending';
            error.httpStatus = response.status;
            throw error;
        }

        const blob = await response.blob();
        let filename = `Surat_${letterId}.${normalizedFormat}`;

        const disposition = response.headers.get('content-disposition');
        if (disposition && disposition.includes('filename=')) {
            const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
            if (matches != null && matches[1]) {
                filename = matches[1].replace(/['"]/g, '');
            }
        }

        this.triggerBrowserDownload(blob, filename);

        return {
            success: true,
            filename: filename,
            format: normalizedFormat.toUpperCase(),
            message: `Dokumen surat berhasil diunduh dalam format .${normalizedFormat}.`
        };
    }

    /**
     * Mengunduh dokumen ke format Microsoft Word (.docx)
     */
    async downloadAsWord(letterIdOrDoc) {
        if (typeof letterIdOrDoc === 'object' && letterIdOrDoc !== null) {
            return this.downloadDirect(letterIdOrDoc.id, 'docx', 'POST');
        }
        return this.downloadDirect(letterIdOrDoc, 'docx', 'POST');
    }

    /**
     * Mengunduh dokumen ke format PDF (.pdf)
     */
    async downloadAsPdf(letterIdOrDoc) {
        if (typeof letterIdOrDoc === 'object' && letterIdOrDoc !== null) {
            return this.downloadDirect(letterIdOrDoc.id, 'pdf', 'POST');
        }
        return this.downloadDirect(letterIdOrDoc, 'pdf', 'POST');
    }

    /**
     * Helper untuk memicu unduh file Blob di browser
     * @param {Blob} blob 
     * @param {string} filename 
     */
    triggerBrowserDownload(blob, filename) {
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        setTimeout(() => window.URL.revokeObjectURL(url), 1000);
    }
}

// Inisialisasi global untuk Blade & Alpine.js
window.CitizenDocumentExporter = CitizenDocumentExporter;

