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
    """Normalize class name, e.g. 'X RPL1' -> 'X RPL 1', 'XII BD' -> 'XII BD'."""
    if not name:
        return ''
    name = ' '.join(str(name).strip().split())
    # Match patterns like "X RPL1" or "XI TKJ2" or "XII AK3"
    m = re.match(r'^(XII|XI|X|12|11|10)\s+([A-Za-z]+)(\d+)$', name, re.I)
    if m:
        return f"{m.group(1).upper()} {m.group(2).upper()} {m.group(3)}"
    
    # Match patterns like "X RPL 1"
    m2 = re.match(r'^(XII|XI|X|12|11|10)\s+([A-Za-z]+)\s+(\d+)$', name, re.I)
    if m2:
        return f"{m2.group(1).upper()} {m2.group(2).upper()} {m2.group(3)}"

    return name.upper()


def parse_fet_cell(cell_str):
    """
    Parse a cell inside FET timetable table:
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
        t_name = tm.group(2).strip().rstrip('",;.')
    else:
        t_code = None
        t_name = ' '.join(teacher_raw.split()).rstrip('",;.')

    room = ' '.join(room_raw.split()).strip() if room_raw and room_raw.strip() else None
    if room in ['"', '""', '-']:
        room = None

    return {
        'subject': subj,
        'teacher_code': t_code,
        'teacher_name': t_name,
        'room': room
    }


def detect_file_type(file_path):
    """Detect whether file is Student Roster (Excel/CSV) or Timetable (PDF)."""
    p = Path(file_path)
    ext = p.suffix.lower()

    if ext in ['.xlsx', '.xls', '.csv']:
        # Inspect text/content
        if ext == '.csv':
            try:
                with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
                    content = f.read(4000)
                    if 'DAFTAR HADIR SISWA' in content or 'KELAS :' in content:
                        return 'STUDENT_ROSTER_EXCEL'
            except Exception:
                pass
        elif openpyxl:
            try:
                wb = openpyxl.load_workbook(file_path, read_only=True, data_only=True)
                for sheetname in wb.sheetnames[:3]:
                    sheet = wb[sheetname]
                    for row in sheet.iter_rows(max_row=10, values_only=True):
                        for cell in row:
                            if cell and ('DAFTAR HADIR SISWA' in str(cell) or 'KELAS :' in str(cell)):
                                return 'STUDENT_ROSTER_EXCEL'
            except Exception:
                pass
        return 'STUDENT_ROSTER_EXCEL'

    elif ext == '.pdf':
        if pypdf:
            try:
                reader = pypdf.PdfReader(file_path)
                p1 = reader.pages[0].extract_text()
                if 'SMK NEGERI 1 BANTUL' in p1 or 'FET' in p1 or 'Kelas X' in p1:
                    return 'TIMETABLE_FET_PDF'
            except Exception:
                pass
        return 'TIMETABLE_FET_PDF'

    return 'UNKNOWN'


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


def parse_fet_timetable_pdf(file_path):
    """Parse PDF file containing FET timetable."""
    if not pdfplumber:
        raise RuntimeError("pdfplumber is required to parse PDF timetable. Please install with: pip install pdfplumber")

    # Detect cycle type from filename or default
    filename = Path(file_path).name.upper()
    cycle_type = 'Putaran 1'
    if 'PUT_2' in filename or 'PUTARAN_2' in filename or 'PUTARAN 2' in filename:
        cycle_type = 'Putaran 2'

    teachers_dict = {}
    subjects_set = set()
    rooms_set = set()
    classes_dict = {}
    schedules = []

    with pdfplumber.open(file_path) as pdf:
        total_pages = len(pdf.pages)
        # Page 0 is index / TOC, pages 1..N are class grids
        start_page = 1 if total_pages > 1 else 0

        for p_idx in range(start_page, total_pages):
            page = pdf.pages[p_idx]
            tables = page.extract_tables()
            if not tables:
                continue

            t = tables[0]
            # Row 0: class name
            raw_class_name = None
            for cell in t[0]:
                if cell and cell.strip():
                    raw_class_name = cell.strip()
                    break
            
            if not raw_class_name:
                continue

            class_name = normalize_class_name(raw_class_name)
            if class_name not in classes_dict:
                classes_dict[class_name] = 0

            # Row 1: Header days
            days = [c.strip() for c in t[1][1:] if c and c.strip()]
            
            # Rows 2..N: Time slots
            # Process column by column (each day)
            for c_idx, day in enumerate(days, start=1):
                # We track the current active block
                current_entry = None
                current_start_slot = None
                last_active_slot = None

                for r_idx in range(2, len(t)):
                    time_slot = t[r_idx][0]
                    if not time_slot or time_slot.strip() not in TIME_SLOT_RANGES:
                        continue
                    slot_code = time_slot.strip()
                    cell_val = t[r_idx][c_idx] if c_idx < len(t[r_idx]) else None

                    parsed = parse_fet_cell(cell_val)

                    if parsed:
                        # If an entry was already accumulating, finish it
                        if current_entry and current_start_slot and last_active_slot:
                            start_t = TIME_SLOT_RANGES[current_start_slot][0]
                            end_t = TIME_SLOT_RANGES[last_active_slot][1]
                            sched_item = {
                                'classroom': class_name,
                                'day_of_week': day,
                                'start_time': start_t,
                                'end_time': end_t,
                                'cycle_type': cycle_type,
                                'subject': current_entry['subject'],
                                'teacher_code': current_entry['teacher_code'],
                                'teacher_name': current_entry['teacher_name'],
                                'room': current_entry['room'],
                            }
                            schedules.append(sched_item)
                            classes_dict[class_name] += 1

                        current_entry = parsed
                        current_start_slot = slot_code
                        last_active_slot = slot_code

                        # Track master data
                        if parsed['teacher_name']:
                            teachers_dict[parsed['teacher_code'] or parsed['teacher_name']] = {
                                'code': parsed['teacher_code'],
                                'name': parsed['teacher_name']
                            }
                        if parsed['subject']:
                            subjects_set.add(parsed['subject'])
                        if parsed['room']:
                            rooms_set.add(parsed['room'])

                    else:
                        # Cell is empty or break
                        if current_entry:
                            # It continues the current block if empty string
                            if cell_val is None or cell_val.strip() == '':
                                last_active_slot = slot_code
                            else:
                                # Cell has '-x-' or break: end current block
                                start_t = TIME_SLOT_RANGES[current_start_slot][0]
                                end_t = TIME_SLOT_RANGES[last_active_slot][1]
                                sched_item = {
                                    'classroom': class_name,
                                    'day_of_week': day,
                                    'start_time': start_t,
                                    'end_time': end_t,
                                    'cycle_type': cycle_type,
                                    'subject': current_entry['subject'],
                                    'teacher_code': current_entry['teacher_code'],
                                    'teacher_name': current_entry['teacher_name'],
                                    'room': current_entry['room'],
                                }
                                schedules.append(sched_item)
                                classes_dict[class_name] += 1
                                current_entry = None
                                current_start_slot = None
                                last_active_slot = None

                # Finish trailing block for the day
                if current_entry and current_start_slot and last_active_slot:
                    start_t = TIME_SLOT_RANGES[current_start_slot][0]
                    end_t = TIME_SLOT_RANGES[last_active_slot][1]
                    sched_item = {
                        'classroom': class_name,
                        'day_of_week': day,
                        'start_time': start_t,
                        'end_time': end_t,
                        'cycle_type': cycle_type,
                        'subject': current_entry['subject'],
                        'teacher_code': current_entry['teacher_code'],
                        'teacher_name': current_entry['teacher_name'],
                        'room': current_entry['room'],
                    }
                    schedules.append(sched_item)
                    classes_dict[class_name] += 1

    return {
        'type': 'timetable_fet',
        'detected_format': 'PDF / Jadwal Pelajaran FET Timetable',
        'institution': 'SMK NEGERI 1 BANTUL',
        'cycle_type': cycle_type,
        'total_classes': len(classes_dict),
        'total_teachers': len(teachers_dict),
        'total_subjects': len(subjects_set),
        'total_rooms': len(rooms_set),
        'total_schedules': len(schedules),
        'teachers': list(teachers_dict.values()),
        'subjects': sorted(list(subjects_set)),
        'rooms': sorted(list(rooms_set)),
        'classes': sorted(list(classes_dict.keys())),
        'schedules': schedules
    }


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
    parser.add_argument("--json", action="store_true", help="Output result as JSON only")
    parser.add_argument("--import", dest="do_import", action="store_true", help="Directly import parsed data into MySQL database")
    parser.add_argument("--dry-run", action="store_true", help="Parse and validate without modifying database")
    parser.add_argument("--env", help="Path to .env file for database connection", default=None)

    args = parser.parse_args()

    file_path = os.path.abspath(args.file)
    if not os.path.exists(file_path):
        print(f"Error: File '{file_path}' does not exist.", file=sys.stderr)
        sys.exit(1)

    file_type = detect_file_type(file_path)

    try:
        if file_type == 'STUDENT_ROSTER_EXCEL':
            result = parse_student_roster_excel(file_path)
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
        print(f"Tipe Terdeteksi  : {result.get('detected_format')}")

        if result['type'] == 'student_roster':
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

        elif result['type'] == 'timetable_fet':
            print(f"Institusi        : {result.get('institution')}")
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
