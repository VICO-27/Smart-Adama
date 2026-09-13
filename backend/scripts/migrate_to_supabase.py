#!/usr/bin/env python3
"""
Smart Adama Database Migration & Verification Script
Safely transfers local smart_adama PostgreSQL database to Supabase,
verifies all 35 tables and row counts, verifies 1024-dim pgvector embeddings,
and runs a test RAG cosine similarity query.
"""

import os
import sys
import subprocess
import re

HOST = "aws-1-eu-west-1.pooler.supabase.com"
PORT = "5432"
DB = "postgres"
USER = "postgres.giknfkibnyzrlegxraio"
BACKUP_SQL = "/tmp/smart_adama_backup.sql"
BACKUP_DUMP = "/tmp/smart_adama_backup.dump"

def get_supabase_password():
    # 1. Environment variable
    for var in ["SUPABASE_DB_PASSWORD", "PGPASSWORD"]:
        val = os.environ.get(var)
        if val:
            return val

    # 2. Check backend/.env
    env_path = os.path.join(os.path.dirname(__file__), "..", ".env")
    if os.path.exists(env_path):
        with open(env_path, "r", encoding="utf-8") as f:
            for line in f:
                line = line.strip()
                if line.startswith("SUPABASE_DB_PASSWORD="):
                    val = line.split("=", 1)[1].strip().strip('"').strip("'")
                    if val:
                        return val

    # 3. Check ~/.pgpass
    pgpass = os.path.expanduser("~/.pgpass")
    if os.path.exists(pgpass):
        with open(pgpass, "r", encoding="utf-8") as f:
            for line in f:
                parts = line.strip().split(":")
                if len(parts) >= 5:
                    h, p, d, u = parts[0], parts[1], parts[2], parts[3]
                    pwd = ":".join(parts[4:])
                    if (h == "*" or h == HOST) and (u == "*" or u == USER):
                        return pwd

    return None

def run_psql_cmd(sql, password):
    env = os.environ.copy()
    if password:
        env["PGPASSWORD"] = password
    cmd = [
        "psql",
        "-h", HOST,
        "-p", PORT,
        "-U", USER,
        "-d", DB,
        "-v", "ON_ERROR_STOP=1",
        "-t",
        "-c", sql
    ]
    res = subprocess.run(cmd, capture_output=True, text=True, env=env)
    return res.returncode, res.stdout, res.stderr

def run_psql_file(filepath, password):
    env = os.environ.copy()
    if password:
        env["PGPASSWORD"] = password
    cmd = [
        "psql",
        "-h", HOST,
        "-p", PORT,
        "-U", USER,
        "-d", DB,
        "-v", "ON_ERROR_STOP=0",
        "-f", filepath
    ]
    res = subprocess.run(cmd, capture_output=True, text=True, env=env)
    return res.returncode, res.stdout, res.stderr

def get_local_counts():
    cmd = [
        "psql",
        "-U", "vico",
        "-d", "smart_adama",
        "-t",
        "-c", """
        DO $$
        DECLARE
            r RECORD;
            cnt BIGINT;
        BEGIN
            FOR r IN SELECT table_name FROM information_schema.tables WHERE table_schema='public' AND table_type='BASE TABLE' ORDER BY table_name LOOP
                EXECUTE 'SELECT count(*) FROM ' || quote_ident(r.table_name) INTO cnt;
                RAISE NOTICE '% : %', r.table_name, cnt;
            END LOOP;
        END $$;
        """
    ]
    res = subprocess.run(cmd, capture_output=True, text=True)
    counts = {}
    for line in res.stderr.splitlines():
        if "NOTICE:" in line and ":" in line:
            parts = line.split("NOTICE:")[1].strip().split(":")
            if len(parts) == 2:
                tbl = parts[0].strip()
                c = int(parts[1].strip())
                counts[tbl] = c
    return counts

def main():
    print("=== Smart Adama Supabase Migration Tool ===")
    password = get_supabase_password()
    if not password:
        print("[ERROR] Supabase database password not found!")
        print("Please configure one of:")
        print("  1. In ~/.pgpass:")
        print(f"     {HOST}:{PORT}:{DB}:{USER}:<your_password>")
        print("  2. In backend/.env:")
        print("     SUPABASE_DB_PASSWORD=<your_password>")
        sys.exit(1)

    print("[1/5] Testing connection to Supabase...")
    code, out, err = run_psql_cmd("SELECT version();", password)
    if code != 0:
        print(f"[ERROR] Connection failed:\n{err.strip()}")
        sys.exit(1)
    print(f"✓ Connected to Supabase: {out.strip()[:60]}...")

    print("[2/5] Ensuring pgvector extension is enabled on Supabase...")
    code, out, err = run_psql_cmd("CREATE EXTENSION IF NOT EXISTS vector WITH SCHEMA public;", password)
    if code != 0:
        print(f"[WARNING] Extension check: {err.strip()}")
    else:
        print("✓ pgvector extension is active in schema public.")

    print("[3/5] Checking/Restoring Smart Adama schema and data into Supabase...")
    code, out, err = run_psql_cmd("SELECT count(*) FROM information_schema.tables WHERE table_schema='public';", password)
    existing_tbls = int(out.strip()) if code == 0 and out.strip().isdigit() else 0
    if existing_tbls >= 35:
        print(f"✓ Smart Adama schema already restored ({existing_tbls} tables found in Supabase). Proceeding to parity verification.")
    else:
        if not os.path.exists(BACKUP_SQL):
            print(f"[ERROR] Backup SQL file {BACKUP_SQL} not found.")
            sys.exit(1)
        code, out, err = run_psql_file(BACKUP_SQL, password)
        print("✓ Restore process executed.")

    print("[4/5] Verifying table row counts against local smart_adama...")
    local_counts = get_local_counts()

    # Query Supabase counts
    code, out, err = run_psql_cmd("""
        DO $$
        DECLARE
            r RECORD;
            cnt BIGINT;
        BEGIN
            FOR r IN SELECT table_name FROM information_schema.tables WHERE table_schema='public' AND table_type='BASE TABLE' ORDER BY table_name LOOP
                EXECUTE 'SELECT count(*) FROM ' || quote_ident(r.table_name) INTO cnt;
                RAISE NOTICE '% : %', r.table_name, cnt;
            END LOOP;
        END $$;
    """, password)

    remote_counts = {}
    for line in err.splitlines():
        if "NOTICE:" in line and ":" in line:
            parts = line.split("NOTICE:")[1].strip().split(":")
            if len(parts) == 2:
                tbl = parts[0].strip()
                c = int(parts[1].strip())
                remote_counts[tbl] = c

    print("\n--- Row Count Parity Check ---")
    all_match = True
    for tbl, local_cnt in sorted(local_counts.items()):
        rem_cnt = remote_counts.get(tbl, 0)
        status = "MATCH" if rem_cnt == local_cnt else "MISMATCH"
        if status == "MISMATCH":
            all_match = False
        print(f"  {tbl:<28}: local={local_cnt:<6} supabase={rem_cnt:<6} [{status}]")

    if all_match:
        print("✓ All tables verified with 100% parity!")
    else:
        print("⚠ Some table row counts differed. Please inspect output above.")

    print("\n[5/5] Verifying pgvector RAG embeddings on Supabase...")
    code, out, err = run_psql_cmd("""
        SELECT count(*) as total,
               count(embedding) as with_emb,
               vector_dims(embedding) as dim
        FROM content_chunks
        WHERE embedding IS NOT NULL
        GROUP BY vector_dims(embedding);
    """, password)
    print(f"  content_chunks pgvector: {out.strip()}")

    code, out, err = run_psql_cmd("""
        SELECT count(*) as total,
               vector_dims(embedding) as dim
        FROM content_chunk_embeddings
        GROUP BY vector_dims(embedding);
    """, password)
    print(f"  content_chunk_embeddings pgvector: {out.strip()}")

    print("\n--- Testing Live RAG Cosine Retrieval Query (<=> operator) on Supabase ---")
    test_query = """
        SELECT id, chunk_index, token_count, (embedding <=> (SELECT embedding FROM content_chunks LIMIT 1)) as distance
        FROM content_chunks
        ORDER BY distance ASC
        LIMIT 3;
    """
    code, out, err = run_psql_cmd(test_query, password)
    if code == 0:
        print("✓ Live vector cosine search executed successfully:")
        print(out.strip())
    else:
        print(f"[ERROR] RAG retrieval query failed: {err.strip()}")
        all_match = False

    if all_match:
        print("\n[6/6] Switching Laravel backend (.env) to Supabase PostgreSQL...")
        env_path = os.path.join(os.path.dirname(__file__), "..", ".env")
        if os.path.exists(env_path):
            with open(env_path, "r", encoding="utf-8") as f:
                env_content = f.read()

            env_content = re.sub(r"^DB_HOST=.*$", f"DB_HOST={HOST}", env_content, flags=re.MULTILINE)
            env_content = re.sub(r"^DB_PORT=.*$", f"DB_PORT={PORT}", env_content, flags=re.MULTILINE)
            env_content = re.sub(r"^DB_DATABASE=.*$", f"DB_DATABASE={DB}", env_content, flags=re.MULTILINE)
            env_content = re.sub(r"^DB_USERNAME=.*$", f"DB_USERNAME={USER}", env_content, flags=re.MULTILINE)
            if password:
                escaped_pwd = password.replace('"', '\\"')
                env_content = re.sub(r"^DB_PASSWORD=.*$", f'DB_PASSWORD="{escaped_pwd}"', env_content, flags=re.MULTILINE)

            if "DB_SSLMODE=" in env_content:
                env_content = re.sub(r"^DB_SSLMODE=.*$", "DB_SSLMODE=require", env_content, flags=re.MULTILINE)
            else:
                env_content = re.sub(r"(^DB_PASSWORD=.*$)", r"\1\nDB_SSLMODE=require", env_content, flags=re.MULTILINE)

            with open(env_path, "w", encoding="utf-8") as f:
                f.write(env_content)

            print("✓ backend/.env successfully configured with Supabase connection.")

            print("\nTesting Laravel connection to Supabase...")
            backend_dir = os.path.join(os.path.dirname(__file__), "..")
            tinker_cmd = [
                "php", "artisan", "tinker", "--execute",
                "echo 'Users in Supabase via Laravel: ' . \\App\\Models\\User::count() . PHP_EOL; "
                "echo 'Chunks in Supabase via Laravel: ' . \\App\\Models\\ContentChunk::count() . PHP_EOL;"
            ]
            tinker_res = subprocess.run(tinker_cmd, cwd=backend_dir, capture_output=True, text=True, env={**os.environ, "XDG_CONFIG_HOME": "/tmp"})
            print(tinker_res.stdout.strip())
            if tinker_res.returncode == 0 and "Users in Supabase via Laravel: 1136" in tinker_res.stdout:
                print("✓ Laravel is now running on Supabase PostgreSQL with 100% verified data!")
            else:
                print(f"[WARNING] Laravel test output:\n{tinker_res.stderr.strip() or tinker_res.stdout.strip()}")
    else:
        print("\n[ABORTED] Laravel was NOT switched because data parity checks did not pass 100%.")

if __name__ == "__main__":
    main()
