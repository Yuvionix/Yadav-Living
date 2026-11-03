CREATE TABLE living_profiles (
 user_id TEXT PRIMARY KEY,
 email TEXT NOT NULL,
 full_name TEXT NOT NULL,
 property TEXT NOT NULL CHECK (property IN ('society','pg')),
 phone TEXT NOT NULL DEFAULT '',
 unit TEXT NOT NULL DEFAULT '',
 created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE TABLE living_records (
 id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
 property TEXT NOT NULL CHECK (property IN ('society','pg')),
 kind TEXT NOT NULL CHECK (kind IN ('announcements','events','services','neighbourhood','menu','complaints','kyc','vacate','visitors','bills')),
 owner_id TEXT NOT NULL REFERENCES living_profiles(user_id),
 data JSONB NOT NULL,
 status TEXT NOT NULL,
 response TEXT NOT NULL DEFAULT '',
 created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
 updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX living_records_scope ON living_records(property,kind,owner_id,created_at DESC);
CREATE TABLE living_rooms (
 id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
 name TEXT NOT NULL UNIQUE,
 capacity INTEGER NOT NULL CHECK (capacity BETWEEN 1 AND 20),
 monthly_rent NUMERIC(12,2) NOT NULL CHECK (monthly_rent >= 0)
);
CREATE TABLE living_assignments (
 user_id TEXT PRIMARY KEY REFERENCES living_profiles(user_id),
 room_id UUID NOT NULL REFERENCES living_rooms(id) ON DELETE RESTRICT,
 bed INTEGER NOT NULL CHECK (bed BETWEEN 1 AND 20),
 UNIQUE(room_id,bed)
);
CREATE TABLE living_documents (
 id UUID PRIMARY KEY,
 record_id UUID NOT NULL UNIQUE REFERENCES living_records(id) ON DELETE CASCADE,
 owner_id TEXT NOT NULL REFERENCES living_profiles(user_id),
 filename TEXT NOT NULL,
 content_type TEXT NOT NULL,
 blob_key TEXT NOT NULL UNIQUE,
 created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE TABLE living_ai_limits (
 user_id TEXT NOT NULL,
 minute TIMESTAMPTZ NOT NULL,
 requests INTEGER NOT NULL DEFAULT 1,
 PRIMARY KEY(user_id,minute)
);
