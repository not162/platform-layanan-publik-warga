# Data Classification and Privacy

## 1. Classification Levels

### 1.1 Public Data
Information that is freely available to any guest or visitor without authentication.
- **Examples**: Public announcements, event schedules, published transparent financial summaries, RT/RW organizational structures.

### 1.2 Internal / Operational Data
Information used for the administration of the platform, visible only to specific authorized roles but not highly sensitive on an individual basis.
- **Examples**: Complaint descriptions (if not anonymous), letter request metadata (types, statuses), non-sensitive citizen demographics (e.g., aggregate data), finance transaction logs.

### 1.3 Sensitive / Confidential Data
Personally Identifiable Information (PII) that must be strictly protected, both in the database (encryption at rest) and in API responses.
- **Examples**: NIK (Nomor Induk Kependudukan), No. KK (Nomor Kartu Keluarga), detailed address when linked to an identity, passwords.

## 2. Handling Sensitive Data
- **Storage**: `nik` and `no_kk` are stored using Laravel's `encrypted` cast.
- **Searchability**: To allow lookup without decrypting every row, deterministic hashes (`nik_hash`, `no_kk_hash`) using SHA-256 are stored alongside the encrypted fields. Lookups must be performed against the hash.
- **API Exposure**: Sensitive fields must NOT be exposed in public endpoints or standard API JSON resources unless explicitly authorized (e.g., to the user themselves or to the Admin).

## 3. Auditing and Traceability
- All writes, updates, and deletes to sensitive or financial data must generate an audit log entry detailing the actor (user ID), action, timestamp, and IP address.
