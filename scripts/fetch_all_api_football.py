import os, time, random, json, argparse, smtplib, traceback
from datetime import datetime

import requests
import mysql.connector
from dotenv import load_dotenv
from email.message import EmailMessage

# --------------------------------------------------
# LOAD .env (FORÇAR CAMINHO NO WINDOWS)
# --------------------------------------------------
load_dotenv(dotenv_path=os.path.join(os.path.dirname(__file__), ".env"), override=True)

API_KEY = os.getenv("API_KEY")
BASE_URL = (os.getenv("BASE_URL") or "").rstrip("/")

# Email env (opcional)
MAIL_HOST = os.getenv("MAIL_HOST")
MAIL_PORT = int(os.getenv("MAIL_PORT") or 587)
MAIL_USER = os.getenv("MAIL_USER")
MAIL_PASS = os.getenv("MAIL_PASS")
MAIL_TO = os.getenv("MAIL_TO") or MAIL_USER
MAIL_FROM_NAME = os.getenv("MAIL_FROM_NAME") or "ScoutTalent Fetch"

# --------------------------------------------------
# MYSQL CONNECTION
# --------------------------------------------------
DB = mysql.connector.connect(
    host=os.getenv("DB_HOST") or "127.0.0.1",
    port=int(os.getenv("DB_PORT") or 3307),
    user=os.getenv("DB_USERNAME"),
    password=os.getenv("DB_PASSWORD"),
    database=os.getenv("DB_DATABASE"),
    use_pure=True
)
CURSOR = DB.cursor()

HEADERS = {"x-apisports-key": API_KEY}

# --------------------------------------------------
# CONFIG
# --------------------------------------------------
SLEEP_BASE = 0.35
MAX_RETRIES = 6
RETRY_BACKOFF_BASE = 1.3

TEAM_ID_CACHE = {}         # team_external_id -> teams.id (interno/PK)
TEAM_NAME_CACHE = {}       # team_external_id -> teams.name
LEAGUE_NAME_CACHE = {}     # league_external_id -> leagues.name
LEAGUE_ID_CACHE = {}       # league_external_id -> leagues.id (interno/PK)

# --------------------------------------------------
# HELPERS
# --------------------------------------------------
def send_email(subject: str, body: str):
    if not (MAIL_HOST and MAIL_USER and MAIL_PASS and MAIL_TO):
        return
    msg = EmailMessage()
    msg["Subject"] = subject
    msg["From"] = f"{MAIL_FROM_NAME} <{MAIL_USER}>"
    msg["To"] = MAIL_TO
    msg.set_content(body)
    with smtplib.SMTP(MAIL_HOST, MAIL_PORT, timeout=30) as s:
        s.ehlo()
        s.starttls()
        s.login(MAIL_USER, MAIL_PASS)
        s.send_message(msg)

def now_str():
    return datetime.now().strftime("%Y-%m-%d %H:%M:%S")

def json_dump(obj):
    try:
        return json.dumps(obj, ensure_ascii=False)
    except Exception:
        return json.dumps({"_json_dump_error": True}, ensure_ascii=False)

def latest_season_year():
    # Jul..Dez: ano atual | Jan..Jun: ano anterior
    today = datetime.today()
    return today.year if today.month >= 7 else (today.year - 1)

def safe_get(obj, path, default=None):
    cur = obj
    for k in path:
        if not isinstance(cur, dict):
            return default
        cur = cur.get(k)
        if cur is None:
            return default
    return cur

def to_float(x):
    if x is None:
        return None
    try:
        return float(str(x).replace(",", "."))
    except Exception:
        return None

def to_int(x):
    if x is None:
        return None
    try:
        return int(x)
    except Exception:
        try:
            return int(float(str(x).replace(",", ".")))
        except Exception:
            return None

def season_candidates(season: int):
    cand = []
    for s in [season, season - 1, season + 1]:
        if s not in cand and s > 1900:
            cand.append(s)
    return cand

def season_fallback_chain(season_target: int, max_back: int, min_season: int = 2000):
    for i in range(max_back + 1):
        s = season_target - i
        if s >= min_season:
            yield s

# --------------------------------------------------
# ROBUST API GET
# --------------------------------------------------
def api_get(endpoint, params=None):
    if not API_KEY:
        raise RuntimeError("API_KEY em falta no .env")
    if not BASE_URL:
        raise RuntimeError("BASE_URL em falta no .env")

    url = f"{BASE_URL}{endpoint}" if endpoint.startswith("/") else f"{BASE_URL}/{endpoint}"
    params = params or {}

    last_exc = None
    for attempt in range(1, MAX_RETRIES + 1):
        try:
            r = requests.get(url, headers=HEADERS, params=params, timeout=45)

            if r.status_code in (429, 500, 502, 503, 504):
                raise RuntimeError(f"HTTP {r.status_code}")

            try:
                data = r.json()
            except Exception:
                raise RuntimeError(f"Resposta não-JSON | status={r.status_code} | body={r.text[:200]}")

            errs = data.get("errors")
            if errs:
                raise RuntimeError(f"API errors: {errs}")

            time.sleep(SLEEP_BASE + random.uniform(0, 0.15))
            return data.get("response", None)

        except Exception as e:
            last_exc = e
            wait = (RETRY_BACKOFF_BASE ** (attempt - 1)) + random.uniform(0, 0.35)
            print(f"[WARN] api_get falhou ({endpoint}) params={params} attempt={attempt}/{MAX_RETRIES} err={e} -> wait {wait:.2f}s", flush=True)
            time.sleep(wait)

    raise RuntimeError(f"api_get falhou em definitivo: {endpoint} params={params} last={last_exc}")

def api_try_many(endpoint, list_of_params):
    last_err = None
    for p in list_of_params:
        try:
            resp = api_get(endpoint, p)
            if isinstance(resp, list) and resp:
                return resp, p
            if isinstance(resp, dict) and resp:
                return resp, p
        except Exception as e:
            last_err = e
            continue
    if last_err:
        print(f"[WARN] api_try_many sem dados ({endpoint}) last_err={last_err}", flush=True)
    return None, None

# --------------------------------------------------
# SCHEMA helpers
# --------------------------------------------------
def _col_exists(table: str, column: str) -> bool:
    CURSOR.execute("""
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = %s
          AND COLUMN_NAME = %s
    """, (table, column))
    return int(CURSOR.fetchone()[0]) > 0

def _idx_exists(table: str, index_name: str) -> bool:
    CURSOR.execute("""
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = %s
          AND INDEX_NAME = %s
    """, (table, index_name))
    return int(CURSOR.fetchone()[0]) > 0

def ensure_team_statistics_denorm_fields():
    """
    Garante colunas e índices:
    - league_country, league_name, league_logo, team_name, team_logo
    - idx_team_stats_league_country, idx_ts_league_name, idx_ts_team_name
    """
    try:
        add_cols = []
        if not _col_exists("team_statistics", "league_country"):
            add_cols.append("ADD COLUMN league_country VARCHAR(120) NULL AFTER season")
        if not _col_exists("team_statistics", "league_name"):
            add_cols.append("ADD COLUMN league_name VARCHAR(190) NULL AFTER league_country")
        if not _col_exists("team_statistics", "league_logo"):
            add_cols.append("ADD COLUMN league_logo VARCHAR(255) NULL AFTER league_name")
        if not _col_exists("team_statistics", "team_name"):
            add_cols.append("ADD COLUMN team_name VARCHAR(190) NULL AFTER league_logo")
        if not _col_exists("team_statistics", "team_logo"):
            add_cols.append("ADD COLUMN team_logo VARCHAR(255) NULL AFTER team_name")

        if add_cols:
            sql = "ALTER TABLE team_statistics " + ", ".join(add_cols)
            print(f"[SCHEMA] {sql}", flush=True)
            CURSOR.execute(sql)
            DB.commit()

        if not _idx_exists("team_statistics", "idx_team_stats_league_country"):
            print("[SCHEMA] a criar index idx_team_stats_league_country", flush=True)
            CURSOR.execute("CREATE INDEX idx_team_stats_league_country ON team_statistics (league_country)")
            DB.commit()

        if not _idx_exists("team_statistics", "idx_ts_league_name"):
            print("[SCHEMA] a criar index idx_ts_league_name", flush=True)
            CURSOR.execute("CREATE INDEX idx_ts_league_name ON team_statistics (league_name)")
            DB.commit()

        if not _idx_exists("team_statistics", "idx_ts_team_name"):
            print("[SCHEMA] a criar index idx_ts_team_name", flush=True)
            CURSOR.execute("CREATE INDEX idx_ts_team_name ON team_statistics (team_name)")
            DB.commit()

    except Exception as e:
        print(f"[WARN] ensure_team_statistics_denorm_fields falhou: {e}", flush=True)

def ensure_schema():
    # countries
    CURSOR.execute("""
        CREATE TABLE IF NOT EXISTS countries (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          name VARCHAR(255) NULL,
          code VARCHAR(50) NULL,
          continent VARCHAR(120) NULL,
          flag VARCHAR(255) NULL,
          meta LONGTEXT NULL,
          created_at TIMESTAMP NULL DEFAULT NULL,
          updated_at TIMESTAMP NULL DEFAULT NULL,
          PRIMARY KEY (id),
          UNIQUE KEY uq_countries_name_code (name, code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    """)

    # leagues
    CURSOR.execute("""
        CREATE TABLE IF NOT EXISTS leagues (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          external_id BIGINT UNSIGNED NULL,
          name VARCHAR(255) NULL,
          country VARCHAR(255) NULL,
          logo VARCHAR(255) NULL,
          meta LONGTEXT NULL,
          created_at TIMESTAMP NULL DEFAULT NULL,
          updated_at TIMESTAMP NULL DEFAULT NULL,
          PRIMARY KEY (id),
          UNIQUE KEY leagues_external_id_unique (external_id),
          KEY idx_leagues_name (name),
          KEY idx_leagues_country (country)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    """)

    # teams
    CURSOR.execute("""
        CREATE TABLE IF NOT EXISTS teams (
          id BIGINT UNSIGNED NOT NULL,
          external_id BIGINT UNSIGNED NULL COMMENT 'ID na API',
          name VARCHAR(255) NULL,
          country VARCHAR(255) NULL,
          league_id BIGINT UNSIGNED NULL,
          code VARCHAR(255) NULL,
          founded VARCHAR(255) NULL,
          venue VARCHAR(255) NULL,
          logo VARCHAR(255) NULL,
          meta LONGTEXT NULL,
          created_at TIMESTAMP NULL DEFAULT NULL,
          updated_at TIMESTAMP NULL DEFAULT NULL,
          PRIMARY KEY (id),
          UNIQUE KEY teams_external_id_unique (external_id),
          KEY idx_teams_league_id (league_id),
          KEY idx_teams_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    """)

    # players
    CURSOR.execute("""
        CREATE TABLE IF NOT EXISTS players (
          id BIGINT UNSIGNED NOT NULL,
          external_id BIGINT UNSIGNED DEFAULT NULL COMMENT 'ID externo fornecido pela API',
          name VARCHAR(255) DEFAULT NULL COMMENT 'Nome do jogador',
          photo VARCHAR(255) DEFAULT NULL,
          age SMALLINT UNSIGNED DEFAULT NULL COMMENT 'Idade',
          nationality VARCHAR(255) DEFAULT NULL COMMENT 'Nacionalidade',
          height VARCHAR(255) DEFAULT NULL COMMENT 'Altura (ex: 179 cm)',
          height_cm SMALLINT UNSIGNED DEFAULT NULL,
          weight VARCHAR(255) DEFAULT NULL COMMENT 'Peso (ex: 75 kg)',
          weight_kg SMALLINT UNSIGNED DEFAULT NULL,
          birth_date DATE DEFAULT NULL COMMENT 'Data de nascimento',
          team_id BIGINT UNSIGNED DEFAULT NULL COMMENT 'ID da equipa (BD local)',
          team_name VARCHAR(255) DEFAULT NULL COMMENT 'Nome da equipa na season',
          league_id BIGINT UNSIGNED DEFAULT NULL,
          league_name VARCHAR(255) DEFAULT NULL,
          appearances SMALLINT UNSIGNED DEFAULT NULL COMMENT 'Aparições / jogos',
          minutes INT UNSIGNED DEFAULT NULL COMMENT 'Minutos jogados',
          goals SMALLINT UNSIGNED DEFAULT NULL COMMENT 'Golos',
          yellow_cards SMALLINT UNSIGNED DEFAULT NULL COMMENT 'Cartões amarelos',
          red_cards SMALLINT UNSIGNED DEFAULT NULL COMMENT 'Cartões vermelhos',
          position VARCHAR(255) DEFAULT NULL COMMENT 'Posição do jogador',
          rating DECIMAL(4,2) DEFAULT NULL COMMENT 'Classificação média',
          meta LONGTEXT NULL COMMENT 'JSON completo retornado pela API',
          created_at TIMESTAMP NULL DEFAULT NULL,
          updated_at TIMESTAMP NULL DEFAULT NULL,
          PRIMARY KEY (id),
          UNIQUE KEY players_external_id_unique (external_id),
          KEY idx_players_team_id (team_id),
          KEY idx_players_league_id (league_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    """)

    # coachs (NOVO)
    CURSOR.execute("""
        CREATE TABLE IF NOT EXISTS coachs (
          id BIGINT UNSIGNED NOT NULL,
          external_id BIGINT UNSIGNED DEFAULT NULL COMMENT 'ID na API',
          name VARCHAR(255) NULL,
          nationality VARCHAR(255) NULL,
          age INT NULL,
          birth_date DATE NULL,
          photo VARCHAR(255) NULL,
          meta LONGTEXT NULL,
          created_at TIMESTAMP NULL DEFAULT NULL,
          updated_at TIMESTAMP NULL DEFAULT NULL,
          team_id BIGINT UNSIGNED DEFAULT NULL,
          PRIMARY KEY (id),
          UNIQUE KEY coachs_external_id_unique (external_id),
          KEY idx_coachs_team_id (team_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    """)

    # pivot league_teams
    CURSOR.execute("""
        CREATE TABLE IF NOT EXISTS league_teams (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          league_external_id BIGINT UNSIGNED NOT NULL,
          team_id BIGINT UNSIGNED NOT NULL,
          season INT NULL,
          created_at TIMESTAMP NULL DEFAULT NULL,
          updated_at TIMESTAMP NULL DEFAULT NULL,
          PRIMARY KEY (id),
          UNIQUE KEY uq_league_team_season (league_external_id, team_id, season),
          KEY idx_league (league_external_id),
          KEY idx_team (team_id),
          KEY idx_season (season)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    """)

    # team_statistics
    CURSOR.execute("""
        CREATE TABLE IF NOT EXISTS team_statistics (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          team_id BIGINT UNSIGNED NOT NULL,
          league_id BIGINT UNSIGNED DEFAULT NULL,
          season INT DEFAULT NULL,
          league_country VARCHAR(120) DEFAULT NULL,
          league_name VARCHAR(190) DEFAULT NULL,
          league_logo VARCHAR(255) DEFAULT NULL,
          team_name VARCHAR(190) DEFAULT NULL,
          team_logo VARCHAR(255) DEFAULT NULL,
          data LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
          form VARCHAR(255) DEFAULT NULL,
          goals_for_avg DECIMAL(6,2) DEFAULT NULL,
          goals_against_avg DECIMAL(6,2) DEFAULT NULL,
          fixtures_played INT DEFAULT NULL,
          synced_at TIMESTAMP NULL DEFAULT NULL,
          created_at TIMESTAMP NULL DEFAULT NULL,
          updated_at TIMESTAMP NULL DEFAULT NULL,
          PRIMARY KEY (id),
          UNIQUE KEY team_stats_unique (team_id, league_id, season),
          KEY team_statistics_team_id_index (team_id),
          KEY team_statistics_league_id_index (league_id),
          KEY team_statistics_season_index (season),
          KEY team_statistics_synced_at_index (synced_at),
          KEY idx_team_stats_league_country (league_country),
          KEY idx_ts_league_name (league_name),
          KEY idx_ts_team_name (team_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    """)

    DB.commit()
    ensure_team_statistics_denorm_fields()

# --------------------------------------------------
# DB HELPERS (IDs internos a partir dos external)
# --------------------------------------------------
def get_team_internal_id(team_external_id: int):
    team_external_id = int(team_external_id)
    if team_external_id in TEAM_ID_CACHE:
        return TEAM_ID_CACHE[team_external_id]

    CURSOR.execute("SELECT id, name FROM teams WHERE external_id=%s LIMIT 1", (team_external_id,))
    row = CURSOR.fetchone()
    if not row:
        TEAM_ID_CACHE[team_external_id] = None
        return None

    tid, tname = int(row[0]), row[1]
    TEAM_ID_CACHE[team_external_id] = tid
    TEAM_NAME_CACHE[team_external_id] = tname
    return tid

def get_league_internal_id(league_external_id: int):
    league_external_id = int(league_external_id)
    if league_external_id in LEAGUE_ID_CACHE:
        return LEAGUE_ID_CACHE[league_external_id]

    CURSOR.execute("SELECT id, name FROM leagues WHERE external_id=%s LIMIT 1", (league_external_id,))
    row = CURSOR.fetchone()
    if not row:
        LEAGUE_ID_CACHE[league_external_id] = None
        LEAGUE_NAME_CACHE[league_external_id] = None
        return None

    lid, lname = int(row[0]), row[1]
    LEAGUE_ID_CACHE[league_external_id] = lid
    LEAGUE_NAME_CACHE[league_external_id] = lname
    return lid

def upsert_league_team(league_external_id: int, team_internal_id: int, season_target: int):
    CURSOR.execute("""
        INSERT INTO league_teams (league_external_id, team_id, season, created_at, updated_at)
        VALUES (%s,%s,%s,NOW(),NOW())
        ON DUPLICATE KEY UPDATE
            season=VALUES(season),
            updated_at=NOW()
    """, (int(league_external_id), int(team_internal_id), int(season_target)))

def get_pairs_from_league_teams(season_target: int):
    CURSOR.execute("""
        SELECT DISTINCT lt.team_id, lt.league_external_id, t.external_id
        FROM league_teams lt
        JOIN teams t ON t.id = lt.team_id
        WHERE t.external_id IS NOT NULL
          AND lt.season = %s
        ORDER BY lt.league_external_id, t.external_id
    """, (int(season_target),))
    return CURSOR.fetchall()

# --------------------------------------------------
# FETCH: COUNTRIES/LEAGUES
# --------------------------------------------------
def fetch_countries():
    print("[INFO] Countries", flush=True)
    ok = 0
    for c in (api_get("/countries") or []):
        CURSOR.execute("""
            INSERT INTO countries (name, code, continent, flag, meta, created_at, updated_at)
            VALUES (%s,%s,%s,%s,%s,NOW(),NOW())
            ON DUPLICATE KEY UPDATE
                continent=VALUES(continent),
                flag=VALUES(flag),
                meta=VALUES(meta),
                updated_at=NOW()
        """, (
            c.get("name"),
            c.get("code"),
            c.get("continent"),
            c.get("flag"),
            json_dump(c)
        ))
        ok += 1
    DB.commit()
    print(f"[INFO] Countries inserted/updated: {ok}", flush=True)

def fetch_leagues():
    print("[INFO] Leagues", flush=True)
    ok = 0
    for l in (api_get("/leagues") or []):
        league = l.get("league", {}) or {}
        country = l.get("country", {}) or {}

        CURSOR.execute("""
            INSERT INTO leagues (external_id, name, country, logo, meta, created_at, updated_at)
            VALUES (%s,%s,%s,%s,%s,NOW(),NOW())
            ON DUPLICATE KEY UPDATE
                name=VALUES(name),
                country=VALUES(country),
                logo=VALUES(logo),
                meta=VALUES(meta),
                updated_at=NOW()
        """, (
            league.get("id"),
            league.get("name"),
            country.get("name"),
            league.get("logo"),
            json_dump(l)
        ))
        ok += 1

    DB.commit()
    print(f"[INFO] Leagues inserted/updated: {ok}", flush=True)

# --------------------------------------------------
# FETCH: TEAMS + pivot league_teams (season TARGET)
# --------------------------------------------------
def fetch_all_teams(season_target: int):
    print(f"[INFO] Teams (all leagues) season_target={season_target}", flush=True)

    CURSOR.execute("SELECT external_id FROM leagues WHERE external_id IS NOT NULL")
    leagues = [int(row[0]) for row in CURSOR.fetchall()]
    if not leagues:
        print("[WARN] leagues está vazia. Corre modo FULL (fetch_leagues) primeiro.", flush=True)
        return

    total_upserts = 0
    pivot_upserts = 0
    used_fallback = 0
    no_teams = []

    cands = season_candidates(season_target)

    for league_ext in leagues:
        params_list = [{"league": league_ext, "season": s} for s in cands]
        teams, used_params = api_try_many("/teams", params_list)

        if not teams:
            no_teams.append(league_ext)
            continue

        season_used = int(used_params.get("season")) if used_params else int(season_target)
        if season_used != int(season_target):
            used_fallback += 1

        for t in teams:
            team = t.get("team", {}) or {}
            venue = t.get("venue", {}) or {}
            team_ext = team.get("id")
            if not team_ext:
                continue

            CURSOR.execute("""
                INSERT INTO teams
                (id, external_id, name, country, code, founded, venue, logo, league_id, meta, created_at, updated_at)
                VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,NOW(),NOW())
                ON DUPLICATE KEY UPDATE
                    name=VALUES(name),
                    country=VALUES(country),
                    code=VALUES(code),
                    founded=VALUES(founded),
                    venue=VALUES(venue),
                    logo=VALUES(logo),
                    league_id=VALUES(league_id),
                    meta=VALUES(meta),
                    updated_at=NOW()
            """, (
                int(team_ext),
                int(team_ext),
                team.get("name"),
                team.get("country"),
                team.get("code"),
                team.get("founded"),
                venue.get("name"),
                team.get("logo"),
                int(league_ext),  # (mantido como tinhas: external_id da liga)
                json_dump(t)
            ))
            total_upserts += 1

            team_internal_id = get_team_internal_id(int(team_ext))
            if team_internal_id:
                upsert_league_team(int(league_ext), int(team_internal_id), int(season_target))
                pivot_upserts += 1

        DB.commit()

    print(f"[INFO] Teams upserts: {total_upserts}", flush=True)
    print(f"[INFO] league_teams upserts (season_target): {pivot_upserts}", flush=True)
    print(f"[INFO] Ligas onde usei fallback para obter equipas: {used_fallback}", flush=True)
    if no_teams:
        print(f"[WARN] Ligas sem equipas: {len(no_teams)} (ex: {no_teams[:20]})", flush=True)

# --------------------------------------------------
# FETCH: TEAM STATISTICS (fallback, grava em season TARGET)
# --------------------------------------------------
def upsert_team_statistics(team_internal_id: int, league_external_id: int, season_target: int, stats_obj: dict, used_season: int):
    league_internal_id = get_league_internal_id(int(league_external_id))
    if league_internal_id is None:
        print(f"[WARN] Liga interna não encontrada para external_id={league_external_id} (team_id={team_internal_id})", flush=True)
        return

    form = safe_get(stats_obj, ["form"], None)
    gf_avg = to_float(safe_get(stats_obj, ["goals", "for", "average", "total"], None))
    ga_avg = to_float(safe_get(stats_obj, ["goals", "against", "average", "total"], None))
    fixtures_played = to_int(safe_get(stats_obj, ["fixtures", "played", "total"], None))

    league_country = safe_get(stats_obj, ["league", "country"], None)
    league_name    = safe_get(stats_obj, ["league", "name"], None)
    league_logo    = safe_get(stats_obj, ["league", "logo"], None)
    team_name      = safe_get(stats_obj, ["team", "name"], None)
    team_logo      = safe_get(stats_obj, ["team", "logo"], None)

    wrapped = {
        "season_target": int(season_target),
        "used_season": int(used_season),
        "league_external_id": int(league_external_id),
        "league_internal_id": int(league_internal_id),
        "team_internal_id": int(team_internal_id),
        "payload": stats_obj
    }

    CURSOR.execute("""
        INSERT INTO team_statistics
        (team_id, league_id, season, league_country, league_name, league_logo, team_name, team_logo,
         data, form, goals_for_avg, goals_against_avg, fixtures_played, synced_at, created_at, updated_at)
        VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,NOW(),NOW(),NOW())
        ON DUPLICATE KEY UPDATE
            league_country=VALUES(league_country),
            league_name=VALUES(league_name),
            league_logo=VALUES(league_logo),
            team_name=VALUES(team_name),
            team_logo=VALUES(team_logo),
            data=VALUES(data),
            form=VALUES(form),
            goals_for_avg=VALUES(goals_for_avg),
            goals_against_avg=VALUES(goals_against_avg),
            fixtures_played=VALUES(fixtures_played),
            synced_at=VALUES(synced_at),
            updated_at=NOW()
    """, (
        int(team_internal_id),
        int(league_internal_id),
        int(season_target),
        league_country,
        league_name,
        league_logo,
        team_name,
        team_logo,
        json_dump(wrapped),
        form,
        gf_avg,
        ga_avg,
        fixtures_played
    ))

def fetch_team_statistics(season_target: int, max_back: int):
    print(f"[INFO] Team Statistics (via league_teams) target_season={season_target} max_back={max_back}", flush=True)

    rows = get_pairs_from_league_teams(season_target=season_target)
    total = len(rows)
    if total == 0:
        print("[WARN] Sem pares em league_teams para esta época. Corre fetch_all_teams primeiro.", flush=True)
        return

    ok = 0
    skip = 0
    fail = 0
    fallback_used = 0

    for i, (team_internal_id, league_ext, team_ext) in enumerate(rows, start=1):
        try:
            if i % 200 == 0 or i == 1 or i == total:
                print(f"[PROGRESS] TeamStats {i}/{total} (league_ext={league_ext} team_ext={team_ext} target={season_target})", flush=True)

            chosen_season = None
            stats_obj = None

            for s in season_fallback_chain(int(season_target), int(max_back)):
                stats = api_get("/teams/statistics", {
                    "league": int(league_ext),
                    "season": int(s),
                    "team": int(team_ext),
                })
                if isinstance(stats, dict) and stats:
                    chosen_season = int(s)
                    stats_obj = stats
                    break

            if chosen_season is None:
                skip += 1
                continue

            if chosen_season != int(season_target):
                fallback_used += 1

            upsert_team_statistics(int(team_internal_id), int(league_ext), int(season_target), stats_obj, chosen_season)
            ok += 1

            if ok % 50 == 0:
                DB.commit()
                print(f"[PROGRESS] TeamStats ok={ok}/{total} (fallback_pairs={fallback_used})", flush=True)

        except Exception as e:
            fail += 1
            print(f"[WARN] Team stats falhou team_ext={team_ext} league_ext={league_ext} target={season_target}: {e}", flush=True)
            continue

    DB.commit()
    print(f"[INFO] Team Statistics upserts: {ok} | skips_sem_dados={skip} | fails={fail} | fallback_used_pairs={fallback_used}", flush=True)

# --------------------------------------------------
# FETCH: PLAYERS (por equipa + época) usando /players (paginado)
# --------------------------------------------------
def _extract_player_best_stats(stat: dict):
    games = stat.get("games", {}) or {}
    cards = stat.get("cards", {}) or {}
    goals = stat.get("goals", {}) or {}

    position = games.get("position")
    rating = to_float(games.get("rating"))
    appearances = to_int(games.get("appearences") or games.get("appearances"))
    minutes = to_int(games.get("minutes"))
    yellow = to_int(cards.get("yellow"))
    red = to_int(cards.get("red"))
    goals_total = to_int(goals.get("total"))

    return position, rating, appearances, minutes, goals_total, yellow, red

def fetch_players_for_season(season_target: int, max_back: int):
    print(f"[INFO] Players (via league_teams) target_season={season_target} max_back={max_back}", flush=True)

    rows = get_pairs_from_league_teams(season_target=season_target)
    total = len(rows)
    if total == 0:
        print("[WARN] Sem pares em league_teams para esta época. Corre fetch_all_teams primeiro.", flush=True)
        return

    ok_players = 0
    fail_teams = 0
    teams_with_data = 0
    fallback_used = 0

    for i, (team_internal_id, league_ext, team_ext) in enumerate(rows, start=1):
        try:
            if i % 100 == 0 or i == 1 or i == total:
                print(f"[PROGRESS] Players team {i}/{total} (league_ext={league_ext} team_ext={team_ext} target={season_target})", flush=True)

            chosen_season = None
            players_payloads = []

            for s in season_fallback_chain(int(season_target), int(max_back)):
                page = 1
                local_payloads = []
                while True:
                    resp = api_get("/players", {
                        "team": int(team_ext),
                        "season": int(s),
                        "page": int(page)
                    })

                    if not isinstance(resp, list) or not resp:
                        break

                    local_payloads.extend(resp)
                    page += 1
                    if page > 50:
                        break

                if local_payloads:
                    chosen_season = int(s)
                    players_payloads = local_payloads
                    break

            if not players_payloads:
                continue

            if chosen_season != int(season_target):
                fallback_used += 1

            league_internal_id = get_league_internal_id(int(league_ext))

            team_name = TEAM_NAME_CACHE.get(int(team_ext))
            if not team_name:
                CURSOR.execute("SELECT name FROM teams WHERE id=%s LIMIT 1", (int(team_internal_id),))
                r = CURSOR.fetchone()
                team_name = r[0] if r else None

            for item in players_payloads:
                p = item.get("player", {}) or {}
                stats_list = item.get("statistics", []) or []

                p_ext = p.get("id")
                if not p_ext:
                    continue

                birth = p.get("birth", {}) or {}
                birth_date = birth.get("date")

                height = p.get("height")
                weight = p.get("weight")

                height_cm = None
                if isinstance(height, str) and "cm" in height:
                    height_cm = to_int(height.replace("cm", "").strip())
                weight_kg = None
                if isinstance(weight, str) and "kg" in weight:
                    weight_kg = to_int(weight.replace("kg", "").strip())

                position = rating = appearances = minutes = goals_total = yellow = red = None
                league_name = None
                if stats_list:
                    st0 = stats_list[0] or {}
                    position, rating, appearances, minutes, goals_total, yellow, red = _extract_player_best_stats(st0)
                    league_name = safe_get(st0, ["league", "name"], None)

                wrapped = {
                    "season_target": int(season_target),
                    "used_season": int(chosen_season),
                    "team_external_id": int(team_ext),
                    "team_internal_id": int(team_internal_id),
                    "league_external_id": int(league_ext),
                    "league_internal_id": int(league_internal_id) if league_internal_id is not None else None,
                    "payload": item
                }

                CURSOR.execute("""
                    INSERT INTO players
                    (id, external_id, name, photo, age, nationality, height, height_cm, weight, weight_kg,
                     birth_date, team_id, team_name, league_id, league_name,
                     appearances, minutes, goals, yellow_cards, red_cards,
                     position, rating, meta, created_at, updated_at)
                    VALUES
                    (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,
                     %s,%s,%s,%s,%s,
                     %s,%s,%s,%s,%s,
                     %s,%s,%s,NOW(),NOW())
                    ON DUPLICATE KEY UPDATE
                        name=VALUES(name),
                        photo=VALUES(photo),
                        age=VALUES(age),
                        nationality=VALUES(nationality),
                        height=VALUES(height),
                        height_cm=VALUES(height_cm),
                        weight=VALUES(weight),
                        weight_kg=VALUES(weight_kg),
                        birth_date=VALUES(birth_date),
                        team_id=VALUES(team_id),
                        team_name=VALUES(team_name),
                        league_id=VALUES(league_id),
                        league_name=VALUES(league_name),
                        appearances=VALUES(appearances),
                        minutes=VALUES(minutes),
                        goals=VALUES(goals),
                        yellow_cards=VALUES(yellow_cards),
                        red_cards=VALUES(red_cards),
                        position=VALUES(position),
                        rating=VALUES(rating),
                        meta=VALUES(meta),
                        updated_at=NOW()
                """, (
                    int(p_ext),
                    int(p_ext),
                    p.get("name"),
                    p.get("photo"),
                    to_int(p.get("age")),
                    p.get("nationality"),
                    height,
                    height_cm,
                    weight,
                    weight_kg,
                    birth_date,
                    int(team_internal_id),
                    team_name,
                    int(league_internal_id) if league_internal_id is not None else None,
                    league_name,
                    appearances,
                    minutes,
                    goals_total,
                    yellow,
                    red,
                    position,
                    rating,
                    json_dump(wrapped)
                ))

                ok_players += 1
                if ok_players % 500 == 0:
                    DB.commit()
                    print(f"[PROGRESS] Players upserts={ok_players} (teams_with_data={teams_with_data})", flush=True)

            teams_with_data += 1
            DB.commit()

        except Exception as e:
            fail_teams += 1
            print(f"[WARN] Players falhou team_ext={team_ext} league_ext={league_ext} target={season_target}: {e}", flush=True)
            continue

    DB.commit()
    print(f"[INFO] Players upserts: {ok_players} | teams_ok={teams_with_data}/{total} | teams_fail={fail_teams} | fallback_used_teams={fallback_used}", flush=True)

# --------------------------------------------------
# FETCH: COACHS (NOVO) por equipa + época via /coachs
# --------------------------------------------------
def fetch_coachs_for_season(season_target: int, max_back: int):
    """
    Estratégia:
    - Usa league_teams como fonte.
    - Para cada equipa: tenta /coachs?team=X&season=Y com fallback de época
    - Normalmente não é paginado (mas se vier lista grande, tratamos como lista simples)
    """
    print(f"[INFO] Coachs (via league_teams) target_season={season_target} max_back={max_back}", flush=True)

    rows = get_pairs_from_league_teams(season_target=season_target)
    total = len(rows)
    if total == 0:
        print("[WARN] Sem pares em league_teams para esta época. Corre fetch_all_teams primeiro.", flush=True)
        return

    ok = 0
    teams_with_data = 0
    fail_teams = 0
    fallback_used = 0

    for i, (team_internal_id, league_ext, team_ext) in enumerate(rows, start=1):
        try:
            if i % 200 == 0 or i == 1 or i == total:
                print(f"[PROGRESS] Coachs team {i}/{total} (team_ext={team_ext} target={season_target})", flush=True)

            chosen_season = None
            payloads = []

            for s in season_fallback_chain(int(season_target), int(max_back)):
                resp = api_get("/coachs", {
                    "team": int(team_ext),
                    "season": int(s),
                })

                if isinstance(resp, list) and resp:
                    chosen_season = int(s)
                    payloads = resp
                    break

            if not payloads:
                continue

            if chosen_season != int(season_target):
                fallback_used += 1

            for item in payloads:
                # API costuma devolver objeto do treinador direto (não "coach": {...})
                c = item.get("coach", item) if isinstance(item, dict) else {}
                if not isinstance(c, dict):
                    continue

                c_ext = c.get("id")
                if not c_ext:
                    continue

                birth = c.get("birth", {}) or {}
                birth_date = birth.get("date")

                wrapped = {
                    "season_target": int(season_target),
                    "used_season": int(chosen_season),
                    "team_external_id": int(team_ext),
                    "team_internal_id": int(team_internal_id),
                    "payload": item
                }

                # id = external_id (alinhado com a tua tabela)
                CURSOR.execute("""
                    INSERT INTO coachs
                    (id, external_id, name, nationality, age, birth_date, photo, meta, created_at, updated_at, team_id)
                    VALUES
                    (%s,%s,%s,%s,%s,%s,%s,%s,NOW(),NOW(),%s)
                    ON DUPLICATE KEY UPDATE
                        name=VALUES(name),
                        nationality=VALUES(nationality),
                        age=VALUES(age),
                        birth_date=VALUES(birth_date),
                        photo=VALUES(photo),
                        meta=VALUES(meta),
                        team_id=VALUES(team_id),
                        updated_at=NOW()
                """, (
                    int(c_ext),
                    int(c_ext),
                    c.get("name"),
                    c.get("nationality"),
                    to_int(c.get("age")),
                    birth_date,
                    c.get("photo"),
                    json_dump(wrapped),
                    int(team_internal_id) if team_internal_id is not None else None
                ))

                ok += 1
                if ok % 300 == 0:
                    DB.commit()
                    print(f"[PROGRESS] Coachs upserts={ok}", flush=True)

            teams_with_data += 1
            DB.commit()

        except Exception as e:
            fail_teams += 1
            print(f"[WARN] Coachs falhou team_ext={team_ext} target={season_target}: {e}", flush=True)
            continue

    DB.commit()
    print(f"[INFO] Coachs upserts: {ok} | teams_ok={teams_with_data}/{total} | teams_fail={fail_teams} | fallback_used_teams={fallback_used}", flush=True)

# --------------------------------------------------
# CHECKS
# --------------------------------------------------
def quick_db_checks(season_target: int):
    try:
        CURSOR.execute("SELECT DATABASE()")
        dbname = CURSOR.fetchone()[0]

        CURSOR.execute("SELECT COUNT(*) FROM leagues WHERE external_id IS NOT NULL")
        leagues_n = CURSOR.fetchone()[0]

        CURSOR.execute("SELECT COUNT(*) FROM teams WHERE external_id IS NOT NULL")
        teams_n = CURSOR.fetchone()[0]

        CURSOR.execute("SELECT COUNT(*) FROM league_teams WHERE season=%s", (int(season_target),))
        lt_n = CURSOR.fetchone()[0]

        CURSOR.execute("SELECT COUNT(*) FROM team_statistics WHERE season=%s", (int(season_target),))
        ts_n = CURSOR.fetchone()[0]

        CURSOR.execute("SELECT COUNT(*) FROM players")
        pl_n = CURSOR.fetchone()[0]

        CURSOR.execute("SELECT COUNT(*) FROM coachs")
        ch_n = CURSOR.fetchone()[0]

        print(f"[CHECK] DATABASE()={dbname}", flush=True)
        print(f"[CHECK] leagues = {leagues_n}", flush=True)
        print(f"[CHECK] teams  = {teams_n}", flush=True)
        print(f"[CHECK] league_teams (season={season_target}) = {lt_n}", flush=True)
        print(f"[CHECK] team_statistics (season={season_target}) = {ts_n}", flush=True)
        print(f"[CHECK] players total = {pl_n}", flush=True)
        print(f"[CHECK] coachs total = {ch_n}", flush=True)

    except Exception as e:
        print(f"[WARN] quick_db_checks falhou: {e}", flush=True)

# --------------------------------------------------
# RUN
# --------------------------------------------------
if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--mode", choices=["full", "core", "teamstats", "players", "coachs"], default="full",
                        help="full: countries+leagues+teams+pivot+teamstats+players+coachs | "
                             "core: leagues+teams+pivot | teamstats: só teamstats | players: só players | coachs: só coachs")
    parser.add_argument("--season", default="auto", help="auto ou ano (ex: 2025)")
    parser.add_argument("--max-back", type=int, default=4, help="fallback de épocas (ex: 4 -> 2025..2021)")
    args = parser.parse_args()

    season_target = latest_season_year() if str(args.season).strip().lower() == "auto" else int(args.season)

    started = datetime.now()
    subject_prefix = f"ScoutTalent Fetch [{args.mode.upper()}] season={season_target}"

    try:
        send_email(
            f"{subject_prefix} START",
            f"Início: {now_str()}\nModo: {args.mode}\nTarget Season: {season_target}\nMaxBack: {args.max_back}\n"
        )

        ensure_schema()

        if args.mode == "full":
            fetch_countries()
            fetch_leagues()
            fetch_all_teams(season_target=season_target)
            fetch_team_statistics(season_target=season_target, max_back=args.max_back)
            fetch_players_for_season(season_target=season_target, max_back=args.max_back)
            fetch_coachs_for_season(season_target=season_target, max_back=args.max_back)
            quick_db_checks(season_target=season_target)

        elif args.mode == "core":
            fetch_leagues()
            fetch_all_teams(season_target=season_target)
            quick_db_checks(season_target=season_target)

        elif args.mode == "teamstats":
            fetch_team_statistics(season_target=season_target, max_back=args.max_back)
            quick_db_checks(season_target=season_target)

        elif args.mode == "players":
            fetch_players_for_season(season_target=season_target, max_back=args.max_back)
            quick_db_checks(season_target=season_target)

        else:
            fetch_coachs_for_season(season_target=season_target, max_back=args.max_back)
            quick_db_checks(season_target=season_target)

        ended = datetime.now()
        elapsed = ended - started

        print("[DONE] concluído", flush=True)
        send_email(
            f"{subject_prefix} OK",
            f"Fim: {now_str()}\nDuração: {elapsed}\nResultado: SUCESSO\n"
        )

    except Exception as e:
        ended = datetime.now()
        elapsed = ended - started
        err_txt = "".join(traceback.format_exception(type(e), e, e.__traceback__))

        print("[ERROR] O script falhou:", flush=True)
        print(err_txt, flush=True)

        send_email(
            f"{subject_prefix} ERROR",
            f"Fim: {now_str()}\nDuração: {elapsed}\nResultado: ERRO\n\n{err_txt}\n"
        )
        raise

    finally:
        try:
            CURSOR.close()
        except:
            pass
        try:
            DB.close()
        except:
            pass
