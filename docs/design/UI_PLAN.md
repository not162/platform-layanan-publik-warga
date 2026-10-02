# UI and Sitemap Plan

## Core Philosophy
The UI must be mobile-first and maintain an extremely simple interface. Target users range from standard citizens to RT/RW heads who might not be highly tech-savvy. The frontend will be powered by standard Blade templates + Vanilla CSS/JS.

## Sitemap

### Public
- `/` - Landing Page (Features, RT/RW Info)
- `/pengumuman` - Public Announcements
- `/keuangan` - Public Finance Transparency Summary
- `/login` - Authentication Login

### Citizen Dashboard (Requires Auth)
- `/dashboard` - Summary of active requests, latest announcements
- `/profil` - Citizen Profile & Family Data (Read Only for Citizen, linked from Admin data)
- `/surat` - Letter Requests History
- `/surat/buat` - Form: Request New Letter
- `/pengaduan` - Complaints History
- `/pengaduan/buat` - Form: Submit Complaint

### Staff & Admin Dashboard (Requires Role)
- `/admin` - Admin Overview
- `/admin/warga` - Manage Citizen Master Data (Admin)
- `/admin/surat` - Verify / Approve Letters (Secretary, RT Head)
- `/admin/pengaduan` - Manage Complaints (Security, RT Head, Admin)
- `/admin/keuangan` - Manage Income & Expenses (Treasurer)

## Architecture
- **Half-MVVM**: Complex Blade pages will be backed by a lightweight Presenter/ViewModel class to encapsulate presentation logic.
- **Components**: UI elements (buttons, modals, cards) will be extracted into reusable Blade Components (`<x-button>`, `<x-card>`).
- **Responsive Layout**: Utilizing CSS Flexbox and Grid. Sidebar navigation collapses to a hamburger menu on mobile.
