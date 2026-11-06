# Yadav Living

Property management for two properties in Deoghar, Jharkhand: **Yadav Living Society** (residential community) and **Yadav Living PG** (student accommodation). Residents, students, guards, wardens and administrators each get a role-based portal for KYC, billing, complaints, room allocation and community updates.

> **Project type:** Freelance client project  
> **Maintainer:** Yuvraj (full-stack developer and platform architect)  
> **Public site:** <https://yadavliving.netlify.app> (the portals sit behind sign-in)



- The **TypeScript/Netlify portal** is the version served at the public URL.
- The **PHP/MySQL app** in `HMS-2/` is the original implementation. It still runs (Docker, Render blueprint) and `netlify.toml` redirects its legacy URLs to the new portal.
- **Not certified for production use with real resident or student data.** There has been no full security review, and the [known issues](#known-issues-and-hardening-backlog) below are still open. Treat every sign-up on the live site as handling real personal data and plan accordingly.

## Screenshots
<img width="1600" height="687" alt="13-public-home" src="https://github.com/user-attachments/assets/09005c4f-bcd5-4240-bcc1-b16ba3efa749" />
<img width="1600" height="696" alt="14-3d-digital-twin" src="https://github.com/user-attachments/assets/62ef5468-3f85-4521-9572-231712cc85ec" />
<img width="1600" height="684" alt="15-property-cards" src="https://github.com/user-attachments/assets/e9773b64-e1be-489c-bf01-8ea4473d9d4c" />
<img width="1600" height="537" alt="16-faith-note" src="https://github.com/user-attachments/assets/85f491c7-681f-41bb-84b9-dd8e4d0e4474" />
<img width="1600" height="643" alt="17-our-story" src="https://github.com/user-attachments/assets/9ebce12d-8049-41e9-a99c-e112579d20dc" />
<img width="1600" height="871" alt="01-portal-sign-in" src="https://github.com/user-attachments/assets/68e3a50c-0ec7-41c6-a363-11996cf5fae6" />
<img width="1600" height="871" alt="02-portal-overview" src="https://github.com/user-attachments/assets/0df2cd7d-bd40-48e4-844a-29203824aee8" />
<img width="1600" height="872" alt="03-announcements" src="https://github.com/user-attachments/assets/687403d1-903a-485f-a7ad-ec601be0bdb9" />
<img width="1600" height="873" alt="04-events" src="https://github.com/user-attachments/assets/b16e7fc9-b76f-41e2-acd6-49fdcb1b465c" />
<img width="1600" height="876" alt="05-home-services" src="https://github.com/user-attachments/assets/b6649df6-542e-4766-9f5d-ad01431b3f81" />
<img width="1600" height="875" alt="06-neighbourhood" src="https://github.com/user-attachments/assets/983e136b-e087-45c9-b49a-22e4f4ca6671" />
<img width="1600" height="872" alt="07-complaints" src="https://github.com/user-attachments/assets/17565219-16b9-4319-aafe-b91155651a01" />
<img width="1600" height="871" alt="08-kyc-submission" src="https://github.com/user-attachments/assets/fc3732c3-5cd2-4541-8561-40e21dcd029c" />
<img width="1600" height="867" alt="09-bills-and-payments" src="https://github.com/user-attachments/assets/e4596798-c82e-4c97-91ec-ca6e6b96902e" />
<img width="1600" height="873" alt="10-community-assistant" src="https://github.com/user-attachments/assets/d8ac4345-6371-4083-9278-4c9349ac566d" />
<img width="1600" height="873" alt="11-location" src="https://github.com/user-attachments/assets/07cd609f-08e4-497c-ba7b-461e96ed7771" />
<img width="1600" height="876" alt="12-my-profile" src="https://github.com/user-attachments/assets/d13859d4-b368-47ad-b9d6-7dd20e6679f3" />




## Two stacks, one product

| | Netlify portal (deployed) | Legacy PHP app |
|---|---|---|
| Location | `web/`, `netlify/`, `shared/` | `HMS-2/`, `Dockerfile`, `docker-compose.yml`, `render.yaml` |
| Stack | Vite + TypeScript SPA, Netlify Functions, Netlify Identity, Netlify Database (Postgres), Netlify Blobs | PHP 8.2 + Apache, MySQL 8 / MariaDB, session-based auth |
| Data model | Generic `living_records` table (JSONB `data` + `kind`), plus profiles, rooms, assignments, documents | About 14 relational tables (`member`, `bill`, `pg_students`, `pg_kyc`, ...) |
| AI assistant | `netlify/functions/assistant.mts` | `ai_service/` (FastAPI) behind `HMS-2/Includes/ai_chat.php` |
| Tests | `tests/` (Node + Playwright) | `ai_service/test_main.py`, `php -l` |

Pick one stack per change. Do not fix a bug in one and forget the other if both are still in use.

## Portal modules (Netlify version)

Defined once in [`shared/model.ts`](shared/model.ts) and used by both the API and the UI.

| Module | Property | Who can create | Who can read | Statuses |
|---|---|---|---|---|
| Announcements, Events, Neighbourhood | both | Admin (any property), Warden (PG) | Everyone in the property | Published |
| Home services | Society | Admin | Everyone in the property | Published |
| Mess menu | PG | Admin, Warden | Everyone in the property | Published |
| Complaints | both | The resident or student | Owner and managers | Open, In progress, Resolved |
| KYC documents | both | The resident or student | Owner and managers | Pending, Approved, Rejected |
| Move-out requests | PG | The student | Owner and managers | Pending, Approved, Rejected |
| Visitor registry | Society | Staff, Admin | Staff, Admin | Checked in, Checked out |
| Bills & payments | both | Managers, for a chosen account | Owner and managers | Due, Paid |

Plus: room and bed management (PG, managers), a resident/student directory (managers), a location page, and the community assistant.

### Roles

| Role | Scope |
|---|---|
| `admin` | Both properties, all modules |
| `warden` | PG only, manager permissions |
| `staff` | Society visitor registry only |
| Resident / student | Not a role. Anyone with a profile in a property sees their own private records plus the public ones |

Roles are assigned in Netlify Identity (`app_metadata`). The UI hides what a role can't use, but the real checks run in the functions via `isManager`, `isOffice`, `canCreate` and `canRead`.

## Repository layout

```
.
├── web/                    # Vite root: public home page and portal SPA
│   ├── index.html          # public landing page (+ Three.js campus view)
│   ├── portal/             # app.ts, index.html, portal.css
│   └── public/             # static assets, robots.txt, sitemap.xml
├── netlify/
│   ├── functions/          # portal.mts, documents.mts, assistant.mts
│   ├── lib/                # auth.ts (origin check, errors), knowledge.ts
│   └── database/migrations # Postgres schema
├── shared/model.ts         # modules, roles, permissions, validation
├── tests/                  # portal.test.ts (Node), browser/portal.spec.ts (Playwright)
├── ai_service/             # FastAPI assistant, knowledge.json, tests
├── HMS-2/                  # legacy PHP app (admin/, members/, staff/, pg/, home/, Includes/)
├── netlify.toml            # build, functions, redirects, security headers
├── Dockerfile, docker-compose.yml, entrypoint.sh, render.yaml   # legacy PHP deployment
└── images/screenshots/     # README screenshots (compressed, redacted)
```

## Getting started: Netlify portal

Requires Node 22 or newer.

```bash
npm install
npm run dev      # Vite dev server on :5173 (front-end only)
npm run check    # TypeScript type-check
npm test         # Node unit tests
npm run build    # outputs to dist/
```

`npm run dev` serves the front-end only. The portal API needs Netlify Functions, Identity, Database and Blobs, so exercise it on a Netlify deploy or with the Netlify CLI (not a project dependency).

Environment variables on the Netlify site:

| Variable | Purpose |
|---|---|
| `OPENAI_API_KEY` | Read by the OpenAI SDK in `assistant.mts`. Without it the assistant returns an error. |

Database: apply the SQL in `netlify/database/migrations/` to create `living_profiles`, `living_records`, `living_rooms`, `living_assignments`, `living_documents` and `living_ai_limits`.

First admin: create the account through Identity, then assign the `admin` role. There is no in-app way to grant roles.

### API summary

| Endpoint | Purpose |
|---|---|
| `/api/portal?action=profile\|people\|rooms\|records&property=society\|pg&kind=...` | Profiles, directory, rooms and beds, module records |
| `/api/documents?id=<record id>` | `GET` downloads a KYC document. `POST` attaches one. |
| `/api/assistant` | Community assistant (`POST`) |

Behaviour to know about:

- Writes (anything but `GET`/`HEAD`) must come from the same origin or get a 403.
- KYC uploads accept PDF, PNG or JPEG up to 4 MB, validated by file signature. A pending KYC record accepts one document.
- Record lists are capped at 500 rows (people at 1000) with no pagination.
- A profile's property is chosen at onboarding and cannot be changed in the app.

## AI assistant

Both implementations answer only from a curated guide, refuse when nothing matches, and reject questions that look like they contain personal data.

| | `ai_service/` (PHP stack) | `assistant.mts` (Netlify stack) |
|---|---|---|
| Retrieval | IDF-weighted keyword scoring over `knowledge.json` (17 entries) | Exact keyword overlap over `netlify/lib/knowledge.ts` |
| Model default | `gpt-4o-mini` (`OPENAI_MODEL`) | `gpt-4.1-mini` |
| PII filter | Email, phone, government-ID and card patterns | Rejects any `@` or long digit run |
| Rate limit | One request per short interval per session | 5 per minute per user (table `living_ai_limits`) |
| Auth | Shared bearer token (`AI_SERVICE_TOKEN`) plus session and CSRF token in PHP | Netlify Identity session |

Questions and the selected guide excerpts are sent to the configured AI provider. The filters are safeguards, not guarantees, so do not enter personal or financial information. The two knowledge bases are separate copies: update both when a workflow changes.

Run the Python service locally:

```bash
python3 -m venv .venv && .venv/bin/pip install -r ai_service/requirements.txt
AI_SERVICE_TOKEN=<random> OPENAI_API_KEY=<key> .venv/bin/uvicorn --app-dir ai_service main:app --port 8000
```

## Getting started: legacy PHP app

**Docker Compose** (PHP 8.2 + MySQL 8 + FastAPI):

```bash
cp .env.example .env     # set DB_PASS, DB_ROOT_PASSWORD, AI_SERVICE_TOKEN, OPENAI_API_KEY
docker compose up -d --build
```

- App: <http://localhost:8080/home/home.php>
- Admin login: `/admin_login.php`, PG login: `/pg/login.php`
- Compose imports `HMS-2/hms.sql` on first start. `docker compose down -v` deletes the database and upload volumes.

**Plain PHP** (PHP 8.2+ with `mysqli`, `pdo_mysql`, `gd`; MySQL 8):

```bash
cp .env.example .env.local
mysql -u root -e "CREATE DATABASE IF NOT EXISTS hms;" && mysql -u root hms < HMS-2/hms.sql
php -S 127.0.0.1:8000 -t HMS-2
```

Key variables (see `.env.example`): `APP_ENV`, `APP_URL`, `DB_HOST/PORT/NAME/USER/PASS`, `HMS_PRIVATE_UPLOAD_DIR`, `AI_SERVICE_URL`, `AI_SERVICE_TOKEN`, `OPENAI_*`. Set `APP_ENV=production` for HTTPS deployments, because that is what turns on the `Secure` session-cookie flag.

**Render:** [`render.yaml`](render.yaml) is a blueprint for a Starter web service with a 1 GB private-upload disk, plus a separate AI service. It needs an external MySQL-compatible database. Import a schema-only dump (`mysqldump --no-data`), never the demo data.

## Testing

| Suite | Command | Covers |
|---|---|---|
| Node unit tests | `npm test` | Permission matrix in `shared/model.ts`, input validation, same-origin write check. 7 tests. |
| Python unit tests | `python3 -m unittest discover -s ai_service -p 'test_*.py'` | Retrieval grounding, PII rejection, provider errors. 10 tests. |
| Playwright | start `npm run dev -- --port 8889`, then `npx playwright test` | Front-end flows with Identity and the API mocked |
| PHP syntax | `find HMS-2 -name '*.php' -not -path '*/vendor/*' -print0 \| xargs -0 -n1 php -l` | Syntax only |

None of these exercise the database-backed handlers in `portal.mts`, `documents.mts` or `assistant.mts`. Role boundaries for the PHP app still need manual checks with two accounts of each type.

## Security

Implemented:

- Server-side role and ownership checks on every Netlify function.
- Parameterized SQL in the Netlify functions. Prepared statements for authentication and ownership checks in PHP.
- Output escaping in the portal UI and in most PHP pages.
- Private KYC storage: Netlify Blobs, or a directory outside the PHP web root with random filenames.
- Same-origin enforcement on writes (Netlify); CSRF token plus session check on the assistant (PHP).
- `nosniff`, `X-Frame-Options: DENY` and a referrer policy from `netlify.toml`.

### Known issues and hardening backlog

Open items from the latest review. Fix the top group before real data is collected.

**Highest priority**

1. **Open sign-up with no caps (Netlify).** Anyone can register and choose a property with no approval step. There are no per-user limits on records or uploads, and the assistant rate limit is per account.
2. **No brute-force protection on PHP logins** (`admin_login.php`, `pg/login.php`, `Includes/session.php`), and a 6-character minimum on public registration.
3. **Default password.** An admin-created member without a password gets `password` (`HMS-2/admin/Members/admin_class.php`). Check the staff copy. The login code still accepts legacy plaintext passwords.
4. **CSRF.** Only the assistant endpoints have tokens in the PHP app. Admin actions rely on the browser's default SameSite behaviour.
5. **`HMS-2/hms.sql` must be scrubbed** of anything resembling real contact details, plaintext passwords and the admin hash shared with student rows before the repository is shared or the file is reused.

**Medium and low**

- No Content-Security-Policy; confirm HSTS with `curl -I`.
- PHP sessions have no idle timeout, and `SameSite` is not set explicitly.
- `.env.example` defaults (`APP_DEBUG=true`, `DB_USER=root`, empty password) are unsafe for production, and `APP_DEBUG` is not wired to error display. Check `display_errors` in the deployed `php.ini`.
- PHP KYC uploads are validated by extension only, and a rejected file is silently dropped while the page reports success (`pg/student/kyc.php`).
- `ucwords($row['username'])` is echoed unescaped in four billing views. The registration regex blocks exploitation today, but escape it anyway.
- No retention or deletion mechanism for KYC documents or visitor details.
- Netlify IDs are checked with `/^[a-f0-9-]{36}$/i`, which accepts non-UUIDs. They then fail in SQL and return a generic 503.
- `netlify/lib/auth.ts` `fail()` returns 503 for unexpected errors without logging them. Add logging.
- Confirm Netlify Identity settings: email confirmation required, password policy, and who can receive roles.

**Functional gaps**

- Approving a PG move-out does not release the bed (Netlify). The PHP "settle" action does.
- Room assignment does not require approved KYC (Netlify).
- The PG vacate form hardcodes `rent_pending = 0.00` and shows a 30-day policy that is not enforced (`pg/student/vacate.php`).
- Bills store the amount as text and have no payment date, method, audit trail, void or edit. "Print receipt" appears for unpaid bills.
- The PG portal still has a Mess menu module, but the public site says meals are not provided. Decide which is true.
- A profile's property cannot be corrected without editing the database.

## Deployment

**Netlify** (`netlify.toml`): build `npm run build`, publish `dist`, functions in `netlify/functions`. About 68 redirects map legacy `.php` URLs to portal sections, and `/portal/*` serves the SPA. Site-wide security headers are defined there.

**Legacy PHP:** see [Getting started: legacy PHP app](#getting-started-legacy-php-app). Always stage first, and test registration, role boundaries, KYC upload and download, billing, and persistence after a restart.

## Data handling rules

- Never commit `.env*` files (other than `.env.example`), uploaded documents, database dumps or backups.
- Never use real resident or student data in demos, screenshots or fixtures.
- Backups contain personal data. Keep them private and test restores.
- Screenshots in `images/screenshots/` are redacted and compressed (1600 px wide JPEG). Before adding a new one, check it for emails, names, phone numbers, IDs and API keys.

## Contributing

- Use parameterized queries. Never interpolate variables into SQL.
- Escape all dynamic output (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` in PHP; the `h()` helper in the portal).
- Guard every authenticated PHP page with `require_role()` or `require_pg_role()`. Add every new Netlify permission to `shared/model.ts` with a test.
- Use Conventional Commits (`feat:`, `fix:`, `docs:`, `security:`).
- Update this README when setup, commands or known issues change. See also [`CONTRIBUTING.md`](CONTRIBUTING.md).

## People

- **Property owners:** Binod Yadav and Sumit Yadav
- **Platform developer, Society admin and PG warden:** Yuvraj ([yuvrajyadav734@gmail.com](mailto:yuvrajyadav734@gmail.com))

## License

The project is intended to be MIT-licensed, but **no `LICENSE` file is currently committed**. Add one before publishing the repository.
