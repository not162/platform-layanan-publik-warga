# Entity-Relationship Diagram (ERD)

```mermaid
erDiagram
    USERS ||--o{ CITIZENS : "has profile"
    USERS {
        bigint id PK
        string name
        string email
        string password
        string role
        timestamp created_at
        timestamp updated_at
    }

    FAMILY_CARDS ||--o{ CITIZENS : "contains"
    FAMILY_CARDS {
        bigint id PK
        text no_kk "Encrypted"
        char(64) no_kk_hash "Searchable hash"
        string address
        string rt
        string rw
        string province
        string city
        string district
        string village
        int version "Optimistic Locking"
        timestamp created_at
        timestamp updated_at
    }

    CITIZENS {
        bigint id PK
        bigint family_card_id FK
        bigint user_id FK
        text nik "Encrypted"
        char(64) nik_hash "Searchable hash"
        string full_name
        string place_of_birth
        date date_of_birth
        string gender
        string religion
        string blood_type
        int version "Optimistic Locking"
        timestamp created_at
        timestamp updated_at
    }

    CITIZENS ||--o{ LETTERS : "requests"
    LETTERS {
        bigint id PK
        bigint citizen_id FK
        string type
        string status "draft, submitted, verified, approved, completed"
        string attachment_path
        string ticket_number
        string letter_number
        int version
        timestamp created_at
        timestamp updated_at
    }

    CITIZENS ||--o{ RESIDENT_DUES : "assessed to"
    RESIDENT_DUES {
        bigint id PK
        bigint citizen_id FK
        smallint period_year
        tinyint period_month
        bigint amount_due_idr
        date due_date
        string status "UNPAID, PARTIAL, PAID, OVERDUE"
        text notes
        timestamp created_at
        timestamp updated_at
    }

    RESIDENT_DUES ||--o{ DUE_PAYMENTS : "has payments"
    FINANCE_TRANSACTIONS ||--o{ DUE_PAYMENTS : "backed by cash entry"
    DUE_PAYMENTS {
        bigint id PK
        bigint resident_due_id FK
        bigint finance_transaction_id FK
        bigint amount_paid_idr
        timestamp paid_at
        string payment_method
        string receipt_number
        string proof_path
        bigint received_by FK
        string status "PAID"
        text notes
        timestamp created_at
        timestamp updated_at
    }

    USERS ||--o{ COMPLAINTS : "submits"
    COMPLAINTS {
        bigint id PK
        bigint citizen_id FK "nullable for anonymous"
        bigint user_id FK "nullable for anonymous"
        string ticket_number
        string kategori
        string title
        text description
        string lokasi
        boolean is_anonymous
        string status "submitted, reviewed, processing, resolved, closed, rejected"
        string priority "rendah, sedang, tinggi, darurat"
        bigint assigned_admin_id FK
        text admin_response
        string attachment_path
        int version "Optimistic Locking"
        timestamp created_at
        timestamp updated_at
    }

    FINANCE_TRANSACTIONS {
        bigint id PK
        string transaction_number
        string source
        bigint amount_idr
        bigint created_by FK
        string type "income, expense"
        string category
        decimal amount
        text description
        string status "draft, published, reversed"
        timestamp transaction_date
        bigint reversal_id FK "For tracking reversals"
        int version
        timestamp created_at
        timestamp updated_at
    }

    AUDIT_LOGS {
        bigint id PK
        bigint user_id FK
        string action
        string entity_type
        bigint entity_id
        text old_values
        text new_values
        string ip_address
        timestamp created_at
    }
```
