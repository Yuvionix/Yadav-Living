import {pgTable, text, timestamp, jsonb, uuid, uniqueIndex} from 'drizzle-orm/pg-core';
export const profiles = pgTable('profiles', {
 id:text().primaryKey(),email:text().notNull(),name:text().notNull(),property:text().notNull(),
 details:jsonb().$type<Record<string,string>>().notNull().default({}),createdAt:timestamp('created_at').defaultNow().notNull()
});
export const records=pgTable('records',{
 id:uuid().defaultRandom().primaryKey(),kind:text().notNull(),property:text().notNull(),ownerId:text('owner_id').references(()=>profiles.id),
 data:jsonb().$type<Record<string,string>>().notNull(),status:text().notNull().default('pending'),
 createdAt:timestamp('created_at').defaultNow().notNull(),updatedAt:timestamp('updated_at').defaultNow().notNull()
});
export const assignments=pgTable('assignments',{
 id:uuid().defaultRandom().primaryKey(),bedId:uuid('bed_id').notNull().references(()=>records.id),studentId:text('student_id').notNull().references(()=>profiles.id)
},t=>[uniqueIndex('one_assignment_per_bed').on(t.bedId),uniqueIndex('one_bed_per_student').on(t.studentId)]);
