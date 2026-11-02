# 🤝 Team Engineering Handbook & Onboarding Manual

> *"Reminder: You're writing this for your team, not just for yourself."*  
> — Clean code, explicit architecture, robust security, and comprehensive documentation make software maintainable for everyone.

Welcome to the engineering team behind **Yadav Living** and **Yadav Living PG**! This handbook sets forth the architectural conventions, coding rules, security mandates, and daily runbooks for any developer working on this codebase.

---

## 🏛️ 1. Project Philosophy & Architecture

Our software operates a **dual-property operational model**:
1. **Yadav Living**: Residential community operations (maintenance billing, security gate visitor registry, resident KYC, AI resident assistant).
2. **Yadav Living PG (`Yadav.in`)**: Student coliving accommodation (Floors 1–4, bed allocation, student KYC, vacate intimations, maintenance ticketing).

### Core Stakeholders
- **Property Owners**: **Binod Yadav & Sumit Yadav** (Property ownership, in-person room visits, custom stay terms & fee agreements).
- **Lead Platform Architect**: **Yuvraj** ([yuvrajyadav734@gmail.com](mailto:yuvrajyadav734@gmail.com)).
- **Society Admin & PG Warden**: **Yuvraj** (day-to-day operations, approvals, allocations).
- **Official Registrar / Creator**: **Sumit Yadav**.
- **Cultural Motif**: **"🙏 जय श्री कृष्णा • Jai Shree Krishna"**.

---

## ⚡ 2. 5-Minute Developer Quickstart

### Prerequisites
- PHP 8.0+ (`php -v`) with `mysqli`, `pdo_mysql`, and `gd` enabled.
- MySQL 8.0+ running on `localhost:3306`.

### Local Development Steps
```bash
# 1. Clone repository
git clone <repository-url> yadav-living
cd yadav-living

# 2. Configure private local environment (strictly git-ignored)
cp .env.example .env.local
# Set DB_USER, DB_PASS, DB_PORT if different from defaults

# 3. Initialize database
mysql -u root -e "CREATE DATABASE IF NOT EXISTS hms;"
mysql -u root hms < "HMS-2/hms.sql"

# 4. Start local development server
php -S 127.0.0.1:8000 -t HMS-2

# 5. Run test suite to verify 100% green status
php scratch/test_all_features.php
```

---

## 🛡️ 3. Non-Negotiable Engineering Standards

### Rule 1: Zero Raw SQL Queries (SQLi Prevention)
Never interpolate or concatenate variables into SQL strings. Always use parameterized prepared statements:

```php
// ❌ FORBIDDEN (SQL Injection Risk)
$query = "SELECT * FROM member WHERE email = '$email'";

// ✅ MANDATORY (Prepared Statement with strict binding)
$stmt = mysqli_prepare($con, "SELECT id, username, email, password FROM member WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
```

### Rule 2: Mandatory Output Sanitization (XSS Prevention)
Every dynamic string rendered in HTML must pass through `htmlspecialchars()`:

```php
// ❌ FORBIDDEN
echo "<div>Welcome, " . $user['name'] . "</div>";

// ✅ MANDATORY
echo "<div>Welcome, " . htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8') . "</div>";
```

### Rule 3: Anti-EAR (Execution-After-Redirect) Guarantee
Whenever `header("Location: ...")` is invoked, immediately terminate script execution with `exit;`:

```php
// ❌ DANGEROUS (Code continues running after redirect header)
header("Location: login.php");

// ✅ MANDATORY
header("Location: login.php");
exit;
```

### Rule 4: Role-Based Access Control (RBAC)
Always call `require_role()` or `require_pg_role()` at the very top of protected pages:
```php
require_once(__DIR__ . "/../../Includes/config.php");
require_once(__DIR__ . "/../../Includes/session.php");
require_role('admin', '../../login.php');
```

### Rule 5: Credential Isolation via `.env.local`
Never hardcode passwords or sensitive credentials in PHP files. Always load environment variables using `hms_env('KEY', 'default')`.
- All secret local overrides belong exclusively in `.env.local`.
- `.env.local` is strictly in `.gitignore` and must never be committed.
- When introducing a new environment variable, document it in `.env.example` with clear comments.

---

## 📋 4. Daily Admin & Warden Operations Runbook

### How to Allocate a Bed to a New Student
1. Student registers at `pg/register.php` (no bed is selected upfront).
2. Student completes guardian info and ID upload at `student/kyc.php`.
3. When the student visits the hostel in person, Warden opens **PG Students & KYC** ([`admin/pg_students.php`](http://127.0.0.1:8000/admin/pg_students.php)).
4. Click the `-- Assign Bed --` dropdown for the student, select any vacant bed (e.g. `R-201 (Bed A)`), and click **Assign**.

### How to Review Society Resident KYC
1. Navigate to **Resident KYC** ([`admin/society_kyc.php`](http://127.0.0.1:8000/admin/society_kyc.php)).
2. Inspect uploaded ID card (Aadhaar / Voter ID) and flat ownership / lease agreement.
3. Click **Verify** to activate their verified resident badge or **Reject** with feedback.

### How to Seed a New PG Room or Bed in Database
```sql
-- 1. Insert Room (e.g. Room 408, 4th floor, Double sharing)
INSERT INTO pg_rooms (room_number, floor, occupancy_type, total_beds, occupied_beds, status) 
VALUES ('R-408', 'Four Floor [Yadav Living PG]', 'double', 2, 0, 'available');

-- 2. Insert Beds for this room
SET @room_id = LAST_INSERT_ID();
INSERT INTO pg_beds (room_id, bed_label, is_occupied) VALUES (@room_id, 'Bed A', 0);
INSERT INTO pg_beds (room_id, bed_label, is_occupied) VALUES (@room_id, 'Bed B', 0);
```

---

## 🎨 5. Frontend & 3D WebGL Guidelines

- **Three.js Performance**: Keep animation loops throttled to 30 FPS (`THREE.Clock` delta clamping). Always attach an `IntersectionObserver` to pause WebGL rendering when canvas is outside the viewport.
- **Boxicons**: Use local CSS fonts from `HMS-2/css/boxicons/` to guarantee offline reliability and fast render times.
- **Glassmorphic Theme**: Maintain high contrast: text on glass cards must use `#ffffff` or `#f8fafc` with subtle drop shadows (`0 2px 4px rgba(0,0,0,0.4)`).

---

## 📦 6. Git Protocol & Commits

- Commit messages must follow Conventional Commits:
  - `feat:` New feature for resident, student, or admin
  - `fix:` Bug fix in controller, layout, or database query
  - `docs:` Documentation updates, handbook, or README
  - `refactor:` Code cleanup without changing functionality
  - `security:` Hardening against SQLi, XSS, CSRF, or IDOR
- Before pushing to `main`, ensure the verification suite produces zero failures:
  ```bash
  php scratch/test_all_features.php
  ```

Thank you for building clean, secure, and team-friendly software for **Yadav Living & Yadav Living PG**!
