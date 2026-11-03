import { getDatabase } from '@netlify/database';
import { getStore } from '@netlify/blobs';
import { authenticate, fail, HttpError, ok } from '../lib/auth.js';
import { isManager, validProperty } from '../../shared/model.js';

export default async (req: Request) => {
 try {
  const user=await authenticate(req), db=getDatabase(), store=getStore('living-private-documents');
  const url=new URL(req.url), id=url.searchParams.get('id') || '';
  if (!/^[a-f0-9-]{36}$/i.test(id)) throw new HttpError(400,'Invalid document request.');
  const [record]=await db.sql`SELECT r.*,d.id AS document_id,d.blob_key,d.filename,d.content_type FROM living_records r LEFT JOIN living_documents d ON d.record_id=r.id WHERE r.id=${id}::uuid AND r.kind='kyc'`;
  if (!record || (record.owner_id!==user.id && !isManager(user.roles || [],validProperty(record.property)))) throw new HttpError(404,'Document not found.');
  if (req.method==='GET') {
   if (!record.blob_key) throw new HttpError(404,'No document has been uploaded.');
   const content=await store.get(record.blob_key,{type:'arrayBuffer'});
   if (!content) throw new HttpError(404,'Document not found.');
   return new Response(content,{headers:{'Content-Type':record.content_type,'Content-Disposition':`attachment; filename="${record.filename}"`,'Cache-Control':'private, no-store','X-Content-Type-Options':'nosniff'}});
  }
  if (req.method!=='POST') throw new HttpError(405,'Method not allowed.');
  if (record.owner_id!==user.id || record.status!=='Pending' || record.blob_key) throw new HttpError(409,'Documents can only be attached once to your pending submission.');
  const form=await req.formData(), file=form.get('document');
  if (!(file instanceof File) || file.size===0 || file.size>4*1024*1024) throw new HttpError(400,'Choose a PDF, PNG, or JPEG file up to 4 MB.');
  const bytes=await file.arrayBuffer(), head=new Uint8Array(bytes.slice(0,8));
  const type=head[0]===0x25 && head[1]===0x50 && head[2]===0x44 && head[3]===0x46 ? 'application/pdf' : head[0]===0xff && head[1]===0xd8 && head[2]===0xff ? 'image/jpeg' : head[0]===137 && head[1]===80 && head[2]===78 && head[3]===71 && head[4]===13 && head[5]===10 && head[6]===26 && head[7]===10 ? 'image/png' : '';
  if (!type || type!==file.type) throw new HttpError(400,'Only PDF, PNG, and JPEG documents are supported.');
  const docId=crypto.randomUUID(), key=`${user.id}/${docId}`, filename=file.name.replace(/[^A-Za-z0-9._-]/g,'_').slice(-100) || 'document';
  await store.set(key,bytes);
  try { await db.sql`INSERT INTO living_documents(id,record_id,owner_id,filename,content_type,blob_key) VALUES(${docId}::uuid,${id}::uuid,${user.id},${filename},${type},${key})`; }
  catch(e) { await store.delete(key); throw e; }
  return ok({uploaded:true},201);
 } catch(e) {return fail(e);}
};
export const config={path:'/api/documents'};
