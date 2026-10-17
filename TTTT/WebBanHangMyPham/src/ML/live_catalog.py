"""Small, dependency-light contracts for live commerce data used by RAG."""
from __future__ import annotations

import html as html_lib
import os
import re
from typing import Callable, Iterable


def mysql_connection_options(env: dict[str, str] | None = None) -> dict:
    values = os.environ if env is None else env
    missing = [name for name in ("MYSQL_HOST", "MYSQL_USER", "MYSQL_DB") if not values.get(name)]
    if missing:
        raise RuntimeError("Missing required database environment: " + ", ".join(missing))
    return {
        "host": values["MYSQL_HOST"],
        "port": int(values.get("MYSQL_PORT", "3306")),
        "user": values["MYSQL_USER"],
        "password": values.get("MYSQL_PASSWORD", ""),
        "database": values["MYSQL_DB"],
        "charset": "utf8mb4",
        "use_unicode": True,
        "connection_timeout": 3,
    }


def product_ids(hits: Iterable[dict]) -> list[int]:
    result: list[int] = []
    for hit in hits:
        try:
            result.append(int((hit.get("meta") or {}).get("IDSanPham", hit.get("id"))))
        except (TypeError, ValueError):
            continue
    return list(dict.fromkeys(result))


def hydrate_products(hits: list[dict], connect: Callable[..., object], env: dict[str, str] | None = None) -> list[dict]:
    """Filter stale/unavailable vectors and overwrite price/stock/name from MySQL."""
    ids = product_ids(hits)
    if not ids:
        return []
    connection = connect(**mysql_connection_options(env))
    try:
        cursor = connection.cursor(dictionary=True)
        placeholders = ",".join(["%s"] * len(ids))
        cursor.execute(
            f"SELECT IDSanPham,TenSanPham,GiaTien,SoLuong FROM sanpham WHERE IDSanPham IN ({placeholders})",
            ids,
        )
        live = {int(row["IDSanPham"]): row for row in cursor.fetchall()}
        result: list[dict] = []
        for original in hits:
            try:
                product_id = int((original.get("meta") or {}).get("IDSanPham", original.get("id")))
            except (TypeError, ValueError):
                continue
            row = live.get(product_id)
            if not row or int(row["SoLuong"]) <= 0:
                continue
            hit = dict(original)
            hit["meta"] = dict(original.get("meta") or {}) | {
                "IDSanPham": product_id,
                "TenSanPham": row["TenSanPham"],
                "Gia": int(row["GiaTien"]),
                "SoLuong": int(row["SoLuong"]),
                "commerce_source": "mysql-live",
            }
            result.append(hit)
        return result
    finally:
        connection.close()


def shipping_policy_context(connect: Callable[..., object], env: dict[str, str] | None = None) -> str | None:
    """Return the active shipping policy summary, or None when admin has not activated one."""
    connection = connect(**mysql_connection_options(env))
    try:
        cursor = connection.cursor(dictionary=True)
        cursor.execute(
            "SELECT id,origin_label AS warehouse_address,free_shipping_min AS free_shipping_threshold FROM shipping_config_versions "
            "WHERE status='active' LIMIT 1"
        )
        version = cursor.fetchone()
        if not version:
            return None
        cursor.execute(
            "SELECT r.name AS region_name,b.min_weight_grams,b.max_weight_grams,b.fee AS fee_amount "
            "FROM shipping_regions r JOIN shipping_rate_bands b ON b.region_id=r.id "
            "WHERE r.version_id=%s ORDER BY r.sort_order,r.id,b.min_weight_grams",
            [int(version["id"])],
        )
        rows = cursor.fetchall()
        if not rows:
            return None
        lines = [f"Kho gửi hàng: {version['warehouse_address']}."]
        threshold = version.get("free_shipping_threshold")
        if threshold is not None:
            lines.append(f"Miễn phí vận chuyển khi tiền hàng đạt {int(threshold)} VND.")
        for row in rows:
            upper = "trở lên" if row["max_weight_grams"] is None else f"đến {int(row['max_weight_grams'])}g"
            lines.append(
                f"{row['region_name']}: từ {int(row['min_weight_grams'])}g {upper}, phí {int(row['fee_amount'])} VND."
            )
        lines.append("Phí chính xác được tính từ khu vực nhận hàng, tổng khối lượng và tiền hàng tại checkout.")
        return "\n".join(lines)
    finally:
        connection.close()


def stale_vector_ids(indexed_ids: Iterable[str], database_ids: Iterable[str]) -> list[str]:
    return sorted(set(map(str, indexed_ids)) - set(map(str, database_ids)))


def retry_delay(attempt: int) -> int:
    """Exponential retry delay capped at 15 minutes."""
    return min(900, 5 * (2 ** max(0, min(attempt - 1, 8))))


# ===== Popularity, name matching and comparison data (deterministic, straight from MySQL)

# Product families the shop sells: customer words -> terms matched against category or product name.
CATEGORY_TERMS: dict[str, tuple[str, ...]] = {
    "son": ("son",),
    "chống nắng": ("chống nắng",),
    "rửa mặt": ("rửa mặt",),
    "tẩy trang": ("tẩy trang",),
    "mặt nạ": ("mặt nạ",),
    "kem nền": ("kem nền",),
    "kẻ mắt": ("kẻ mắt",),
    "trang điểm": ("trang điểm", "makeup"),
    "makeup": ("trang điểm", "makeup"),
    "dưỡng ẩm": ("dưỡng ẩm", "kem dưỡng"),
    "kem dưỡng": ("kem dưỡng", "dưỡng ẩm"),
}

_PRODUCT_COLUMNS = (
    "s.IDSanPham, s.TenSanPham, s.GiaTien, s.SoLuong, s.MoTaChiTiet, s.NoiBat, "
    "th.TenThuongHieu, dm.TenDanhMuc, "
    "COALESCE(v.views,0) AS views, COALESCE(w.likes,0) AS likes, COALESCE(o.sold,0) AS sold"
)
# Sold units weigh most, then wishlist adds, then views. Cancelled orders do not count as sales.
_POPULARITY_JOINS = (
    "JOIN danhmuc dm ON dm.IDDanhMuc = s.IDDanhMuc "
    "JOIN thuonghieu th ON th.IDThuongHieu = s.IDThuongHieu "
    "LEFT JOIN (SELECT CAST(JSON_UNQUOTE(JSON_EXTRACT(metadata,'$.product_id')) AS UNSIGNED) AS pid, COUNT(*) AS views "
    "           FROM user_events WHERE event_type='product_view' GROUP BY pid) v ON v.pid = s.IDSanPham "
    "LEFT JOIN (SELECT ID_SanPham AS pid, COUNT(*) AS likes FROM danhsachyeuthich GROUP BY ID_SanPham) w ON w.pid = s.IDSanPham "
    "LEFT JOIN (SELECT c.ID_SanPham AS pid, SUM(c.SoLuong) AS sold FROM donhangchitiet c "
    "           JOIN donhang d ON d.ID_DonHang = c.ID_DonHang WHERE d.TrangThai <> -1 GROUP BY c.ID_SanPham) o ON o.pid = s.IDSanPham "
)
_POPULARITY_SCORE = "(COALESCE(o.sold,0) * 5 + COALESCE(w.likes,0) * 3 + COALESCE(v.views,0))"


def infer_category_terms(text: str) -> tuple[str, ...]:
    lowered = (text or "").lower()
    terms: list[str] = []
    for keyword, mapped in CATEGORY_TERMS.items():
        if keyword in lowered:
            terms.extend(mapped)
    return tuple(dict.fromkeys(terms))


def plain_text(html: str | None, limit: int = 160) -> str:
    text = re.sub(r"<[^>]+>", " ", html or "")
    text = re.sub(r"\s+", " ", html_lib.unescape(text)).strip()
    return text if len(text) <= limit else text[: limit - 1].rstrip() + "…"


def _product_hit(row: dict) -> dict:
    product_id = int(row["IDSanPham"])
    return {
        "id": str(product_id),
        "doc": plain_text(row.get("MoTaChiTiet"), 2000),
        "distance": None,
        "meta": {
            "IDSanPham": product_id,
            "TenSanPham": row["TenSanPham"],
            "ThuongHieu": row.get("TenThuongHieu") or "",
            "DanhMuc": row.get("TenDanhMuc") or "",
            "Gia": int(row["GiaTien"]),
            "SoLuong": int(row["SoLuong"]),
            "views": int(row.get("views") or 0),
            "likes": int(row.get("likes") or 0),
            "sold": int(row.get("sold") or 0),
            "commerce_source": "mysql-live",
        },
    }


def popular_products(connect: Callable[..., object], terms: Iterable[str] = (), limit: int = 3,
                     env: dict[str, str] | None = None) -> list[dict]:
    """Most viewed / wishlisted / sold in-stock products, optionally within a product family.
    The family is matched on the product name first (categories can be mis-assigned, e.g. an
    eyeliner filed under lip balm) and on the category only when no name matches."""
    terms = [t for t in terms if t]
    limit = max(1, min(int(limit), 5))
    connection = connect(**mysql_connection_options(env))
    try:
        cursor = connection.cursor(dictionary=True)
        columns = ["s.TenSanPham", "dm.TenDanhMuc"] if terms else [None]
        for column in columns:
            where = ["s.SoLuong > 0", "s.GiaTien > 0"]
            params: list = []
            if column:
                where.append("(" + " OR ".join([f"{column} LIKE %s"] * len(terms)) + ")")
                params.extend(f"%{term}%" for term in terms)
            cursor.execute(
                f"SELECT {_PRODUCT_COLUMNS} FROM sanpham s {_POPULARITY_JOINS} WHERE {' AND '.join(where)} "
                f"ORDER BY {_POPULARITY_SCORE} DESC, s.NoiBat DESC, s.IDSanPham DESC LIMIT %s",
                [*params, limit],
            )
            rows = cursor.fetchall()
            if rows:
                return [_product_hit(row) for row in rows]
        return []
    finally:
        connection.close()


def products_by_ids(connect: Callable[..., object], ids: Iterable[int], env: dict[str, str] | None = None) -> list[dict]:
    """In-stock products for the given ids, in the requested order (used for 'compare these two')."""
    wanted: list[int] = []
    for value in ids:
        try:
            wanted.append(int(value))
        except (TypeError, ValueError):
            continue
    wanted = list(dict.fromkeys(i for i in wanted if i > 0))[:5]
    if not wanted:
        return []
    connection = connect(**mysql_connection_options(env))
    try:
        cursor = connection.cursor(dictionary=True)
        placeholders = ",".join(["%s"] * len(wanted))
        cursor.execute(
            f"SELECT {_PRODUCT_COLUMNS} FROM sanpham s {_POPULARITY_JOINS} "
            f"WHERE s.IDSanPham IN ({placeholders}) AND s.SoLuong > 0 AND s.GiaTien > 0",
            wanted,
        )
        rows = {int(row["IDSanPham"]): row for row in cursor.fetchall()}
        return [_product_hit(rows[i]) for i in wanted if i in rows]
    finally:
        connection.close()


_NAME_STOPWORDS = {
    "so", "sánh", "sanh", "với", "voi", "và", "va", "hay", "hoặc", "giữa", "cái", "nào", "nao", "tốt", "hơn",
    "sản", "phẩm", "cho", "mình", "tôi", "em", "bạn", "của", "này", "kia", "giúp", "xem", "thử", "nên", "chọn",
    "loại", "the", "and", "or", "vs", "compare", "with",
}


def _name_tokens(text: str) -> set[str]:
    return {t for t in re.findall(r"\w+", (text or "").lower()) if len(t) >= 2 and t not in _NAME_STOPWORDS}


# Customers type English marketing words; product names in the shop are Vietnamese.
_NAME_SYNONYMS: dict[str, tuple[str, ...]] = {
    "water": ("chống", "thấm", "nước"), "waterproof": ("chống", "thấm", "nước"),
    "hydrating": ("dưỡng", "ẩm", "cấp"), "moisturizing": ("dưỡng", "ẩm"), "moisture": ("dưỡng", "ẩm"),
    "balm": ("dưỡng",), "matte": ("lì",), "velvet": ("nhung", "lì"), "sunscreen": ("chống", "nắng"),
    "cleanser": ("rửa", "mặt"), "foundation": ("nền",), "lipstick": ("son",), "lip": ("son",),
}
# Words that only say which family a product belongs to; a name match needs something more specific.
_GENERIC_TOKENS = {"son", "môi", "kem", "sữa", "nước", "lip"}
# A family word the customer typed must appear in the product name: "son hydrating" is never a cream.
_FAMILY_TOKENS = {"son", "kem", "sữa", "nước"}
_SPLIT_PARTS = re.compile(r"\s+(?:và|với|hay|hoặc|vs|or|and)\s+|,", flags=re.IGNORECASE)
_COMPARE_PREFIX = re.compile(r"(?i)^\s*(?:so\s*s[áa]nh|compare)\s*:?\s*")


def _part_tokens(part: str) -> set[str]:
    tokens = _name_tokens(part)
    for token in list(tokens):
        tokens.update(_NAME_SYNONYMS.get(token, ()))
    return tokens


def match_named_parts(connect: Callable[..., object], text: str,
                      env: dict[str, str] | None = None) -> list[dict]:
    """Each product the customer named ("so sánh Cerave và L'Oreal"), as {"name", "hit"}; "hit" is
    None when the shop has nothing matching that name. Parts that name no specific product are skipped."""
    parts = [p.strip() for p in _SPLIT_PARTS.split(text or "") if p and p.strip()]
    named = [p for p in parts if _name_tokens(p) - _GENERIC_TOKENS]
    if not named:
        return []
    connection = connect(**mysql_connection_options(env))
    try:
        cursor = connection.cursor(dictionary=True)
        cursor.execute(f"SELECT {_PRODUCT_COLUMNS} FROM sanpham s {_POPULARITY_JOINS} WHERE s.SoLuong > 0 AND s.GiaTien > 0")
        rows = cursor.fetchall()
    finally:
        connection.close()

    catalog = [(row, _name_tokens(f"{row['TenSanPham']} {row.get('TenThuongHieu') or ''}")) for row in rows]
    chosen: list[int] = []
    result: list[dict] = []
    for part in named:
        typed = _name_tokens(part)
        family = typed & _FAMILY_TOKENS
        specific = typed - _GENERIC_TOKENS
        tokens = _part_tokens(part)
        best, best_score = None, 0
        for row, name_tokens in catalog:
            if int(row["IDSanPham"]) in chosen or not family <= name_tokens:
                continue
            # Most of the specific words (directly or through a synonym) must be in the name,
            # so "son mac" never falls back to any lipstick just because it says "son".
            covered = sum(1 for t in specific if t in name_tokens or set(_NAME_SYNONYMS.get(t, ())) & name_tokens)
            if covered * 2 < len(specific):
                continue
            score = len(tokens & name_tokens)
            if score > best_score:
                best, best_score = row, score
        hit = None
        if best is not None:
            chosen.append(int(best["IDSanPham"]))
            hit = _product_hit(best)
        result.append({"name": _COMPARE_PREFIX.sub("", part).strip(" ?.!:"), "hit": hit})
    return result


def match_products_by_name(connect: Callable[..., object], text: str, limit: int = 2,
                           env: dict[str, str] | None = None) -> list[dict]:
    """Products the customer named ("so sánh Cerave và L'Oreal"). Each part of the sentence picks
    its best-matching product, so two names give two different products."""
    parts = [p for p in _SPLIT_PARTS.split(text or "") if p and p.strip()]
    connection = connect(**mysql_connection_options(env))
    try:
        cursor = connection.cursor(dictionary=True)
        cursor.execute(f"SELECT {_PRODUCT_COLUMNS} FROM sanpham s {_POPULARITY_JOINS} WHERE s.SoLuong > 0 AND s.GiaTien > 0")
        rows = cursor.fetchall()
    finally:
        connection.close()

    catalog = [(row, _name_tokens(f"{row['TenSanPham']} {row.get('TenThuongHieu') or ''}")) for row in rows]
    chosen: list[dict] = []
    for part in parts or [text]:
        tokens = _part_tokens(part)
        if not tokens:
            continue
        best, best_score = None, 0
        for row, name_tokens in catalog:
            if any(int(row["IDSanPham"]) == int(c["IDSanPham"]) for c in chosen):
                continue
            score = len(tokens & name_tokens)
            if score > best_score:
                best, best_score = row, score
        # Two shared words (brand + product type) avoid matching on one generic word like "kem".
        if best is not None and best_score >= 2:
            chosen.append(best)
        if len(chosen) >= limit:
            break
    return [_product_hit(row) for row in chosen]
