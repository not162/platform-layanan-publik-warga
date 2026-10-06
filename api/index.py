import os
import hashlib
import json
from datetime import datetime, date
from typing import Optional, List, Dict, Any

from fastapi import FastAPI, HTTPException, Request, Response, status, Depends
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
import psycopg2
from psycopg2.extras import RealDictCursor

app = FastAPI(
    title="Platform Layanan Publik Warga RT 01 (Staging FastAPI)",
    description="Backend FastAPI Environment Staging untuk Vercel Serverless & PostgreSQL Neon",
    version="1.0.0",
    docs_url="/api/docs",
    redoc_url="/api/redoc",
    openapi_url="/api/openapi.json"
)

# CORS Middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Database Connection
DATABASE_URL = os.getenv(
    "DATABASE_URL",
    "postgresql://neondb_owner:npg_cvKAOlS4jh7p@ep-mute-queen-b3soy5r6-pooler.c-4.ap-southeast-1.aws.neon.tech/neondb?sslmode=require"
)

def get_db():
    conn = psycopg2.connect(DATABASE_URL, cursor_factory=RealDictCursor)
    try:
        yield conn
    finally:
        conn.close()


# -------------------------------------------------------------
# Pydantic Schemas
# -------------------------------------------------------------
class CheckCitizenRequest(BaseModel):
    nik: Optional[str] = ""
    name: Optional[str] = ""

class RegisterRequest(BaseModel):
    nik: str
    name: str
    email: str
    password: str
    password_confirmation: str
    notes: Optional[str] = None

class LoginRequest(BaseModel):
    email: str
    password: str

class LetterCreateRequest(BaseModel):
    letter_type_code: str
    purpose: str
    description: Optional[str] = None

class ComplaintCreateRequest(BaseModel):
    title: str
    description: str
    category: Optional[str] = "infrastruktur"


# -------------------------------------------------------------
# Endpoints
# -------------------------------------------------------------
@app.get("/")
@app.get("/api")
@app.get("/api/v1/health")
def health_check():
    return {
        "status": "online",
        "service": "Platform Layanan Publik Warga RT 01",
        "environment": "staging",
        "framework": "FastAPI (Python 3)",
        "serverless_provider": "Vercel",
        "database": "PostgreSQL 18 (Neon Serverless)",
        "timestamp": datetime.utcnow().isoformat()
    }

@app.post("/api/v1/auth/check-citizen")
def check_citizen(req: CheckCitizenRequest):
    nik = (req.nik or "").strip()
    name = (req.name or "").strip()

    data = {
        "exists": False,
        "has_account": False,
        "status": "EMPTY",
        "message": "Silakan masukkan 16 digit NIK.",
        "allow_new_application": False,
        "registered_name": None,
        "registered_status": None,
        "similar_citizens": []
    }

    conn = psycopg2.connect(DATABASE_URL, cursor_factory=RealDictCursor)
    cursor = conn.cursor()

    try:
        # 1. Cek NIK jika formatnya 16 digit
        if len(nik) == 16 and nik.isdigit():
            nik_hash = hashlib.sha256(nik.encode('utf-8')).hexdigest()
            cursor.execute("SELECT * FROM citizens WHERE nik_hash = %s LIMIT 1", (nik_hash,))
            citizen = cursor.fetchone()

            if citizen:
                data["exists"] = True
                data["registered_name"] = citizen["full_name"]
                data["registered_status"] = citizen["status_warga"]

                if citizen["user_id"] is not None:
                    data["has_account"] = True
                    data["status"] = "ALREADY_REGISTERED"
                    data["allow_new_application"] = False
                    data["message"] = f"Data NIK ini SUDAH TERDAFTAR dan memiliki akun aktif di sistem RT atas nama {citizen['full_name']}. Silakan langsung masuk (login)."
                elif citizen["status_warga"] == "pending_verification":
                    data["has_account"] = False
                    data["status"] = "PENDING_VERIFICATION"
                    data["allow_new_application"] = False
                    data["message"] = "Pengajuan warga baru untuk NIK ini sudah diajukan sebelumnya dan saat ini sedang dalam proses verifikasi pengurus RT."
                else:
                    data["has_account"] = False
                    data["status"] = "PRE_REGISTERED_RT"
                    data["allow_new_application"] = False
                    data["message"] = f"NIK terdata resmi dalam master kependudukan RT 01 atas nama {citizen['full_name']}. Anda dapat langsung melakukan Aktivasi Akun."
            else:
                data["exists"] = False
                data["has_account"] = False
                data["status"] = "NOT_FOUND"
                data["allow_new_application"] = True
                data["message"] = "Nomor NIK belum tercatat dalam data kependudukan RT 01. Anda dapat mengajukan pendaftaran sebagai warga baru (menunggu verifikasi pengurus RT)."

        # 2. Cek nama mirip
        if len(name) >= 3:
            search_pattern = f"%{name}%"
            cursor.execute("SELECT * FROM citizens WHERE full_name ILIKE %s LIMIT 3", (search_pattern,))
            similar_rows = cursor.fetchall()
            for sim in similar_rows:
                raw_nik = sim.get("nik") or ""
                masked_nik = raw_nik[:4] + "********" + raw_nik[-4:] if len(raw_nik) >= 8 else "317103********02"
                data["similar_citizens"].append({
                    "name": sim["full_name"],
                    "masked_nik": masked_nik,
                    "has_account": sim["user_id"] is not None,
                    "status_warga": sim["status_warga"]
                })

        return data
    finally:
        cursor.close()
        conn.close()

@app.post("/api/v1/register", status_code=status.HTTP_201_CREATED)
def register_citizen(req: RegisterRequest):
    if req.password != req.password_confirmation:
        raise HTTPException(status_code=422, detail={"password": ["Konfirmasi kata sandi tidak cocok."]})

    if len(req.nik) != 16 or not req.nik.isdigit():
        raise HTTPException(status_code=422, detail={"nik": ["NIK harus berjumlah tepat 16 digit angka."]})

    nik_hash = hashlib.sha256(req.nik.encode('utf-8')).hexdigest()
    password_hash = hashlib.sha256(req.password.encode('utf-8')).hexdigest()

    conn = psycopg2.connect(DATABASE_URL, cursor_factory=RealDictCursor)
    cursor = conn.cursor()

    try:
        # Cek apakah email sudah terdaftar
        cursor.execute("SELECT id FROM users WHERE email = %s LIMIT 1", (req.email,))
        if cursor.fetchone():
            raise HTTPException(status_code=422, detail={"email": ["Email ini sudah terdaftar."]})

        # Cek citizen
        cursor.execute("SELECT * FROM citizens WHERE nik_hash = %s LIMIT 1", (nik_hash,))
        citizen = cursor.fetchone()

        is_new_applicant = False

        if not citizen:
            is_new_applicant = True
            day_raw = int(req.nik[6:8])
            gender = "Perempuan" if day_raw > 40 else "Laki-laki"
            birth_day = (day_raw - 40) if day_raw > 40 else day_raw
            birth_month = int(req.nik[8:10])
            birth_year_2 = int(req.nik[10:12])
            curr_year_2 = datetime.utcnow().year % 100
            full_year = (1900 + birth_year_2) if birth_year_2 > curr_year_2 else (2000 + birth_year_2)

            dob = None
            try:
                dob = date(full_year, birth_month, birth_day)
            except Exception:
                dob = None

            cursor.execute("""
                INSERT INTO citizens (nik, nik_hash, full_name, email, gender, place_of_birth, date_of_birth, status_warga, verification_notes, is_active)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, true)
                RETURNING id, full_name, status_warga
            """, (req.nik, nik_hash, req.name, req.email, gender, "Jakarta", dob, "pending_verification", req.notes or "Pengajuan mandiri warga baru (menunggu verifikasi RT)."))
            citizen = cursor.fetchone()
        else:
            if citizen["user_id"] is not None:
                raise HTTPException(status_code=422, detail={"nik": ["NIK ini sudah terhubung dengan akun lain. Silakan login."]})

        # Insert user
        cursor.execute("""
            INSERT INTO users (name, email, password, role, warga_id, is_active)
            VALUES (%s, %s, %s, 'warga', %s, true)
            RETURNING id, name, email, role, is_active
        """, (req.name, req.email, password_hash, citizen["id"]))
        user = cursor.fetchone()

        # Update citizen link
        cursor.execute("UPDATE citizens SET user_id = %s, email = %s WHERE id = %s", (user["id"], req.email, citizen["id"]))
        conn.commit()

        success_msg = "Pengajuan pendaftaran warga baru berhasil dikirim dan sedang menunggu verifikasi berkas oleh pengurus RT." if is_new_applicant else "Registrasi dan Verifikasi Warga Berhasil. Akun Anda telah aktif."

        fake_jwt_token = f"jwt_staging_{user['id']}_{nik_hash[:16]}"

        return {
            "message": success_msg,
            "is_pending_verification": is_new_applicant,
            "access_token": fake_jwt_token,
            "token_type": "Bearer",
            "user": {
                "id": user["id"],
                "name": user["name"],
                "email": user["email"],
                "role": user["role"],
                "citizen": {
                    "id": citizen["id"],
                    "full_name": citizen["full_name"],
                    "status_warga": citizen.get("status_warga", "tetap")
                }
            }
        }
    finally:
        cursor.close()
        conn.close()

@app.post("/api/v1/login")
def login(req: LoginRequest):
    conn = psycopg2.connect(DATABASE_URL, cursor_factory=RealDictCursor)
    cursor = conn.cursor()
    try:
        pwd_hash = hashlib.sha256(req.password.encode('utf-8')).hexdigest()
        cursor.execute("SELECT * FROM users WHERE email = %s LIMIT 1", (req.email,))
        user = cursor.fetchone()

        if not user or user["password"] != pwd_hash:
            raise HTTPException(status_code=401, detail={"email": ["Email atau kata sandi tidak valid."]})

        fake_jwt_token = f"jwt_staging_{user['id']}_auth"

        return {
            "message": "Login berhasil.",
            "access_token": fake_jwt_token,
            "token_type": "Bearer",
            "user": {
                "id": user["id"],
                "name": user["name"],
                "email": user["email"],
                "role": user["role"]
            }
        }
    finally:
        cursor.close()
        conn.close()

@app.get("/api/v1/letter-types")
def get_letter_types():
    return {
        "data": [
            {
                "kode_surat": "SK-UMUM",
                "nama_surat": "Surat Keterangan Umum",
                "template_key": "surat-keterangan",
                "estimated_process_hours": 24,
                "is_active": True
            },
            {
                "kode_surat": "SK-USAHA",
                "nama_surat": "Surat Keterangan Domisili Usaha",
                "template_key": "surat-domisili-usaha",
                "estimated_process_hours": 48,
                "is_active": True
            },
            {
                "kode_surat": "SK-KEMATIAN",
                "nama_surat": "Surat Keterangan Kematian",
                "template_key": "surat-kematian",
                "estimated_process_hours": 12,
                "is_active": True
            },
            {
                "kode_surat": "SK-TIDAK-MAMPU",
                "nama_surat": "Surat Keterangan Tidak Mampu (SKTM)",
                "template_key": "surat-sktm",
                "estimated_process_hours": 24,
                "is_active": True
            }
        ]
    }

@app.get("/api/v1/letters/{letter_id}/download")
@app.post("/api/v1/letters/{letter_id}/download")
def download_letter(letter_id: int, request: Request, format: Optional[str] = "pdf"):
    # Mendukung format GET dan POST
    fmt = (format or "pdf").lower()
    if fmt == "docx" or fmt == "word":
        content_type = "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
        filename = f"Surat_RT01_STAGING_{letter_id}.docx"
        content = b"PK\x03\x04[FASTAPI_VERCEL_STAGING_WORD_DOCX]"
    else:
        content_type = "application/pdf"
        filename = f"Surat_RT01_STAGING_{letter_id}.pdf"
        content = b"%PDF-1.4\n%FASTAPI_VERCEL_STAGING_PDF\n%%EOF"

    return Response(
        content=content,
        media_type=content_type,
        headers={
            "Content-Disposition": f'attachment; filename="{filename}"',
            "X-Staging-Runtime": "FastAPI-Vercel"
        }
    )

@app.get("/api/v1/announcements")
def get_announcements():
    conn = psycopg2.connect(DATABASE_URL, cursor_factory=RealDictCursor)
    cursor = conn.cursor()
    try:
        cursor.execute("SELECT * FROM announcements WHERE is_active = true ORDER BY id DESC LIMIT 10")
        rows = cursor.fetchall()
        return {"data": rows}
    finally:
        cursor.close()
        conn.close()

@app.get("/api/v1/finance/summary")
def get_finance_summary():
    conn = psycopg2.connect(DATABASE_URL, cursor_factory=RealDictCursor)
    cursor = conn.cursor()
    try:
        cursor.execute("""
            SELECT 
                COALESCE(SUM(CASE WHEN transaction_type = 'income' THEN amount ELSE 0 END), 0) AS total_income,
                COALESCE(SUM(CASE WHEN transaction_type = 'expense' THEN amount ELSE 0 END), 0) AS total_expense
            FROM finance_transactions
            WHERE status = 'published'
        """)
        row = cursor.fetchone()
        income = float(row["total_income"] or 0)
        expense = float(row["total_expense"] or 0)
        balance = income - expense
        return {
            "total_pemasukan": income,
            "total_pengeluaran": expense,
            "saldo_kas_rt": balance,
            "formatted_saldo": f"Rp {balance:,.0f}"
        }
    finally:
        cursor.close()
        conn.close()

@app.post("/api/v1/admin/citizens/{citizen_id}/verify")
def verify_citizen(citizen_id: int):
    conn = psycopg2.connect(DATABASE_URL, cursor_factory=RealDictCursor)
    cursor = conn.cursor()
    try:
        cursor.execute("UPDATE citizens SET status_warga = 'tetap', verification_notes = 'Disetujui di staging RT' WHERE id = %s RETURNING *", (citizen_id,))
        updated = cursor.fetchone()
        conn.commit()
        if not updated:
            raise HTTPException(status_code=404, detail="Data warga tidak ditemukan.")
        return {"message": f"Warga {updated['full_name']} berhasil disahkan.", "data": updated}
    finally:
        cursor.close()
        conn.close()
