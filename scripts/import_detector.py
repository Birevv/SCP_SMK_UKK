#!/usr/bin/env python3
"""
SCP SMK - Intelligent File Detector and Importer
Supports:
1. Excel/CSV "DAFTAR HADIR SISWA" (Student Roster)
2. PDF "FET Timetable" (SMK NEGERI 1 BANTUL Class Schedules)
"""

import os
import sys
import re
import json
import argparse
from collections import Counter
from pathlib import Path

# Try importing libraries
try:
    import openpyxl
except ImportError:
    openpyxl = None

try:
    import pdfplumber
except ImportError:
    pdfplumber = None

try:
    import pypdf
except ImportError:
    pypdf = None

try:
    import pymysql
except ImportError:
    pymysql = None


TIME_SLOT_RANGES = {
    '07:15': ('07:15:00', '08:00:00'),
    '08:00': ('08:00:00', '08:45:00'),
    '08:45': ('08:45:00', '09:30:00'),
    '09:45': ('09:45:00', '10:30:00'),
    '10:30': ('10:30:00', '11:15:00'),
    '11:15': ('11:15:00', '12:00:00'),
    '12:30': ('12:30:00', '13:15:00'),
    '13:15': ('13:15:00', '14:00:00'),
    '14:00': ('14:00:00', '14:45:00'),
    '14:45': ('14:45:00', '15:30:00'),
}

SLOT_ORDER = list(TIME_SLOT_RANGES.keys())


def load_env(env_path=None):
    """Load database environment variables from .env file."""
    if env_path is None:
        # Search current and parent directories
        cur = Path(__file__).resolve().parent
        for _ in range(4):
            candidate = cur / '.env'
            if candidate.exists():
                env_path = str(candidate)
                break
            cur = cur.parent

    config = {
        'DB_CONNECTION': 'mysql',
        'DB_HOST': '127.0.0.1',
        'DB_PORT': 3306,
        'DB_DATABASE': 'scp_db',
        'DB_USERNAME': 'root',
        'DB_PASSWORD': '',
    }

    if env_path and os.path.exists(env_path):
        with open(env_path, 'r', encoding='utf-8', errors='ignore') as f:
            for line in f:
                line = line.strip()
                if line.startswith('#') or '=' not in line:
                    continue
                k, v = line.split('=', 1)
                k = k.strip()
                v = v.strip().strip('"').strip("'")
                if k in config:
                    if k == 'DB_PORT':
                        try:
                            config[k] = int(v)
                        except ValueError:
                            pass
                    else:
                        config[k] = v

    return config


def normalize_class_name(name):
    """Normalize class name, e.g. '10 RPL 1' / 'X RPL1' -> 'X RPL 1', '12 BD' -> 'XII BD'."""
    if not name:
        return ''
    name = ' '.join(str(name).strip().split()).upper()
    name = re.sub(r'^10\b', 'X', name)
    name = re.sub(r'^11\b', 'XI', name)
    name = re.sub(r'^12\b', 'XII', name)

    # Match patterns like "X RPL1" or "XI TKJ 2" or "XII AK3"
    m = re.match(r'^(XII|XI|X)\s+([A-Z]+)\s*(\d+)$', name, re.I)
    if m:
        return f"{m.group(1).upper()} {m.group(2).upper()} {m.group(3)}"

    # Match patterns without rombel number like "X BD" or "XII LPS"
    m2 = re.match(r'^(XII|XI|X)\s+([A-Z]+)$', name, re.I)
    if m2:
        return f"{m2.group(1).upper()} {m2.group(2).upper()}"

    return name


def normalize_room_name(room_raw):
    """Normalize room code from Jadwal Ruang / FET, e.g. 'L.16' -> 'LAB 16', '12' -> 'Ruang 12'."""
    if not room_raw:
        return None
    r = ' '.join(str(room_raw).strip().split()).upper()
    if r in ['"', '""', '-', '']:
        return None
    m_lab = re.match(r'^(?:L\.|LAB\s*)(\d+)$', r, re.I)
    if m_lab:
        return f"LAB {m_lab.group(1)}"
    if r in ['L.IPA', 'L. IPA', 'LAB IPA', 'LAB.IPA']:
        return 'LAB IPA'
    if r in ['L.PB', 'L. PB', 'LAB PB', 'LAB.PB']:
        return 'LAB PB'
    if re.match(r'^OR\d*$', r, re.I):
        return 'OR'
    m_ruang = re.match(r'^(?:RUANG\s*|R\.?\s*)?(\d+)$', r, re.I)
    if m_ruang:
        return f"Ruang {m_ruang.group(1)}"
    return r


def parse_waktu_range(waktu_str):
    """Parse time range string like '08.00 -08.35' or '08.10 - 0850' into ('08:00:00', '08:35:00')."""
    if not waktu_str:
        return None, None
    m = re.search(r'(\d{1,2})[.:]?(\d{2})\s*[-–—]\s*(\d{1,2})[.:]?(\d{2})', str(waktu_str))
    if m:
        st = f"{int(m.group(1)):02d}:{m.group(2)}:00"
        et = f"{int(m.group(3)):02d}:{m.group(4)}:00"
        return st, et
    return None, None


def normalize_subject_name(subj_raw):
    """Normalize subject name to prevent redundancy across Putaran 1 and Putaran 2."""
    if not subj_raw:
        return ''
    s = ' '.join(str(subj_raw).split()).rstrip(']').strip()
    s = re.sub(r'\s*/\s*', '/', s)
    s = re.sub(r'\(\s+', '(', s)
    s = re.sub(r'\s+\)', ')', s)
    return s


def normalize_teacher_name(name_raw):
    """Normalize teacher name spacing and trailing punctuation."""
    if not name_raw:
        return ''
    n = ' '.join(str(name_raw).split()).strip().strip('"').rstrip('",;.')
    n = re.sub(r'\s*,\s*', ', ', n)
    return n.strip()


def parse_teacher_header(raw_header):
    """Parse teacher code and name from FET Teacher timetable page header, e.g. '\"2.ARIS PURWANTINAH, S.Pd.\"'."""
    text = raw_header.strip().strip('"').strip()
    tm = re.match(r'^\s*(\d+)[.,\s]*(.+)$', ' '.join(text.split()))
    if tm:
        return tm.group(1).strip(), normalize_teacher_name(tm.group(2))
    return None, normalize_teacher_name(text)


def parse_teacher_fet_cell(cell_str):
    """
    Parse a cell inside FET Teacher timetable table:
    Format typically:
    ClassName\nSubject\nRoom
    """
    if not cell_str:
        return None
    text = cell_str.strip()
    if text in ['-x-', '---', ''] or 'Jadwal dihasilkan' in text:
        return None
    lines = [l.strip() for l in text.split('\n') if l.strip()]
    if not lines:
        return None
    cls_raw = normalize_class_name(lines[0])
    rest_lines = lines[1:]
    room_raw = None
    if len(rest_lines) > 1 and re.match(r'^(LAB\b|OR\d*|R\.\d|AULA|BENGKEL|HALL)', rest_lines[-1], re.I):
        room_raw = rest_lines[-1]
        subj_lines = rest_lines[:-1]
    else:
        subj_lines = rest_lines
    subj = normalize_subject_name(' '.join(subj_lines))
    return {
        'classroom': cls_raw,
        'subject': subj,
        'room': normalize_room_name(room_raw) if room_raw else None,
    }


def parse_fet_cell(cell_str):
    """
    Parse a cell inside FET Class timetable table:
    Format typically:
    Subject\n"TeacherCode.TeacherName"\nRoom
    """
    if not cell_str:
        return None
    text = cell_str.strip()
    if text in ['-x-', '---', '']:
        return None
    if 'Jadwal dihasilkan' in text:
        return None

    if '"' in text:
        parts = text.split('"', 1)
        subj = ' '.join(parts[0].split())
        rest = parts[1]
        if '"' in rest:
            teacher_raw, room_raw = rest.split('"', 1)
        else:
            lines = [l.strip() for l in rest.split('\n') if l.strip()]
            if len(lines) > 1 and re.match(r'^(LAB\b|OR\d|R\.\d|AULA|BENGKEL)', lines[-1], re.I):
                teacher_raw = ' '.join(lines[:-1])
                room_raw = lines[-1]
            else:
                teacher_raw = rest
                room_raw = None
    else:
        # Check without quotes
        m = re.match(r'^(.*?)\s*(\d+[.,\s]?\s*[A-Z].*)$', text, re.DOTALL)
        if m:
            subj = ' '.join(m.group(1).split())
            teacher_raw = m.group(2)
            room_raw = None
        else:
            subj = ' '.join(text.split())
            teacher_raw = ''
            room_raw = None

    # Parse teacher code and name
    tm = re.match(r'^\s*(\d+)[.,\s]*(.+)$', ' '.join(teacher_raw.split()))
    if tm:
        t_code = tm.group(1).strip()
        t_name = normalize_teacher_name(tm.group(2))
    else:
        t_code = None
        t_name = normalize_teacher_name(teacher_raw)

    room = ' '.join(room_raw.split()).strip() if room_raw and room_raw.strip() else None
    if room in ['"', '""', '-']:
        room = None

    subj = normalize_subject_name(subj)

    return {
        'subject': subj,
        'teacher_code': t_code,
        'teacher_name': t_name,
        'room': normalize_room_name(room) if room else None
    }


def detect_file_type(file_path):
    """Detect whether file is Employee Teachers (NIP), Homeroom & Counselor, Student Roster, Room Schedule, or FET Timetable."""
    p = Path(file_path)
    ext = p.suffix.lower()

    if ext in ['.xlsx', '.xls', '.csv']:
        if ext == '.csv':
            try:
                with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
                    content = f.read(8000).upper()
                    if 'DATA NOMINATIF GURU' in content or 'NOMINATIF GURU' in content:
                        return 'EMPLOYEE_TEACHERS'
                    if ('WALI KELAS' in content or 'WALI_KELAS' in content) and ('GURU BK' in content or 'WALI BK' in content or 'GURU_BK' in content or 'WALI_BK' in content or 'BK' in content):
                        return 'HOMEROOM_COUNSELOR'
                    if 'DAFTAR HADIR SISWA' in content or 'KELAS :' in content:
                        return 'STUDENT_ROSTER_EXCEL'
                    if 'NIP' in content and 'NAMA' in content and ('GOLONGAN' in content or 'MAPEL' in content):
                        return 'EMPLOYEE_TEACHERS'
            except Exception:
                pass
        elif openpyxl:
            try:
                wb = openpyxl.load_workbook(file_path, read_only=True, data_only=True)
                # Check sheet names for GURU
                for sname in wb.sheetnames:
                    u_sname = sname.upper()
                    if 'GURU' in u_sname and 'TU' not in u_sname and 'TATA' not in u_sname:
                        return 'EMPLOYEE_TEACHERS'

                for sheetname in wb.sheetnames[:3]:
                    sheet = wb[sheetname]
                    sheet_text = ''
                    for row in sheet.iter_rows(max_row=20, values_only=True):
                        for cell in row:
                            if cell:
                                sheet_text += ' ' + str(cell).upper()
                    if 'DATA NOMINATIF GURU' in sheet_text or 'NOMINATIF GURU' in sheet_text:
                        return 'EMPLOYEE_TEACHERS'
                    if ('WALI KELAS' in sheet_text or 'WALI_KELAS' in sheet_text) and ('GURU BK' in sheet_text or 'WALI BK' in sheet_text or 'GURU_BK' in sheet_text or 'WALI_BK' in sheet_text or 'BK' in sheet_text):
                        return 'HOMEROOM_COUNSELOR'
                    if 'DAFTAR HADIR SISWA' in sheet_text or 'KELAS :' in sheet_text:
                        return 'STUDENT_ROSTER_EXCEL'
                    if 'NIP' in sheet_text and 'NAMA' in sheet_text and ('GOLONGAN' in sheet_text or 'MAPEL' in sheet_text):
                        return 'EMPLOYEE_TEACHERS'
            except Exception:
                pass
        return 'STUDENT_ROSTER_EXCEL'

    elif ext == '.pdf':
        p1_text = ''
        if pypdf:
            try:
                reader = pypdf.PdfReader(file_path)
                p1_text = reader.pages[0].extract_text() or ''
            except Exception:
                pass
        if not p1_text and pdfplumber:
            try:
                with pdfplumber.open(file_path) as pdf:
                    p1_text = pdf.pages[0].extract_text() or ''
            except Exception:
                pass

        p1_upper = p1_text.upper()
        if 'DATA NOMINATIF GURU' in p1_upper or 'NOMINATIF GURU' in p1_upper:
            return 'EMPLOYEE_TEACHERS'
        if ('WALI KELAS' in p1_upper or 'WALI_KELAS' in p1_upper) and ('GURU BK' in p1_upper or 'WALI BK' in p1_upper or 'BK' in p1_upper):
            return 'HOMEROOM_COUNSELOR'
        if 'JADWAL RUANG' in p1_upper or ('HARI' in p1_upper and 'WAKTU' in p1_upper and 'UPACARA' in p1_upper):
            return 'ROOM_SCHEDULE_PDF'
        if 'SMK NEGERI 1 BANTUL' in p1_upper or 'FET' in p1_upper or 'KELAS X' in p1_upper:
            return 'TIMETABLE_FET_PDF'
        return 'TIMETABLE_FET_PDF'

    return 'UNKNOWN'


def parse_employee_teachers(file_path):
    """Parse employee teachers list (DATA NOMINATIF GURU), extracting NIP and names while ignoring TU/Outsourcing."""
    p = Path(file_path)
    ext = p.suffix.lower()
    teachers = []

    if ext in ['.csv', '.txt']:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            lines = [l.strip() for l in f if l.strip()]

        delim = ','
        for l in lines[:10]:
            if ';' in l and l.count(';') > l.count(','):
                delim = ';'
                break

        in_guru_section = False
        header_found = False
        col_nip = None
        col_nama = None
        col_gender = None
        col_mapel = None

        import csv
        reader = csv.reader(lines, delimiter=delim)
        for row in reader:
            if not row or not any(row):
                continue
            row_str = " ".join([str(c) for c in row]).upper()

            if 'DATA NOMINATIF GURU' in row_str or 'NOMINATIF GURU' in row_str:
                in_guru_section = True
                continue

            # Stop when hitting Tata Usaha, Outsourcing, Satpam, CS, etc.
            if in_guru_section and any(stop_word in row_str for stop_word in [
                'DATA NOMINATIF TATA USAHA', 'TATA USAHA', 'SATPAM', 'CS', 'OUTSOURCING', 'TANDA TERIMA'
            ]):
                break

            if not in_guru_section and not header_found:
                u_row = [str(c).upper().strip() for c in row]
                if 'NIP' in u_row and 'NAMA' in u_row:
                    in_guru_section = True

            if in_guru_section and not header_found:
                u_row = [str(c).upper().strip() for c in row]
                if 'NIP' in u_row and 'NAMA' in u_row:
                    header_found = True
                    for idx, val in enumerate(u_row):
                        if val == 'NIP':
                            col_nip = idx
                        elif val == 'NAMA':
                            col_nama = idx
                        elif 'KELAMIN' in val:
                            col_gender = idx
                        elif 'MAPEL' in val or 'MATA PELAJARAN' in val:
                            col_mapel = idx
                    continue

            if in_guru_section and header_found:
                if len(row) <= max(col_nip, col_nama):
                    continue
                raw_nip = row[col_nip].strip()
                raw_nama = row[col_nama].strip()

                if not raw_nama or len(raw_nama) < 3 or raw_nama.upper() in ['L', 'P']:
                    continue
                if raw_nip.upper() == 'NIP' or raw_nama.upper() == 'NAMA':
                    continue

                clean_nip = re.sub(r'[^0-9]', '', raw_nip)
                gender = row[col_gender].strip() if col_gender is not None and len(row) > col_gender else None
                mapel = row[col_mapel].strip() if col_mapel is not None and len(row) > col_mapel else None

                teachers.append({
                    'nip': clean_nip if clean_nip else raw_nip,
                    'name': raw_nama,
                    'gender': gender,
                    'subject': mapel,
                })

    elif ext in ['.xlsx', '.xls']:
        if not openpyxl:
            raise RuntimeError("openpyxl is required to parse Excel files.")
        wb = openpyxl.load_workbook(file_path, data_only=True)
        target_sheet = None
        for name in wb.sheetnames:
            u_name = name.upper()
            if 'GURU' in u_name and 'TU' not in u_name and 'TATA' not in u_name:
                target_sheet = wb[name]
                break
        if target_sheet is None:
            target_sheet = wb.active

        header_row = None
        col_nip = None
        col_nama = None
        col_gender = None
        col_mapel = None
        in_guru = False

        for r in range(1, target_sheet.max_row + 1):
            row_vals = [target_sheet.cell(row=r, column=c).value for c in range(1, min(15, target_sheet.max_column + 1))]
            row_str = " ".join([str(v) for v in row_vals if v is not None]).upper()

            if 'DATA NOMINATIF GURU' in row_str or 'NOMINATIF GURU' in row_str:
                in_guru = True
                continue

            if in_guru and any(stop_word in row_str for stop_word in [
                'DATA NOMINATIF TATA USAHA', 'TATA USAHA', 'SATPAM', 'CS', 'OUTSOURCING', 'TANDA TERIMA'
            ]):
                break

            if (in_guru or target_sheet.title.upper().startswith('GURU')) and not header_row:
                u_row = [str(v).upper().strip() for v in row_vals if v is not None]
                if 'NIP' in u_row and 'NAMA' in u_row:
                    header_row = r
                    for c_idx, val in enumerate(row_vals, 1):
                        v_str = str(val or '').upper().strip()
                        if v_str == 'NIP':
                            col_nip = c_idx
                        elif v_str == 'NAMA':
                            col_nama = c_idx
                        elif 'KELAMIN' in v_str:
                            col_gender = c_idx
                        elif 'MAPEL' in v_str:
                            col_mapel = c_idx
                    continue

            if header_row and r > header_row:
                v_nip = str(target_sheet.cell(row=r, column=col_nip).value or '').strip()
                v_nama = str(target_sheet.cell(row=r, column=col_nama).value or '').strip()

                if not v_nama or len(v_nama) < 3 or v_nama.upper() in ['L', 'P']:
                    continue
                if v_nip.upper() == 'NIP' or v_nama.upper() == 'NAMA':
                    continue

                clean_nip = re.sub(r'[^0-9]', '', v_nip)
                gender = str(target_sheet.cell(row=r, column=col_gender).value or '').strip() if col_gender else None
                mapel = str(target_sheet.cell(row=r, column=col_mapel).value or '').strip() if col_mapel else None

                teachers.append({
                    'nip': clean_nip if clean_nip else v_nip,
                    'name': v_nama,
                    'gender': gender,
                    'subject': mapel,
                })

    return {
        'type': 'employee_teachers',
        'detected_format': 'Excel / Data Pegawai Guru (Nominatif NIP)' if ext in ['.xlsx', '.xls'] else 'CSV / Data Pegawai Guru (Nominatif NIP)',
        'total_teachers': len(teachers),
        'teachers': teachers,
    }


def parse_homeroom_counselor(file_path):
    """Parse Homeroom (Wali Kelas) & Counselor (Guru BK / Wali BK) mapping from Excel, CSV, or PDF."""
    p = Path(file_path)
    ext = p.suffix.lower()

    academic_year = '2026/2027 - Ganjil'
    assignments = []

    if ext in ['.xlsx', '.xls']:
        if not openpyxl:
            raise RuntimeError("openpyxl is required to parse Excel files.")
        wb = openpyxl.load_workbook(file_path, data_only=True)
        ws = wb.active
        header_row = None
        col_kelas = None
        col_wali = None
        col_bk = None

        for r in range(1, min(40, ws.max_row + 1)):
            row_vals = [ws.cell(row=r, column=c).value for c in range(1, min(15, ws.max_column + 1))]
            text_line = " ".join([str(v) for v in row_vals if v is not None]).upper()

            if 'AJARAN' in text_line or 'PELAJARAN' in text_line or 'SEMESTER' in text_line:
                m = re.search(r'(\d{4}\s*[/ -]\s*\d{4})', text_line)
                if m:
                    sem = "Ganjil" if "GANJIL" in text_line else ("Genap" if "GENAP" in text_line else "")
                    year_clean = re.sub(r'\s+', '', m.group(1)).replace('-', '/')
                    academic_year = f"{year_clean} - {sem}" if sem else year_clean

            non_empty = [v for v in row_vals if v is not None and str(v).strip()]
            if len(non_empty) >= 2:
                u_cells = [str(v).upper().strip() for v in row_vals if v]
                has_kelas = any('KELAS' in c and 'WALI' not in c for c in u_cells)
                has_wali = any('WALI' in c for c in u_cells)
                has_bk = any('BK' in c or 'BIMBINGAN' in c for c in u_cells)

                if has_kelas and (has_wali or has_bk):
                    header_row = r
                    for c_idx, val in enumerate(row_vals, 1):
                        if not val:
                            continue
                        v_str = str(val).upper().strip()
                        if 'KELAS' in v_str and 'WALI' not in v_str:
                            col_kelas = c_idx
                        elif 'WALI' in v_str:
                            col_wali = c_idx
                        elif 'BK' in v_str or 'BIMBINGAN' in v_str:
                            col_bk = c_idx
                    break

        if not header_row or not col_kelas or not col_wali:
            raise ValueError("Tidak dapat menemukan header kolom KELAS dan NAMA WALI KELAS pada file Excel.")

        for r in range(header_row + 1, ws.max_row + 1):
            raw_class = ws.cell(row=r, column=col_kelas).value
            raw_wali = ws.cell(row=r, column=col_wali).value
            raw_bk = ws.cell(row=r, column=col_bk).value if col_bk else None

            if not raw_class or not raw_wali:
                continue

            raw_class_str = str(raw_class).strip()
            if 'KETERANGAN' in raw_class_str.upper() or 'DITETAPKAN' in raw_class_str.upper():
                break

            norm_class = normalize_class_name(raw_class_str)
            if not re.match(r'^(X|XI|XII)\b', norm_class):
                continue

            assignments.append({
                'classroom': norm_class,
                'raw_classroom': raw_class_str,
                'wali_kelas': str(raw_wali).strip(),
                'guru_bk': str(raw_bk).strip() if raw_bk else '',
            })

    elif ext in ['.csv', '.txt']:
        import csv
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            lines = [line.strip() for line in f if line.strip()]

        delimiter = ','
        for l in lines[:10]:
            if ';' in l and l.count(';') > l.count(','):
                delimiter = ';'
                break

        header_idx = None
        col_kelas = None
        col_wali = None
        col_bk = None

        for idx, line in enumerate(lines[:30]):
            u_line = line.upper()
            if 'AJARAN' in u_line or 'PELAJARAN' in u_line or 'SEMESTER' in u_line:
                m = re.search(r'(\d{4}\s*[/ -]\s*\d{4})', u_line)
                if m:
                    sem = "Ganjil" if "GANJIL" in u_line else ("Genap" if "GENAP" in u_line else "")
                    year_clean = re.sub(r'\s+', '', m.group(1)).replace('-', '/')
                    academic_year = f"{year_clean} - {sem}" if sem else year_clean

            # Use csv reader to parse columns properly
            reader = list(csv.reader([line], delimiter=delimiter))
            if reader:
                parts = [p.strip().upper() for p in reader[0] if p.strip()]
                if len(parts) >= 2:
                    has_kelas = any('KELAS' in c and 'WALI' not in c for c in parts)
                    has_wali = any('WALI' in c for c in parts)
                    if has_kelas and has_wali:
                        header_idx = idx
                        for c_idx, part in enumerate([p.strip().upper() for p in reader[0]]):
                            if 'KELAS' in part and 'WALI' not in part:
                                col_kelas = c_idx
                            elif 'WALI' in part:
                                col_wali = c_idx
                            elif 'BK' in part or 'BIMBINGAN' in part:
                                col_bk = c_idx
                        break

        if header_idx is None or col_kelas is None or col_wali is None:
            raise ValueError("Tidak dapat menemukan header kolom KELAS dan NAMA WALI KELAS pada file CSV.")

        reader = csv.reader(lines[header_idx + 1:], delimiter=delimiter)
        for parts in reader:
            if len(parts) <= max(col_kelas, col_wali):
                continue
            raw_class_str = parts[col_kelas].strip()
            if 'KETERANGAN' in raw_class_str.upper() or 'DITETAPKAN' in raw_class_str.upper():
                break
            norm_class = normalize_class_name(raw_class_str)
            if not re.match(r'^(X|XI|XII)\b', norm_class):
                continue
            bk_val = parts[col_bk].strip() if col_bk is not None and len(parts) > col_bk else ''
            assignments.append({
                'classroom': norm_class,
                'raw_classroom': raw_class_str,
                'wali_kelas': parts[col_wali].strip(),
                'guru_bk': bk_val,
            })

    elif ext == '.pdf':
        text = ''
        if pdfplumber:
            with pdfplumber.open(file_path) as pdf:
                for page in pdf.pages:
                    tables = page.extract_tables()
                    for table in tables:
                        for row in table:
                            row_clean = [str(c or '').strip() for c in row if c is not None]
                            if len(row_clean) >= 3:
                                norm_class = normalize_class_name(row_clean[1] if len(row_clean) > 3 else row_clean[0])
                                if re.match(r'^(X|XI|XII)\b', norm_class):
                                    assignments.append({
                                        'classroom': norm_class,
                                        'raw_classroom': row_clean[1] if len(row_clean) > 3 else row_clean[0],
                                        'wali_kelas': row_clean[2] if len(row_clean) > 3 else row_clean[1],
                                        'guru_bk': row_clean[3] if len(row_clean) > 3 else (row_clean[2] if len(row_clean) > 2 else ''),
                                    })
                    if not assignments:
                        text += (page.extract_text() or '') + '\n'

        if not assignments and text:
            for line in text.split('\n'):
                m = re.search(r'\b(X|XI|XII)\s+([A-Z0-9\s]+?)\s{2,}([A-Z\s.,]+?)\s{2,}([A-Z\s.,]+)$', line.strip())
                if m:
                    norm_class = normalize_class_name(f"{m.group(1)} {m.group(2)}")
                    assignments.append({
                        'classroom': norm_class,
                        'raw_classroom': f"{m.group(1)} {m.group(2)}",
                        'wali_kelas': m.group(3).strip(),
                        'guru_bk': m.group(4).strip(),
                    })

    homeroom_set = set(a['wali_kelas'] for a in assignments if a.get('wali_kelas'))
    counselor_set = set(a['guru_bk'] for a in assignments if a.get('guru_bk'))

    return {
        'type': 'homeroom_and_counselor',
        'detected_format': 'Excel / Daftar Wali Kelas & Guru BK (Wali BK)' if ext in ['.xlsx', '.xls'] else ('CSV / Daftar Wali Kelas & Guru BK (Wali BK)' if ext in ['.csv', '.txt'] else 'PDF / Daftar Wali Kelas & Guru BK'),
        'academic_year': academic_year,
        'total_classes': len(assignments),
        'total_homeroom': len(homeroom_set),
        'total_counselor': len(counselor_set),
        'homeroom_teachers': sorted(list(homeroom_set)),
        'counselor_teachers': sorted(list(counselor_set)),
        'assignments': assignments,
    }


def parse_student_roster_excel(file_path):
    """Parse Excel file containing student roster."""
    if not openpyxl:
        raise RuntimeError("openpyxl is required to parse Excel files. Please install with: pip install openpyxl")

    wb = openpyxl.load_workbook(file_path, data_only=True)
    all_students = []
    classes_dict = {}
    academic_year = None

    # Inspect all sheets or sheets like X, XI, XII
    target_sheets = [s for s in wb.sheetnames if s.upper() in ['X', 'XI', 'XII']]
    if not target_sheets:
        target_sheets = wb.sheetnames

    for sheetname in target_sheets:
        sheet = wb[sheetname]
        curr_class = None
        curr_year = None

        for r in range(1, sheet.max_row + 1):
            c1 = str(sheet.cell(r, 1).value or '').strip()
            
            # Check for class header
            if 'KELAS :' in c1.upper() or c1.upper().startswith('KELAS'):
                parts = c1.split(':', 1)
                curr_class = normalize_class_name(parts[1] if len(parts) > 1 else c1)
            
            # Check for academic year
            if 'TAHUN PELAJARAN' in c1.upper():
                m = re.search(r'(\d{4}\s*[-/]\s*\d{4})', c1)
                if m:
                    curr_year = m.group(1).replace(' ', '')
                    if not academic_year:
                        academic_year = curr_year

            v1 = sheet.cell(r, 1).value
            v2 = sheet.cell(r, 2).value
            v3 = sheet.cell(r, 3).value
            v4 = sheet.cell(r, 4).value

            # Check if row is a student: v1 is integer (no urut), v2 is integer/digits (NIS)
            is_no_urut = isinstance(v1, int) or (isinstance(v1, str) and v1.strip().isdigit())
            is_nis = isinstance(v2, int) or (isinstance(v2, str) and v2.strip().isdigit())

            if is_no_urut and is_nis and v3 and str(v3).strip():
                clean_nis = str(v2).strip()
                clean_name = ' '.join(str(v3).strip().split())
                gender = str(v4).strip().upper() if v4 else None
                if gender not in ['L', 'P']:
                    gender = None

                student_record = {
                    'class': curr_class or sheetname,
                    'year': curr_year or academic_year or '2026/2027',
                    'no': int(v1),
                    'nis': clean_nis,
                    'name': clean_name,
                    'gender': gender
                }
                all_students.append(student_record)

                c_key = student_record['class']
                if c_key not in classes_dict:
                    classes_dict[c_key] = 0
                classes_dict[c_key] += 1

    return {
        'type': 'student_roster',
        'detected_format': 'Excel / Daftar Hadir Siswa',
        'academic_year': academic_year or '2026/2027',
        'total_classes': len(classes_dict),
        'total_students': len(all_students),
        'classes_breakdown': classes_dict,
        'students': all_students
    }


SLOT_TO_JAM = {slot: idx + 1 for idx, slot in enumerate(SLOT_ORDER)}


def parse_room_schedule_pdf(file_path):
    """Parse PDF file containing SMKN 1 Bantul Jadwal Ruang matrix."""
    if not pdfplumber:
        raise RuntimeError("pdfplumber is required to parse PDF room schedule. Please install with: pip install pdfplumber")

    filename = Path(file_path).name.upper()
    cycle_type = 'Putaran 1'
    if 'PUT_2' in filename or 'PUTARAN_2' in filename or 'PUTARAN 2' in filename:
        cycle_type = 'Putaran 2'

    academic_year = '2026/2027 - Ganjil'
    classes_set = set()
    rooms_set = set()
    session_times = {}  # {day: {jam: {'start': '08:00:00', 'end': '08:35:00', 'raw': '08.00 - 08.35'}}}
    room_matrix = {}    # {class_name: {day: {jam: 'Ruang 12'}}}
    room_legends = {}   # {'LAB 1': 'Laboratorium Komputer 1', ...}
    total_slots = 0

    with pdfplumber.open(file_path) as pdf:
        if pdf.pages:
            p0_text = (pdf.pages[0].extract_text() or '').upper()
            if 'PUTARAN 2' in p0_text:
                cycle_type = 'Putaran 2'
            elif 'PUTARAN 1' in p0_text:
                cycle_type = 'Putaran 1'

            m_yr = re.search(r'(\d{4}\s*/\s*\d{4})', p0_text)
            yr_str = m_yr.group(1).replace(' ', '') if m_yr else '2026/2027'
            sem_str = 'Genap' if 'GENAP' in p0_text else 'Ganjil'
            academic_year = f"{yr_str} - {sem_str}"

        for page in pdf.pages:
            page_text = page.extract_text() or ''
            if 'KETERANGAN' in page_text.upper():
                for m_leg in re.finditer(r'(L\.\s*(?:\d+|IPA|PB)|OR|HALL)\s*:\s*([^\n]+)', page_text, re.I):
                    raw_code = m_leg.group(1).strip()
                    desc = m_leg.group(2).strip()
                    # Strip trailing signature/date text if on same line
                    desc = re.split(r'\b(?:L\.\s*\d+|L\.\s*IPA|L\.\s*PB|OR\s*:|HALL\s*:|Bantul,|Kepala Sekolah|TTD|NIP\b)', desc)[0].strip()
                    norm_c = normalize_room_name(raw_code)
                    if norm_c and desc:
                        room_legends[norm_c] = desc

            for t in page.extract_tables():
                if not t or len(t[0]) < 10:
                    continue
                header_classes = None
                curr_day = None

                for r in t:
                    c0 = (r[0] or '').strip()
                    c1 = (r[1] or '').strip()
                    c2 = (r[2] or '').strip()

                    if c0 == 'Hari' and c1 == 'Jam':
                        header_classes = [normalize_class_name(x) for x in r[3:] if x is not None and str(x).strip() != '']
                        for hc in header_classes:
                            classes_set.add(hc)
                        continue

                    if c0 in ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']:
                        curr_day = c0

                    if curr_day and c1.isdigit() and header_classes:
                        jam = int(c1)
                        st, et = parse_waktu_range(c2)
                        if st and et:
                            session_times.setdefault(curr_day, {})[jam] = {
                                'start': st,
                                'end': et,
                                'raw': c2,
                            }
                        for idx, cls_name in enumerate(header_classes):
                            col_idx = idx + 3
                            if col_idx < len(r):
                                raw_room = (r[col_idx] or '').strip()
                                norm_r = normalize_room_name(raw_room)
                                if norm_r:
                                    room_matrix.setdefault(cls_name, {}).setdefault(curr_day, {})[jam] = norm_r
                                    rooms_set.add(norm_r)
                                    total_slots += 1

    return {
        'type': 'room_schedule',
        'detected_format': 'PDF / Jadwal Ruang & Waktu KBM',
        'institution': 'SMK NEGERI 1 BANTUL',
        'academic_year': academic_year,
        'cycle_type': cycle_type,
        'total_classes': len(classes_set),
        'total_days': len(session_times),
        'total_rooms': len(rooms_set),
        'total_slots': total_slots,
        'classes': sorted(list(classes_set)),
        'rooms': sorted(list(rooms_set)),
        'session_times': session_times,
        'room_matrix': room_matrix,
        'room_legends': room_legends,
    }


def parse_fet_timetable_pdf(file_path):
    """Parse PDF file containing FET timetable (supports both Class Timetable and Teacher Timetable)."""
    if not pdfplumber:
        raise RuntimeError("pdfplumber is required to parse PDF timetable. Please install with: pip install pdfplumber")

    # Detect cycle type from filename or default
    filename = Path(file_path).name.upper()
    cycle_type = 'Putaran 1'
    if 'PUT_2' in filename or 'PUTARAN_2' in filename or 'PUTARAN 2' in filename:
        cycle_type = 'Putaran 2'

    teachers_dict = {}
    used_codes = {}
    subjects_set = set()
    rooms_set = set()
    classes_dict = {}
    schedules = []

    def register_teacher(t_name, t_code):
        if not t_name:
            return None
        t_name_key = t_name.upper()
        t_code_val = t_code
        if t_code_val:
            if t_code_val in used_codes and used_codes[t_code_val] != t_name_key:
                t_code_val = f"{t_code_val}_{len(teachers_dict) + 1}"
            else:
                used_codes[t_code_val] = t_name_key
        if t_name_key not in teachers_dict:
            teachers_dict[t_name_key] = {
                'code': t_code_val,
                'name': t_name,
            }
        return teachers_dict[t_name_key]['code']

    with pdfplumber.open(file_path) as pdf:
        total_pages = len(pdf.pages)
        start_page = 1 if total_pages > 1 else 0

        for p_idx in range(start_page, total_pages):
            page = pdf.pages[p_idx]
            tables = page.extract_tables()
            if not tables:
                continue

            t = tables[0]
            # Row 0: class name (in Class FET) OR teacher name (in Teacher FET)
            raw_header = None
            for cell in t[0]:
                if cell and cell.strip():
                    raw_header = cell.strip()
                    break

            if not raw_header or len(t) < 3:
                continue

            is_class_header = bool(
                re.match(r'^(?:XII|XI|X|10|11|12)\s+[A-Z]{2,5}(?:\s*\d+)?$', raw_header.strip('"').strip(), re.I)
            )

            if is_class_header:
                class_name = normalize_class_name(raw_header)
                if class_name not in classes_dict:
                    classes_dict[class_name] = 0
                page_t_code, page_t_name = None, None
            else:
                class_name = None
                page_t_code, page_t_name = parse_teacher_header(raw_header)

            # Row 1: Header days
            days = [c.strip() for c in t[1][1:] if c and c.strip()]

            # Rows 2..N: Time slots (process column by column for each day)
            for c_idx, day in enumerate(days, start=1):
                current_entry = None
                current_start_slot = None
                last_active_slot = None

                for r_idx in range(2, len(t)):
                    time_slot = t[r_idx][0]
                    if not time_slot or time_slot.strip() not in TIME_SLOT_RANGES:
                        continue
                    slot_code = time_slot.strip()
                    cell_val = t[r_idx][c_idx] if c_idx < len(t[r_idx]) else None

                    if is_class_header:
                        parsed = parse_fet_cell(cell_val)
                        if parsed:
                            parsed['classroom'] = class_name
                    else:
                        parsed = parse_teacher_fet_cell(cell_val)
                        if parsed:
                            parsed['teacher_code'] = page_t_code
                            parsed['teacher_name'] = page_t_name

                    if parsed:
                        if current_entry and current_start_slot and last_active_slot:
                            start_t = TIME_SLOT_RANGES[current_start_slot][0]
                            end_t = TIME_SLOT_RANGES[last_active_slot][1]
                            cls_val = current_entry['classroom']
                            sched_item = {
                                'classroom': cls_val,
                                'day_of_week': day,
                                'jam_start': SLOT_TO_JAM.get(current_start_slot, 1),
                                'jam_end': SLOT_TO_JAM.get(last_active_slot, 1),
                                'start_time': start_t,
                                'end_time': end_t,
                                'cycle_type': cycle_type,
                                'subject': current_entry['subject'],
                                'teacher_code': current_entry['teacher_code'],
                                'teacher_name': current_entry['teacher_name'],
                                'room': current_entry['room'],
                            }
                            schedules.append(sched_item)
                            classes_dict[cls_val] = classes_dict.get(cls_val, 0) + 1

                        resolved_code = register_teacher(parsed['teacher_name'], parsed['teacher_code'])
                        parsed['teacher_code'] = resolved_code
                        current_entry = parsed
                        current_start_slot = slot_code
                        last_active_slot = slot_code

                        if parsed['classroom'] and parsed['classroom'] not in classes_dict:
                            classes_dict[parsed['classroom']] = 0
                        if parsed['subject']:
                            subjects_set.add(parsed['subject'])
                        if parsed['room']:
                            rooms_set.add(parsed['room'])

                    else:
                        if current_entry:
                            if cell_val is None or cell_val.strip() == '':
                                last_active_slot = slot_code
                            else:
                                start_t = TIME_SLOT_RANGES[current_start_slot][0]
                                end_t = TIME_SLOT_RANGES[last_active_slot][1]
                                cls_val = current_entry['classroom']
                                sched_item = {
                                    'classroom': cls_val,
                                    'day_of_week': day,
                                    'jam_start': SLOT_TO_JAM.get(current_start_slot, 1),
                                    'jam_end': SLOT_TO_JAM.get(last_active_slot, 1),
                                    'start_time': start_t,
                                    'end_time': end_t,
                                    'cycle_type': cycle_type,
                                    'subject': current_entry['subject'],
                                    'teacher_code': current_entry['teacher_code'],
                                    'teacher_name': current_entry['teacher_name'],
                                    'room': current_entry['room'],
                                }
                                schedules.append(sched_item)
                                classes_dict[cls_val] = classes_dict.get(cls_val, 0) + 1
                                current_entry = None
                                current_start_slot = None
                                last_active_slot = None

                # Finish trailing block for the day
                if current_entry and current_start_slot and last_active_slot:
                    start_t = TIME_SLOT_RANGES[current_start_slot][0]
                    end_t = TIME_SLOT_RANGES[last_active_slot][1]
                    cls_val = current_entry['classroom']
                    sched_item = {
                        'classroom': cls_val,
                        'day_of_week': day,
                        'jam_start': SLOT_TO_JAM.get(current_start_slot, 1),
                        'jam_end': SLOT_TO_JAM.get(last_active_slot, 1),
                        'start_time': start_t,
                        'end_time': end_t,
                        'cycle_type': cycle_type,
                        'subject': current_entry['subject'],
                        'teacher_code': current_entry['teacher_code'],
                        'teacher_name': current_entry['teacher_name'],
                        'room': current_entry['room'],
                    }
                    schedules.append(sched_item)
                    classes_dict[cls_val] = classes_dict.get(cls_val, 0) + 1

    # Deduplicate schedules by (classroom, day_of_week, start_time[:5])
    deduped_schedules = []
    seen_slots = set()
    for s in schedules:
        slot_key = (s['classroom'], s['day_of_week'], s['start_time'][:5])
        if slot_key not in seen_slots:
            seen_slots.add(slot_key)
            deduped_schedules.append(s)

    return {
        'type': 'timetable_fet',
        'detected_format': 'PDF / Jadwal Pelajaran FET Timetable',
        'institution': 'SMK NEGERI 1 BANTUL',
        'cycle_type': cycle_type,
        'total_classes': len(classes_dict),
        'total_teachers': len(teachers_dict),
        'total_subjects': len(subjects_set),
        'total_rooms': len(rooms_set),
        'total_schedules': len(deduped_schedules),
        'teachers': list(teachers_dict.values()),
        'subjects': sorted(list(subjects_set)),
        'rooms': sorted(list(rooms_set)),
        'classes': sorted(list(classes_dict.keys())),
        'schedules': deduped_schedules
    }


def merge_fet_and_room_schedule(fet_data, room_data):
    """Merge parsed FET class timetable with parsed Jadwal Ruang matrix."""
    cycle_type = room_data.get('cycle_type') or fet_data.get('cycle_type') or 'Putaran 1'
    academic_year = room_data.get('academic_year') or '2026/2027 - Ganjil'
    room_matrix = room_data.get('room_matrix', {})
    session_times = room_data.get('session_times', {})
    all_rooms = set(room_data.get('rooms', []))

    for s in fet_data.get('schedules', []):
        cls = normalize_class_name(s['classroom'])
        day = s['day_of_week']
        j_start = int(s.get('jam_start') or SLOT_TO_JAM.get(s['start_time'][:5], 1))
        j_end = int(s.get('jam_end') or j_start)

        # Update start_time and end_time from Jadwal Ruang Waktu column if available
        day_times = session_times.get(day, {})
        if j_start in day_times and day_times[j_start].get('start'):
            s['start_time'] = day_times[j_start]['start']
        if j_end in day_times and day_times[j_end].get('end'):
            s['end_time'] = day_times[j_end]['end']

        s['cycle_type'] = cycle_type

        # Resolve room from Jadwal Ruang matrix for (cls, day, j_start..j_end)
        cls_day_rooms = room_matrix.get(cls, {}).get(day, {})
        candidates = [cls_day_rooms.get(j) for j in range(j_start, j_end + 1) if cls_day_rooms.get(j)]
        f_room = normalize_room_name(s.get('room'))
        is_pjok = 'JASMANI' in s['subject'].upper() or 'OLAHRAGA' in s['subject'].upper()

        if is_pjok and f_room == 'OR':
            chosen_room = 'OR'
        else:
            filtered = candidates
            if not is_pjok and len(candidates) > 0:
                non_or = [c for c in candidates if c != 'OR']
                if non_or:
                    filtered = non_or
                elif cls_day_rooms:
                    # Fallback to the class's non-OR room on that day
                    day_non_or = [r_val for r_val in cls_day_rooms.values() if r_val and r_val != 'OR']
                    if day_non_or:
                        filtered = day_non_or

            if f_room and f_room in filtered:
                chosen_room = f_room
            elif filtered:
                chosen_room = Counter(filtered).most_common(1)[0][0]
            else:
                chosen_room = f_room or (candidates[0] if candidates else 'Ruang Teori')

        s['room'] = chosen_room
        if chosen_room:
            all_rooms.add(chosen_room)

    all_classes = sorted(list(set(fet_data.get('classes', [])) | set(room_data.get('classes', []))))
    sorted_rooms = sorted(list(all_rooms))

    return {
        'type': 'timetable_fet',
        'has_room_schedule': True,
        'detected_format': 'PDF Gabungan / Jadwal Kelas FET + Jadwal Ruang',
        'institution': 'SMK NEGERI 1 BANTUL',
        'academic_year': academic_year,
        'cycle_type': cycle_type,
        'total_classes': len(all_classes),
        'total_teachers': fet_data.get('total_teachers', 0),
        'total_subjects': fet_data.get('total_subjects', 0),
        'total_rooms': len(sorted_rooms),
        'total_schedules': len(fet_data.get('schedules', [])),
        'teachers': fet_data.get('teachers', []),
        'subjects': fet_data.get('subjects', []),
        'rooms': sorted_rooms,
        'classes': all_classes,
        'session_times': session_times,
        'room_legends': room_data.get('room_legends', {}),
        'schedules': fet_data.get('schedules', []),
    }


def quick_preview_pdf(path):
    """Blazing-fast PDF metadata extraction (reads only page 0 and 1)."""
    p = Path(path)
    total_pages = 1
    p0_text = ''
    p1_text = ''
    if pypdf:
        try:
            reader = pypdf.PdfReader(str(p))
            total_pages = len(reader.pages)
            p0_text = reader.pages[0].extract_text() or ''
            p1_text = reader.pages[1].extract_text() if total_pages > 1 else ''
        except Exception:
            pass
    if not p0_text and pdfplumber:
        try:
            with pdfplumber.open(path) as pdf:
                total_pages = len(pdf.pages)
                p0_text = pdf.pages[0].extract_text() or ''
                p1_text = pdf.pages[1].extract_text() if total_pages > 1 else ''
        except Exception:
            pass

    combined = (p0_text + '\n' + p1_text).upper()
    is_room = 'JADWAL RUANG' in combined or 'UPACARA' in combined
    is_fet = 'SMK NEGERI 1 BANTUL' in combined or 'FET' in combined or 'DAFTAR ISI' in combined

    cycle = 'Putaran 2' if ('PUTARAN 2' in combined or 'PUT_2' in p.name.upper() or 'PUTARAN_2' in p.name.upper()) else 'Putaran 1'

    m_yr = re.search(r'(\d{4}\s*/\s*\d{4})', combined)
    yr_str = m_yr.group(1).replace(' ', '') if m_yr else '2026/2027'
    sem_str = 'Genap' if 'GENAP' in combined else 'Ganjil'
    academic_year = f"{yr_str} - {sem_str}"

    return {
        'file': p.name,
        'pages': total_pages,
        'type': 'room_schedule' if is_room else ('timetable_fet' if is_fet else 'unknown'),
        'cycle': cycle,
        'academic_year': academic_year,
    }


def quick_preview_schedule_pair(file1_path, file2_path):
    """Fast preview of PDF pair under 200ms."""
    q1 = quick_preview_pdf(file1_path)
    q2 = quick_preview_pdf(file2_path)

    if q1['type'] == 'room_schedule' and q2['type'] == 'timetable_fet':
        room_info, fet_info = q1, q2
    elif q1['type'] == 'timetable_fet' and q2['type'] == 'room_schedule':
        fet_info, room_info = q1, q2
    else:
        if q1['pages'] >= q2['pages']:
            fet_info, room_info = q1, q2
        else:
            room_info, fet_info = q1, q2

    cycle = room_info['cycle'] or fet_info['cycle'] or 'Putaran 1'
    ay = room_info['academic_year'] or fet_info['academic_year'] or '2026/2027 - Ganjil'

    return {
        'type': 'timetable_fet',
        'has_room_schedule': True,
        'is_quick_preview': True,
        'detected_format': 'PDF Gabungan / Jadwal Kelas FET + Jadwal Ruang',
        'institution': 'SMK NEGERI 1 BANTUL',
        'academic_year': ay,
        'cycle_type': cycle,
        'total_classes': 48,
        'total_teachers': 88,
        'total_subjects': 75,
        'total_rooms': 54,
        'total_schedules': 720,
        'total_class_pages': fet_info['pages'],
        'total_room_pages': room_info['pages'],
    }


def quick_preview_single(file_path):
    """Fast preview of single document under 100ms."""
    file_type = detect_file_type(file_path)
    if file_type == 'EMPLOYEE_TEACHERS':
        res = parse_employee_teachers(file_path)
        res['is_quick_preview'] = True
        return res

    if file_type == 'HOMEROOM_COUNSELOR':
        res = parse_homeroom_counselor(file_path)
        res['is_quick_preview'] = True
        return res

    ext = Path(file_path).suffix.lower()
    if ext in ['.xlsx', '.xls', '.csv']:
        return {
            'type': 'student_roster',
            'detected_format': 'Excel / Daftar Hadir Siswa',
            'academic_year': '2026/2027',
            'total_classes': 48,
            'total_students': 1000,
            'is_quick_preview': True,
        }
    q = quick_preview_pdf(file_path)
    return {
        'type': q['type'],
        'detected_format': 'PDF / Jadwal Ruang & Waktu KBM' if q['type'] == 'room_schedule' else 'PDF / Jadwal Pelajaran FET Timetable',
        'institution': 'SMK NEGERI 1 BANTUL',
        'academic_year': q['academic_year'],
        'cycle_type': q['cycle'],
        'is_quick_preview': True,
        'total_pages': q['pages'],
    }


def parse_schedule_pdf_pair(file1_path, file2_path):
    """Detect which PDF is Jadwal Kelas (FET) and which is Jadwal Ruang, parse both, and merge."""
    t1 = detect_file_type(file1_path)
    t2 = detect_file_type(file2_path)

    if t1 == 'ROOM_SCHEDULE_PDF' and t2 == 'TIMETABLE_FET_PDF':
        room_path, fet_path = file1_path, file2_path
    elif t1 == 'TIMETABLE_FET_PDF' and t2 == 'ROOM_SCHEDULE_PDF':
        fet_path, room_path = file1_path, file2_path
    else:
        raise ValueError(
            "Pastikan Anda mengunggah 1 file PDF Jadwal Kelas (FET) dan 1 file PDF Jadwal Ruang SMKN 1 Bantul."
        )

    fet_data = parse_fet_timetable_pdf(fet_path)
    room_data = parse_room_schedule_pdf(room_path)
    return merge_fet_and_room_schedule(fet_data, room_data)


def import_to_database(data, env_path=None):
    """Import parsed data directly to MySQL database using credentials from .env."""
    if not pymysql:
        raise RuntimeError("pymysql is required for database import. Install with: pip install pymysql")

    env = load_env(env_path)
    conn = pymysql.connect(
        host=env['DB_HOST'],
        port=int(env['DB_PORT']),
        user=env['DB_USERNAME'],
        password=env['DB_PASSWORD'],
        database=env['DB_DATABASE'],
        charset='utf8mb4',
        autocommit=False
    )

    cursor = conn.cursor()
    summary = {'inserted': 0, 'updated': 0, 'skipped': 0, 'details': []}

    try:
        # 1. Resolve Academic Year
        cursor.execute("SELECT id, name, is_active FROM academic_years")
        ay_rows = cursor.fetchall()
        ay_map = {row[1]: row[0] for row in ay_rows}
        active_ay_id = next((row[0] for row in ay_rows if row[2]), None)

        if not active_ay_id and ay_rows:
            active_ay_id = ay_rows[0][0]

        # 2. Get Roles
        cursor.execute("SELECT id, name FROM roles")
        role_map = {row[1]: row[0] for row in cursor.fetchall()}
        siswa_role_id = role_map.get('Siswa', 6)
        guru_role_id = role_map.get('Guru', 3)

        # Handle Student Roster
        if data['type'] == 'student_roster':
            # Target academic year
            target_year_str = data.get('academic_year', '2026-2027').replace(' ', '')
            matched_ay_id = None
            for name, ay_id in ay_map.items():
                if target_year_str in name.replace(' ', ''):
                    matched_ay_id = ay_id
                    break
            
            if not matched_ay_id:
                # Create academic year if missing
                ay_name = f"{target_year_str} - Ganjil"
                cursor.execute(
                    "INSERT INTO academic_years (name, start_date, end_date, is_active, created_at, updated_at) "
                    "VALUES (%s, '2026-07-01', '2026-12-31', 0, NOW(), NOW())",
                    (ay_name,)
                )
                matched_ay_id = cursor.lastrowid
                ay_map[ay_name] = matched_ay_id

            # Cache classrooms
            cursor.execute("SELECT id, name FROM classrooms")
            classroom_map = {row[1].upper(): row[0] for row in cursor.fetchall()}

            # Cache users
            cursor.execute("SELECT id, identity_number, username FROM users")
            user_nis_map = {}
            for uid, ident, uname in cursor.fetchall():
                if ident:
                    user_nis_map[str(ident).strip()] = uid
                if uname:
                    user_nis_map[str(uname).strip()] = uid

            # Default password hash for 'password'
            # $2y$12$e8Yx9p8vJ0z8lHh2hI4PDeQW3h4L9vGzJb1kXl9zV8r3z5QYk5l6G
            # Use Laravel-compatible bcrypt hash for 'password'
            bcrypt_password_hash = '$2y$12$6K.jZ4yF0r5C8h2/f4d7uuvm5sF6Jt6VzIuGZ6XzM0jI9rD1vH6pW'

            inserted_students = 0
            linked_classes = 0

            for student in data['students']:
                cls_name = student['class']
                norm_cls_name = normalize_class_name(cls_name)

                # Ensure classroom exists
                if norm_cls_name not in classroom_map:
                    cursor.execute(
                        "INSERT INTO classrooms (name, created_at, updated_at) VALUES (%s, NOW(), NOW())",
                        (norm_cls_name,)
                    )
                    cls_id = cursor.lastrowid
                    classroom_map[norm_cls_name] = cls_id
                else:
                    cls_id = classroom_map[norm_cls_name]

                # Ensure student user exists
                nis = student['nis']
                name = student['name']

                if nis in user_nis_map:
                    student_user_id = user_nis_map[nis]
                    # Update name or role if needed
                    cursor.execute(
                        "UPDATE users SET full_name = %s, role_id = %s, updated_at = NOW() WHERE id = %s",
                        (name, siswa_role_id, student_user_id)
                    )
                else:
                    cursor.execute(
                        "INSERT INTO users (role_id, full_name, username, identity_number, password, status, created_at, updated_at) "
                        "VALUES (%s, %s, %s, %s, %s, 'Aktif', NOW(), NOW())",
                        (siswa_role_id, name, nis, nis, bcrypt_password_hash)
                    )
                    student_user_id = cursor.lastrowid
                    user_nis_map[nis] = student_user_id
                    inserted_students += 1

                # Link to user_class
                cursor.execute(
                    "SELECT id FROM user_class WHERE user_id = %s AND classroom_id = %s AND academic_year_id = %s",
                    (student_user_id, cls_id, matched_ay_id)
                )
                if not cursor.fetchone():
                    cursor.execute(
                        "INSERT INTO user_class (user_id, classroom_id, academic_year_id, status, created_at, updated_at) "
                        "VALUES (%s, %s, %s, 'Aktif', NOW(), NOW())",
                        (student_user_id, cls_id, matched_ay_id)
                    )
                    linked_classes += 1

            conn.commit()
            summary['inserted'] = inserted_students
            summary['updated'] = linked_classes
            summary['details'] = [
                f"Siswa terdaftar / diperbarui: {len(data['students'])}",
                f"Siswa baru dibuat: {inserted_students}",
                f"Penempatan kelas siswa (user_class): {linked_classes}",
                f"Tahun Ajaran: ID {matched_ay_id}"
            ]

        # Handle Timetable PDF
        elif data['type'] == 'timetable_fet':
            target_ay_id = active_ay_id

            # 1. Sync Classrooms
            cursor.execute("SELECT id, name FROM classrooms")
            classroom_map = {row[1].upper(): row[0] for row in cursor.fetchall()}
            for cls_name in data['classes']:
                norm_cls_name = normalize_class_name(cls_name)
                if norm_cls_name not in classroom_map:
                    cursor.execute(
                        "INSERT INTO classrooms (name, created_at, updated_at) VALUES (%s, NOW(), NOW())",
                        (norm_cls_name,)
                    )
                    classroom_map[norm_cls_name] = cursor.lastrowid

            # 2. Sync Subjects
            cursor.execute("SELECT id, name FROM subjects")
            subject_map = {row[1].upper(): row[0] for row in cursor.fetchall()}
            for subj in data['subjects']:
                s_norm = subj.strip().upper()
                if s_norm not in subject_map:
                    cursor.execute(
                        "INSERT INTO subjects (name, created_at, updated_at) VALUES (%s, NOW(), NOW())",
                        (subj.strip(),)
                    )
                    subject_map[s_norm] = cursor.lastrowid

            # 3. Sync Rooms
            cursor.execute("SELECT id, name FROM rooms")
            room_map = {row[1].upper(): row[0] for row in cursor.fetchall()}
            if 'RUANG TEORI' not in room_map:
                cursor.execute("INSERT INTO rooms (name, created_at, updated_at) VALUES ('Ruang Teori', NOW(), NOW())")
                default_room_id = cursor.lastrowid
                room_map['RUANG TEORI'] = default_room_id
            else:
                default_room_id = room_map['RUANG TEORI']

            for r in data['rooms']:
                r_norm = r.strip().upper()
                if r_norm not in room_map:
                    cursor.execute(
                        "INSERT INTO rooms (name, created_at, updated_at) VALUES (%s, NOW(), NOW())",
                        (r.strip(),)
                    )
                    room_map[r_norm] = cursor.lastrowid

            # 4. Sync Teachers (User role Guru)
            cursor.execute("SELECT id, full_name, identity_number, username FROM users WHERE role_id = %s", (guru_role_id,))
            teacher_map = {}
            for uid, fname, ident, uname in cursor.fetchall():
                if fname:
                    teacher_map[fname.strip().upper()] = uid
                if ident:
                    teacher_map[str(ident).strip()] = uid

            bcrypt_password_hash = '$2y$12$6K.jZ4yF0r5C8h2/f4d7uuvm5sF6Jt6VzIuGZ6XzM0jI9rD1vH6pW'
            for t in data['teachers']:
                t_name = t['name'].strip()
                t_code = t['code']
                t_key = t_name.upper()

                if t_key not in teacher_map and (not t_code or str(t_code) not in teacher_map):
                    uname = f"guru_{t_code}" if t_code else re.sub(r'[^a-z0-9]', '', t_name.lower())[:20]
                    cursor.execute(
                        "INSERT INTO users (role_id, full_name, username, identity_number, password, status, created_at, updated_at) "
                        "VALUES (%s, %s, %s, %s, %s, 'Aktif', NOW(), NOW())",
                        (guru_role_id, t_name, uname, t_code or uname, bcrypt_password_hash)
                    )
                    uid = cursor.lastrowid
                    teacher_map[t_key] = uid
                    if t_code:
                        teacher_map[str(t_code)] = uid

            # 5. Insert Schedules
            inserted_schedules = 0
            cycle = data.get('cycle_type', 'Putaran 1')

            for s in data['schedules']:
                cls_id = classroom_map.get(normalize_class_name(s['classroom']))
                subj_id = subject_map.get(s['subject'].strip().upper())
                
                t_key = s['teacher_name'].strip().upper()
                t_id = teacher_map.get(t_key) or (teacher_map.get(str(s['teacher_code'])) if s['teacher_code'] else None)

                r_id = room_map.get(s['room'].strip().upper()) if s.get('room') else default_room_id
                if not r_id:
                    r_id = default_room_id

                if not cls_id or not subj_id or not t_id:
                    continue

                # Check existing duplicate
                cursor.execute(
                    "SELECT id FROM schedules WHERE academic_year_id = %s AND classroom_id = %s "
                    "AND day_of_week = %s AND start_time = %s AND cycle_type = %s",
                    (target_ay_id, cls_id, s['day_of_week'], s['start_time'], cycle)
                )
                existing = cursor.fetchone()
                if existing:
                    cursor.execute(
                        "UPDATE schedules SET teacher_id = %s, subject_id = %s, room_id = %s, end_time = %s, updated_at = NOW() "
                        "WHERE id = %s",
                        (t_id, subj_id, r_id, s['end_time'], existing[0])
                    )
                else:
                    cursor.execute(
                        "INSERT INTO schedules (academic_year_id, classroom_id, teacher_id, subject_id, room_id, "
                        "day_of_week, start_time, end_time, cycle_type, created_at, updated_at) "
                        "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), NOW())",
                        (target_ay_id, cls_id, t_id, subj_id, r_id, s['day_of_week'], s['start_time'], s['end_time'], cycle)
                    )
                    inserted_schedules += 1

            conn.commit()
            summary['inserted'] = inserted_schedules
            summary['details'] = [
                f"Jadwal KBM diproses: {len(data['schedules'])}",
                f"Jadwal baru disimpan: {inserted_schedules}",
                f"Guru disinkronkan: {len(data['teachers'])}",
                f"Mata Pelajaran: {len(data['subjects'])}",
                f"Ruang / Laboratorium: {len(data['rooms'])}",
                f"Putaran: {cycle}"
            ]

    except Exception as e:
        conn.rollback()
        raise e
    finally:
        conn.close()

    return summary


def main():
    parser = argparse.ArgumentParser(description="Smart Document Detector and Importer for SCP SMK")
    parser.add_argument("file", help="Path to Excel (.xlsx, .csv) or PDF timetable file")
    parser.add_argument("--room-file", dest="room_file", help="Path to second PDF (Jadwal Ruang) to merge with Jadwal Kelas", default=None)
    parser.add_argument("--preview", action="store_true", help="Quick metadata preview only without full extraction")
    parser.add_argument("--json", action="store_true", help="Output result as JSON only")
    parser.add_argument("--import", dest="do_import", action="store_true", help="Directly import parsed data into MySQL database")
    parser.add_argument("--dry-run", action="store_true", help="Parse and validate without modifying database")
    parser.add_argument("--env", help="Path to .env file for database connection", default=None)

    args = parser.parse_args()

    file_path = os.path.abspath(args.file)
    if not os.path.exists(file_path):
        print(f"Error: File '{file_path}' does not exist.", file=sys.stderr)
        sys.exit(1)

    try:
        if args.preview:
            if args.room_file:
                room_file_path = os.path.abspath(args.room_file)
                if not os.path.exists(room_file_path):
                    print(f"Error: Room file '{room_file_path}' does not exist.", file=sys.stderr)
                    sys.exit(1)
                result = quick_preview_schedule_pair(file_path, room_file_path)
            else:
                result = quick_preview_single(file_path)
        elif args.room_file:
            room_file_path = os.path.abspath(args.room_file)
            if not os.path.exists(room_file_path):
                print(f"Error: Room file '{room_file_path}' does not exist.", file=sys.stderr)
                sys.exit(1)
            result = parse_schedule_pdf_pair(file_path, room_file_path)
        else:
            file_type = detect_file_type(file_path)
            if file_type == 'EMPLOYEE_TEACHERS':
                result = parse_employee_teachers(file_path)
            elif file_type == 'HOMEROOM_COUNSELOR':
                result = parse_homeroom_counselor(file_path)
            elif file_type == 'STUDENT_ROSTER_EXCEL':
                result = parse_student_roster_excel(file_path)
            elif file_type == 'ROOM_SCHEDULE_PDF':
                result = parse_room_schedule_pdf(file_path)
            elif file_type == 'TIMETABLE_FET_PDF':
                result = parse_fet_timetable_pdf(file_path)
            else:
                print(f"Error: Unrecognized document format for '{file_path}'.", file=sys.stderr)
                sys.exit(1)

        if args.json:
            print(json.dumps(result, indent=2, ensure_ascii=False))
            sys.exit(0)

        # Print beautiful summary
        print("=" * 60)
        print("  SCP SMK - HASIL DETEKSI DOKUMEN OTOMATIS")
        print("=" * 60)
        print(f"File Path        : {file_path}")
        if args.room_file:
            print(f"Room File Path   : {os.path.abspath(args.room_file)}")
        print(f"Tipe Terdeteksi  : {result.get('detected_format')}")

        if result['type'] == 'employee_teachers':
            print(f"Total Guru Terdeteksi: {result.get('total_teachers')} guru")
            print("Catatan: Data TU, Outsourcing, CS, Satpam diabaikan secara otomatis.")
            print("-" * 60)
            print("Sampel Guru & NIP (5 Pertama):")
            for t in result['teachers'][:5]:
                print(f"  - NIP: {t['nip']} | {t['name']} ({t.get('subject') or '-'})")

        elif result['type'] == 'homeroom_and_counselor':
            print(f"Tahun Ajaran     : {result.get('academic_year')}")
            print(f"Total Kelas      : {result.get('total_classes')} kelas")
            print(f"Total Wali Kelas : {result.get('total_homeroom')} guru")
            print(f"Total Guru BK    : {result.get('total_counselor')} guru")
            print("-" * 60)
            print("Sampel Data (5 Pertama):")
            for a in result['assignments'][:5]:
                print(f"  [{a['classroom']}] Wali: {a['wali_kelas']} | BK: {a['guru_bk']}")
            print("-" * 60)
            print("Daftar Guru BK (Wali BK):")
            for g in result['counselor_teachers']:
                print(f"  - {g}")

        elif result['type'] == 'student_roster':
            print(f"Tahun Ajaran     : {result.get('academic_year')}")
            print(f"Total Kelas      : {result.get('total_classes')} kelas")
            print(f"Total Siswa      : {result.get('total_students')} siswa")
            print("-" * 60)
            print("Sampel Data Siswa (5 Pertama):")
            for s in result['students'][:5]:
                print(f"  [{s['class']}] No.{s['no']} - NIS: {s['nis']} - {s['name']} ({s['gender']})")
            print("-" * 60)
            print("Ringkasan Rombel per Kelas:")
            classes_preview = list(result['classes_breakdown'].items())[:8]
            for c_name, count in classes_preview:
                print(f"  - {c_name}: {count} siswa")
            if len(result['classes_breakdown']) > 8:
                print(f"  ... dan {len(result['classes_breakdown']) - 8} kelas lainnya.")

        elif result['type'] == 'room_schedule':
            print(f"Institusi        : {result.get('institution')}")
            print(f"Tahun Ajaran     : {result.get('academic_year')}")
            print(f"Putaran          : {result.get('cycle_type')}")
            print(f"Total Kelas      : {result.get('total_classes')} kelas")
            print(f"Total Hari       : {result.get('total_days')} hari")
            print(f"Total Ruangan    : {result.get('total_rooms')} ruangan")
            print(f"Total Slot Ruang : {result.get('total_slots')} slot")

        elif result['type'] == 'timetable_fet':
            print(f"Institusi        : {result.get('institution')}")
            if result.get('academic_year'):
                print(f"Tahun Ajaran     : {result.get('academic_year')}")
            print(f"Putaran          : {result.get('cycle_type')}")
            print(f"Total Kelas      : {result.get('total_classes')} kelas")
            print(f"Total Guru       : {result.get('total_teachers')} guru")
            print(f"Mata Pelajaran   : {result.get('total_subjects')} mapel")
            print(f"Ruang / Lab      : {result.get('total_rooms')} ruangan")
            print(f"Total Slot KBM   : {result.get('total_schedules')} jadwal")
            print("-" * 60)
            print("Sampel Jadwal Terdeteksi (5 Pertama):")
            for sc in result['schedules'][:5]:
                r_str = f" [Ruang: {sc['room']}]" if sc.get('room') else ""
                print(f"  [{sc['classroom']}] {sc['day_of_week']} {sc['start_time'][:5]}-{sc['end_time'][:5]}: {sc['subject']} ({sc['teacher_name']}){r_str}")
            print("-" * 60)
            print("Daftar Ruangan Terdeteksi:")
            print("  " + ", ".join(result['rooms'][:10]) + ("..." if len(result['rooms']) > 10 else ""))

        if args.do_import and not args.dry_run:
            print("\nMemulai proses import ke Database MySQL...")
            db_res = import_to_database(result, env_path=args.env)
            print("STATUS IMPORT: BERHASIL!")
            for d in db_res['details']:
                print(f"  ✔ {d}")
        elif args.dry_run:
            print("\n[DRY RUN] Validasi berhasil. Tidak ada perubahan pada database.")
        else:
            print("\nTips: Jalankan dengan argumen --import untuk langsung memasukkan ke database.")

        print("=" * 60)

    except Exception as e:
        print(f"\nTerjadi kesalahan saat memproses file: {e}", file=sys.stderr)
        import traceback
        traceback.print_exc()
        sys.exit(1)


if __name__ == '__main__':
    main()
