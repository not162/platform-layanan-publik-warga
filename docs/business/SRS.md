# Software Requirements Specification (SRS)

## 1. Introduction
This document defines the Functional Requirements (FR), Non-Functional Requirements (NFR), and Business Rules (BR) for the "Platform Layanan Publik Warga" system.

## 2. Actors
- **Public**: Unregistered users who can view public announcements, events, and transparent financial summaries.
- **Citizen**: Registered residents who can request letters, submit complaints, and view their own data.
- **Secretary**: Processes letter requests and manages citizen data.
- **Treasurer**: Manages financial records and publishes transparency reports.
- **Security**: Manages and views security logs or public safety announcements.
- **RT Head**: Approves letter requests and oversees the RT level operations.
- **Admin**: System administrator with full access to master data and configurations.

## 3. Functional Requirements (FR)
- **FR-01**: The system must allow Admin to manage Citizen and Family Card master data.
- **FR-02**: The system must allow Citizens to request official letters (Surat Pengantar).
- **FR-03**: The system must allow the Secretary to verify letter requests and RT Head to approve them.
- **FR-04**: The system must allow Citizens to submit complaints (Pengaduan), optionally anonymously.
- **FR-05**: The system must allow the Treasurer to record income and expenses.
- **FR-06**: The system must generate immutable, published financial summaries for Public viewing.
- **FR-07**: The system must log critical actions (Audit Log) for traceability.

## 4. Non-Functional Requirements (NFR)
- **NFR-01 (Security)**: Sensitive data (NIK, No. KK) must be encrypted at rest and never exposed in public APIs.
- **NFR-02 (Performance)**: Public endpoints must be indexed and paginated. Dashboard must avoid N+1 queries.
- **NFR-03 (Concurrency)**: The system must implement Optimistic Locking using `version` columns to prevent lost updates.
- **NFR-04 (Usability)**: The UI must be mobile-first and simple enough for an RT/RW-level operator.

## 5. Business Rules (BR)
- **BR-01**: A published financial record cannot be silently edited or deleted. Changes require a reversal transaction.
- **BR-02**: Letter Request Status Transition: Draft -> Submitted -> Verified (Secretary) -> Approved (RT Head) -> Completed.
- **BR-03**: Complaint Status Transition: Submitted -> In Progress -> Resolved / Rejected.

## 6. Acceptance Criteria for Critical Modules
- **Citizen Master**: Migrations run properly; NIK/No. KK are encrypted; Hash lookup works; Validation rejects invalid formats.
- **Letter Module**: Illegal state transitions yield HTTP 409; Citizens can only see their own letters (HTTP 403 otherwise).
- **Audit Logging**: All critical writes (Create/Update/Delete) generate an audit log entry.
