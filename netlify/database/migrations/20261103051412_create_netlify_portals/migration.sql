CREATE TABLE "assignments" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid(),
	"bed_id" uuid NOT NULL,
	"student_id" text NOT NULL
);
--> statement-breakpoint
CREATE TABLE "profiles" (
	"id" text PRIMARY KEY,
	"email" text NOT NULL,
	"name" text NOT NULL,
	"property" text NOT NULL,
	"details" jsonb DEFAULT '{}' NOT NULL,
	"created_at" timestamp DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "records" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid(),
	"kind" text NOT NULL,
	"property" text NOT NULL,
	"owner_id" text,
	"data" jsonb NOT NULL,
	"status" text DEFAULT 'pending' NOT NULL,
	"created_at" timestamp DEFAULT now() NOT NULL,
	"updated_at" timestamp DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE UNIQUE INDEX "one_assignment_per_bed" ON "assignments" ("bed_id");--> statement-breakpoint
CREATE UNIQUE INDEX "one_bed_per_student" ON "assignments" ("student_id");--> statement-breakpoint
ALTER TABLE "assignments" ADD CONSTRAINT "assignments_bed_id_records_id_fkey" FOREIGN KEY ("bed_id") REFERENCES "records"("id");--> statement-breakpoint
ALTER TABLE "assignments" ADD CONSTRAINT "assignments_student_id_profiles_id_fkey" FOREIGN KEY ("student_id") REFERENCES "profiles"("id");--> statement-breakpoint
ALTER TABLE "records" ADD CONSTRAINT "records_owner_id_profiles_id_fkey" FOREIGN KEY ("owner_id") REFERENCES "profiles"("id");