import { getDatabase } from '@netlify/database';
import { authenticate, HttpError, fail, ok } from '../lib/auth.js';
import { modules, canCreate, canRead, cleanFields, isManager, isOffice, statuses, validProperty } from '../../shared/model.js';
import type { Property } from '../../shared/model.js';

export default async (req: Request) => {
 try {
  const user = await authenticate(req), roles = user.roles || [], db = getDatabase();
  const url = new URL(req.url), action = url.searchParams.get('action') || 'records';
  const property = validProperty(url.searchParams.get('property') || 'society');
  const manager = isManager(roles,property), office = isOffice(roles,property);
  const [profile] = await db.sql`SELECT * FROM living_profiles WHERE user_id=${user.id}`;
  if (action === 'profile') {
   if (req.method === 'GET') return ok({user:{id:user.id,email:user.email,name:user.name,roles}, profile:profile || null});
   if (req.method !== 'POST') throw new HttpError(405,'Method not allowed.');
   const body = await req.json();
   const name = String(body.full_name || '').trim(), phone = String(body.phone || '').trim(), unit = String(body.unit || '').trim();
   if (!name || name.length > 150 || phone.length > 30 || unit.length > 100) throw new HttpError(400,'Check your name, phone, and accommodation details.');
   const chosen = profile?.property || validProperty(body.property);
   const [saved] = await db.sql`INSERT INTO living_profiles(user_id,email,full_name,property,phone,unit) VALUES(${user.id},${user.email || ''},${name},${chosen},${phone},${unit}) ON CONFLICT(user_id) DO UPDATE SET full_name=EXCLUDED.full_name,phone=EXCLUDED.phone,unit=EXCLUDED.unit,email=EXCLUDED.email RETURNING *`;
   return ok(saved);
  }
  if (!profile) throw new HttpError(409,'Complete your profile first.');
  if (!manager && !(roles.includes('staff') && property === 'society') && profile.property !== property) throw new HttpError(403,'This account belongs to another property.');
  if (action === 'people') {
   if (!manager) throw new HttpError(403,'Administrator access required.');
   if (req.method !== 'GET') throw new HttpError(405,'Method not allowed.');
   return ok(await db.sql`SELECT p.*,a.room_id,a.bed,r.name AS room_name FROM living_profiles p LEFT JOIN living_assignments a ON a.user_id=p.user_id LEFT JOIN living_rooms r ON r.id=a.room_id WHERE p.property=${property} ORDER BY p.full_name LIMIT 1000`);
  }
  if (action === 'rooms') {
   if (property !== 'pg') throw new HttpError(400,'Rooms are available for PG accounts.');
   if (req.method === 'GET') {
    if (manager) return ok(await db.sql`SELECT r.*,COUNT(a.user_id)::integer AS occupied FROM living_rooms r LEFT JOIN living_assignments a ON a.room_id=r.id GROUP BY r.id ORDER BY r.name`);
    return ok(await db.sql`SELECT r.*,a.bed FROM living_rooms r JOIN living_assignments a ON a.room_id=r.id WHERE a.user_id=${user.id}`);
   }
   if (!manager) throw new HttpError(403,'Administrator access required.');
   const b = await req.json();
   if (req.method === 'POST') {
    const name=String(b.name || '').trim(), capacity=Number(b.capacity), rent=Number(b.monthly_rent);
    if (!name || name.length>80 || !Number.isInteger(capacity) || capacity<1 || capacity>20 || !Number.isFinite(rent) || rent<0 || rent>10000000) throw new HttpError(400,'Check room name, capacity (1–20), and rent.');
    const [room]=await db.sql`INSERT INTO living_rooms(name,capacity,monthly_rent) VALUES(${name},${capacity},${rent}) ON CONFLICT(name) DO NOTHING RETURNING *`;
    if (!room) throw new HttpError(409,'A room with this name already exists.');
    return ok(room,201);
   }
   if (req.method === 'PUT') {
    const id=String(b.room_id || ''), student=String(b.user_id || ''), bed=Number(b.bed);
    if (!/^[a-f0-9-]{36}$/i.test(id) || !student || !Number.isInteger(bed)) throw new HttpError(400,'Select a student, room, and bed.');
    try {
     const assigned=await db.sql`INSERT INTO living_assignments(user_id,room_id,bed) SELECT p.user_id,r.id,${bed} FROM living_profiles p CROSS JOIN living_rooms r WHERE p.user_id=${student} AND p.property='pg' AND r.id=${id}::uuid AND ${bed} BETWEEN 1 AND r.capacity ON CONFLICT(user_id) DO UPDATE SET room_id=EXCLUDED.room_id,bed=EXCLUDED.bed RETURNING *`;
     if (!assigned.length) throw new HttpError(400,'Student or bed is invalid.');
     return ok(assigned[0]);
    } catch(e) { if (e instanceof HttpError) throw e; if ((e as {code?:string}).code==='23505') throw new HttpError(409,'This bed is already occupied.'); throw e; }
   }
   if (req.method === 'DELETE') { await db.sql`DELETE FROM living_assignments WHERE user_id=${String(b.user_id || '')}`; return ok({deleted:true}); }
   throw new HttpError(405,'Method not allowed.');
  }
  if (action !== 'records') throw new HttpError(404,'Unknown endpoint.');
  const kind = url.searchParams.get('kind') || 'announcements', module = modules[kind];
  if (!module || (module.properties && !module.properties.includes(property))) throw new HttpError(404,'Section not found.');
  if (module.audience === 'office' && !office) throw new HttpError(403,'Staff access required.');
  if (req.method === 'GET') {
   const all = manager || module.audience === 'public' || (module.audience === 'office' && office);
   return ok(await db.sql`SELECT r.*,p.full_name AS owner_name,d.id AS document_id FROM living_records r JOIN living_profiles p ON p.user_id=r.owner_id LEFT JOIN living_documents d ON d.record_id=r.id WHERE r.property=${property} AND r.kind=${kind} AND (${all} OR r.owner_id=${user.id}) ORDER BY r.created_at DESC LIMIT 500`);
  }
  const body = await req.json();
  if (req.method === 'POST') {
   if (!canCreate(kind,roles,property)) throw new HttpError(403,'You cannot create records in this section.');
   let data;
   try { data=cleanFields(kind,body.data); } catch(e) { throw new HttpError(400,(e as Error).message); }
   const owner=kind==='bills' ? String(body.owner_id || '') : user.id;
   const [target]=await db.sql`SELECT user_id FROM living_profiles WHERE user_id=${owner} AND property=${property}`;
   if (!target && owner!==user.id) throw new HttpError(400,'Choose an account in this property.');
   const [record]=await db.sql`INSERT INTO living_records(property,kind,owner_id,data,status) VALUES(${property},${kind},${owner},${JSON.stringify(data)}::jsonb,${statuses(kind)[0]}) RETURNING *`;
   return ok(record,201);
  }
  const id=String(body.id || '');
  if (!/^[a-f0-9-]{36}$/i.test(id)) throw new HttpError(400,'Invalid record.');
  const [record]=await db.sql`SELECT * FROM living_records WHERE id=${id}::uuid AND property=${property} AND kind=${kind}`;
  if (!record || !canRead(kind,roles,property,user.id,record.owner_id)) throw new HttpError(404,'Record not found.');
  if (req.method === 'PATCH') {
   if (!manager && !(kind==='visitors' && office)) throw new HttpError(403,'Administrator access required.');
   const status=String(body.status || ''), response=String(body.response || '').trim();
   if (!statuses(kind).includes(status) || response.length>2000) throw new HttpError(400,'Check the status or response.');
   const [updated]=await db.sql`UPDATE living_records SET status=${status},response=${response},updated_at=now() WHERE id=${id}::uuid RETURNING *`;
   return ok(updated);
  }
  if (req.method === 'DELETE') {
   if (!manager || !['announcements','events','menu','services','neighbourhood'].includes(kind)) throw new HttpError(403,'This record cannot be deleted.');
   await db.sql`DELETE FROM living_records WHERE id=${id}::uuid`;
   return ok({deleted:true});
  }
  throw new HttpError(405,'Method not allowed.');
 } catch(e) { if (e instanceof Error && e.message==='Choose a property.') return fail(new HttpError(400,e.message)); return fail(e); }
};
export const config = { path: '/api/portal' };
