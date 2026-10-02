# Sprint / Delivery Plan - Waterfall Controlled

> Catatan: proyek tetap menggunakan Waterfall. Istilah "sprint" di sini adalah timebox eksekusi untuk memudahkan pembagian kerja, bukan perubahan metode menjadi Scrum. Setiap sprint hanya dapat masuk ke fase berikutnya setelah gate Waterfall selesai.

## Timeline Summary - 12 Weeks

| Sprint | Weeks | Waterfall phase | Focus | Target snapshot |
|---|---:|---|---|---|
| Sprint 0 | 1 | Analysis | repository, scope, actors, SRS baseline | planning |
| Sprint 1 | 2 | Analysis -> Design Gate | FR/NFR/BR, RBAC, acceptance criteria | `0.0.1-docs` |
| Sprint 2 | 3-4 | Design | UML, ERD, API, UI, architecture | `0.1.0-snapshot` |
| Sprint 3 | 5-6 | Implementation | foundation, auth, citizen data | `0.2.0-snapshot` |
| Sprint 4 | 7-8 | Implementation | letters, complaints, finance, audit | `0.4.0-snapshot` |
| Sprint 5 | 9-10 | Testing/UAT | API, realtime reads, black box, security | `0.9.0-rc` |
| Sprint 6 | 11-12 | Deployment/Maintenance start | staging -> production, rollback, monitoring | `1.0.0` |

---

## Sprint 0 - Repository and Scope Baseline

### Goal

Menyiapkan proyek agar development tidak dimulai langsung di production.

### Tasks

- Audit isi repository.
- Confirm default branch `main`.
- Create `development` from `main`.
- Add `docs/` planning files.
- Define branch and PR policy.
- Define environment matrix: local, staging, production.
- Confirm Laravel 11/PHP/MySQL requirements.
- Prepare `.env.example` without secrets.

### Deliverables

- repository structure proposal
- branch policy
- environment matrix
- project risks and assumptions

### Gate

No feature implementation before scope and branch policy are approved.

---

## Sprint 1 - Requirements and Data Governance

### Goal

Membekukan kebutuhan rilis pertama dan aturan data.

### Tasks

- Finalize actors: public, citizen, secretary, treasurer, security, RT head, admin.
- Map FR/NFR/business rules.
- Define RBAC matrix.
- Define sensitive/public/internal data classification.
- Define letter and complaint status transitions.
- Define acceptance criteria for critical modules.
- Define audit events.

### Key outputs

- `docs/SRS.md`
- `docs/RBAC_MATRIX.md`
- `docs/DATA_CLASSIFICATION.md`

### Gate

Requirements sign-off.

---

## Sprint 2 - Architecture, ERD, API, UI

### Goal

Mengubah kebutuhan menjadi rancangan implementable.

### Tasks

- Build ERD for 18 operational tables.
- Set PK/FK/unique/index rules.
- Define encrypted identity + hash lookup pattern.
- Define API `/api/v1`.
- Define near-real-time GET polling and conditional GET.
- Define optimistic locking using `version`/`updated_at`.
- Create use case, activity, sequence, state, component, deployment diagrams.
- Create mobile-first sitemap and Blade component plan.
- Finalize logo/brand direction.

### Gate

Design consistency review: UML, database, API, and UI must use the same terms/statuses.

---

## Sprint 3 - Foundation, Authentication, Citizen Master

### Goal

Menyelesaikan dependency paling dasar di branch `development` melalui feature PRs.

### Feature branches

- `feature/project-foundation`
- `feature/auth-rbac`
- `feature/citizen-master`

### Tasks

- Laravel 11 bootstrap.
- Core config and timezone Asia/Jakarta.
- User/role enums.
- Family card and citizen migrations.
- NIK/No. KK encryption and hash lookup.
- Authentication and reset flow.
- Policies/middleware.
- Seed fake staging data.
- Unit and feature tests.

### Acceptance criteria

- migrations run on empty staging DB
- roles block unauthorized access
- no NIK/No. KK appears in public responses
- staging uses fake/anonymized data

### Snapshot

`0.2.0-snapshot`

---

## Sprint 4 - Core Citizen Services

### Goal

Menyelesaikan layanan yang memberi nilai utama.

### Feature branches

- `feature/letter-service`
- `feature/complaints`
- `feature/finance-transparency`
- `feature/public-content`
- `feature/audit-log`

### Letter module

- letter types
- request form
- attachments
- status workflow
- secretary verification
- RT head approval
- ticket number
- letter number
- PDF/QR verification
- audit trail

### Complaints

- public/authenticated submission policy
- anonymous public display option
- attachments
- response/status history
- audit events

### Finance

- categories
- income/expense
- immutable published report rule
- reversal transaction strategy
- public published summary only

### Acceptance criteria

- illegal state transition -> HTTP 409
- citizen cannot read another citizen's private record -> 403
- published finance records cannot be silently edited/deleted
- critical actions generate audit logs

### Snapshot

`0.4.0-snapshot`

---

## Sprint 5 - API v1, Near-Real-Time, Testing and UAT

### Goal

Menyatukan endpoint GET, dashboard, polling, security tests, dan UAT pada staging.

### Feature branches

- `feature/api-v1`
- `feature/realtime-read`
- `test/uat-hardening`

### Tasks

- JSON Resources.
- Public/private endpoint separation.
- Pagination.
- GET polling modules with `fetch()`.
- ETag/Last-Modified or cursor-based incremental sync.
- `304 Not Modified` behavior.
- `version` conflict tests.
- rate-limit tests.
- file upload tests.
- cross-role authorization tests.
- black-box matrix.
- UAT with key actors.

### Performance targets

- public list endpoints indexed and paginated
- dashboard avoids N+1 queries
- repeated unchanged polling can return 304
- polling stops/reduces frequency when page is not active

### Gate

No critical defects; UAT approved.

### Snapshot

`0.9.0-rc`

---

## Sprint 6 - Staging Release, Production and Maintenance Start

### Goal

Mempromosikan release candidate dari `development` ke `main` secara terkontrol.

### Pre-release checklist

- full test suite passes
- `composer audit` reviewed
- production `.env` ready outside Git
- production backup taken
- staging restore test passed
- migration rehearsal passed
- health check documented
- rollback procedure documented
- `APP_DEBUG=false`
- HTTPS enabled
- admin access reviewed

### Git flow

```text
feature/* -> PR -> development -> UAT -> release PR -> main -> tag v1.0.0
```

### Production verification

- login works
- public pages load
- create/test request with non-sensitive test account
- tracking/verification endpoint works
- queue/jobs healthy if enabled
- DB and storage writable
- logs contain no sensitive values

### Rollback trigger

Rollback if any of the following occurs:

- migration causes data integrity failure
- critical authorization bypass
- core letter/complaint/finance flow unavailable
- data corruption
- repeated 5xx beyond agreed threshold

### Release

`1.0.0`

---

## Backlog After v1.0.0

- SSE/EventSource or Laravel Reverb if polling load becomes high.
- richer analytics with pseudonymized data.
- optional data warehouse only when historical scale justifies it.
- framework upgrade plan beyond Laravel 11.
- additional external integrations only after security/privacy review.

