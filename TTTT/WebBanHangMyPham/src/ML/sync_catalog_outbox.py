"""Deliver durable catalog.changed events from MySQL to Chroma."""
from __future__ import annotations

import json
import os
import socket
import sys
import time
import uuid
from pathlib import Path

import chromadb
import mysql.connector
from chromadb.config import Settings
from live_catalog import mysql_connection_options, retry_delay
from embedding import MODEL_NAME, create_embedder
from rag_config import CATALOG_INDEX_VERSION, PRODUCT_COLLECTION
from env_loader import load_project_env

MODULE_DIR = Path(__file__).resolve().parent
PROJECT_ENV = load_project_env(__file__)

INDEX_VERSION = CATALOG_INDEX_VERSION
CHROMA_DIR = os.getenv("CHROMA_DIR", str(MODULE_DIR.parent / "RAG" / "chroma_cosmetics"))
EMB_MODEL = os.getenv("EMB_MODEL", MODEL_NAME)
WORKER_ID = f"{socket.gethostname()}:{os.getpid()}:{uuid.uuid4().hex[:8]}"


def log(level: str, event: str, **fields) -> None:
    print(json.dumps({"level": level, "event": event, **fields}, ensure_ascii=False), flush=True)


def claim(connection):
    connection.start_transaction()
    cursor = connection.cursor(dictionary=True)
    cursor.execute(
        "SELECT id,payload,attempts,max_attempts FROM outbox_events "
        "WHERE event_type='catalog.changed' AND processed_at IS NULL AND dead_at IS NULL "
        "AND available_at<=NOW() AND (locked_at IS NULL OR locked_at<DATE_SUB(NOW(),INTERVAL 5 MINUTE)) "
        "ORDER BY id LIMIT 1 FOR UPDATE SKIP LOCKED"
    )
    event = cursor.fetchone()
    if event:
        cursor.execute("UPDATE outbox_events SET locked_at=NOW(),worker_id=%s WHERE id=%s", (WORKER_ID, event["id"]))
    connection.commit()
    return event


def load_product(connection, product_id: int):
    cursor = connection.cursor(dictionary=True)
    cursor.execute(
        "SELECT sp.IDSanPham,sp.TenSanPham,sp.MoTaChiTiet,sp.GiaTien,sp.SoLuong,"
        "dm.TenDanhMuc AS DanhMuc,th.TenThuongHieu AS ThuongHieu "
        "FROM sanpham sp LEFT JOIN danhmuc dm ON dm.IDDanhMuc=sp.IDDanhMuc "
        "LEFT JOIN thuonghieu th ON th.IDThuongHieu=sp.IDThuongHieu WHERE sp.IDSanPham=%s",
        (product_id,),
    )
    return cursor.fetchone()


def document(row: dict) -> str:
    return "\n".join([
        f"TenSanPham: {row['TenSanPham']}",
        f"DanhMuc: {row.get('DanhMuc') or ''}",
        f"ThuongHieu: {row.get('ThuongHieu') or ''}",
        f"MoTa: {row.get('MoTaChiTiet') or ''}",
    ])[:4000]


def complete(connection, event_id: int) -> None:
    cursor = connection.cursor()
    cursor.execute(
        "UPDATE outbox_events SET processed_at=NOW(),attempts=attempts+1,locked_at=NULL,worker_id=NULL,last_error=NULL "
        "WHERE id=%s AND worker_id=%s",
        (event_id, WORKER_ID),
    )
    connection.commit()


def fail(connection, event: dict, error: Exception) -> None:
    next_attempt = int(event["attempts"]) + 1
    delay = retry_delay(next_attempt)
    message = f"{type(error).__name__}: {str(error)}"[:500]
    cursor = connection.cursor()
    cursor.execute(
        f"UPDATE outbox_events SET attempts=%s,last_error=%s,locked_at=NULL,worker_id=NULL,"
        f"available_at=DATE_ADD(NOW(),INTERVAL {delay} SECOND),"
        "dead_at=IF(%s>=max_attempts,NOW(),NULL) WHERE id=%s AND worker_id=%s",
        (next_attempt, message, next_attempt, event["id"], WORKER_ID),
    )
    connection.commit()
    log("error", "catalog_sync_failed", event_id=int(event["id"]), attempt=next_attempt, dead=next_attempt >= int(event["max_attempts"]))


def main() -> int:
    connection = mysql.connector.connect(**mysql_connection_options())
    processed = 0
    collection = None
    embedder = None
    try:
        while processed < int(os.getenv("RAG_SYNC_BATCH", "100")):
            event = claim(connection)
            if not event:
                break
            try:
                if collection is None:
                    client = chromadb.PersistentClient(path=CHROMA_DIR, settings=Settings(anonymized_telemetry=False))
                    collection = client.get_or_create_collection(PRODUCT_COLLECTION)
                payload = json.loads(event["payload"]) if isinstance(event["payload"], str) else event["payload"]
                product_id = int(payload["product_id"])
                if payload.get("index_version") != INDEX_VERSION:
                    raise ValueError("Unsupported catalog index version")
                row = load_product(connection, product_id)
                if payload.get("action") == "delete" or not row:
                    collection.delete(ids=[str(product_id)])
                elif payload.get("action") == "upsert":
                    if embedder is None:
                        embedder = create_embedder(EMB_MODEL)
                    doc = document(row)
                    embedding = embedder.encode([doc], normalize_embeddings=True)[0]
                    collection.upsert(
                        ids=[str(product_id)], documents=[doc], embeddings=[embedding],
                        metadatas=[{
                            "IDSanPham": product_id, "TenSanPham": row["TenSanPham"],
                            "DanhMuc": row.get("DanhMuc") or "", "ThuongHieu": row.get("ThuongHieu") or "",
                            "index_version": INDEX_VERSION,
                        }],
                    )
                else:
                    raise ValueError("Unsupported catalog action")
                complete(connection, int(event["id"]))
                processed += 1
            except Exception as error:
                connection.rollback()
                fail(connection, event, error)
        log("info", "catalog_sync_complete", processed=processed)
        return 0
    finally:
        connection.close()


if __name__ == "__main__":
    sys.exit(main())
