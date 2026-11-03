# Yadav Living on Netlify

The live application now uses the HTML homepage and TypeScript portal in `web/`, Netlify Functions, Netlify Identity, Netlify Database (Postgres), and private Netlify Blobs documents. The original PHP/Docker application remains in the repository as a reference; it is not published or executed on Netlify.

## Deployment

The repository's `netlify.toml` configures the build and the `dist` publish directory. Netlify installs the locked npm dependencies, builds the two frontend entry points, packages the functions, and applies migrations in `netlify/database/migrations` before publishing. Do not publish the repository root: it contains PHP source and a legacy database dump.

Identity has been enabled for this site. Confirm the deployment finishes successfully in Netlify before using the application. Build validation and publishing are handled by the hosting workflow.

## First administrator

1. Open Netlify's project dashboard and choose Identity.
2. Invite the administrator's email address. Accept the emailed invitation and choose a password on the portal.
3. Open that user in Identity, add `admin` to their Roles, and save.
4. Sign out and sign in again to refresh the role. Administrators can switch between Society and PG inside the portal.

Public registration creates an ordinary account. A user cannot assign themselves administrator or security staff permissions. Give security personnel the `staff` role in Identity; staff can manage the Society visitor register. Keep privileged role changes restricted to trusted project administrators.

## Accounts and existing data

New residents and students choose Society or Student PG at signup, confirm their email, and sign in. Their property association is fixed when their database profile is created. Administrators issue bills and payment records to an account after the account has opened the portal at least once.

The new database starts empty. The checked-in SQL dump is not treated as verified production data and is not imported. Old MySQL passwords and PHP sessions do not authenticate with Netlify Identity. Existing users must create or accept an invitation for a new account. Existing financial history and documents need a separately verified data import before this deployment replaces a populated production system; do not assume those records have been migrated.

## Portal workflows

Residents and students can update their profile, read property announcements and events, browse services and neighbourhood information, submit complaints and move-out notices, upload KYC documents, and view their own bills and payment records. PG users also see the mess menu and room information. The assistant retrieves relevant curated property-guide excerpts and sends those excerpts and the question to Netlify AI Gateway; it does not query private records or retain conversations.

Administrators can add, edit, delete, and update the status of property records, review KYC documents, issue bills, record payments, view registered accounts, and assign/release PG beds. Each `Rooms & beds` record represents one bookable bed with a unique human-readable room/bed label. Database constraints prevent assigning a bed twice or giving a student multiple beds. Occupied beds cannot be placed into maintenance until released.

Bills are management-entered invoices with an amount and charge description. Payments are management-entered records; this application does not collect online payments or automatically reconcile balances. Printing creates a copy of the selected record, with its actual status shown.

KYC accepts PDF, JPEG, and PNG documents up to 4 MB. Documents are stored in Netlify Blobs and downloaded only through an authenticated endpoint that verifies ownership or the administrator role. Private records are filtered by both property and ownership. The SQL dump, PHP source, and original upload directories are excluded by the dedicated frontend publish directory.

## Development and verification

Use `npm ci`, then `netlify dev --port 8889`. Run `npm run check` and `npm test`. Browser checks use `npx playwright test` against that development server; install Chromium with `npx playwright install chromium` if needed.

Schema definitions live in `db/schema.ts`. Generate a migration after schema changes using `npx drizzle-kit generate --name describe_the_change`. Netlify applies migrations during deployment; do not apply schema changes manually.

Local checks cover TypeScript, validation, role permissions, anonymous API/document rejection, cross-origin mutation rejection, desktop/mobile public navigation, and legacy redirects. Authenticated database writes, invitation/email delivery, file storage, and AI inference require validation on the deployed site with real test accounts after migrations have run.
