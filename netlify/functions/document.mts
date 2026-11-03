import {getUser} from '@netlify/identity';
import {getStore} from '@netlify/blobs';
import {eq} from 'drizzle-orm';
import {database} from '../../db/index.js';
import {records} from '../../db/schema.js';
export default async(req:Request)=>{
 try{
  if(req.method!=='GET')return new Response('Method not allowed',{status:405});
  const user=await getUser();if(!user)return new Response('Sign in required',{status:401});
  const id=new URL(req.url).searchParams.get('id')||'';if(!/^[0-9a-f-]{36}$/i.test(id))return new Response('Invalid document',{status:400});
  const [record]=await database().select().from(records).where(eq(records.id,id));
  if(!record||record.kind!=='kyc'||(!user.roles?.includes('admin')&&record.ownerId!==user.id))return new Response('Not found',{status:404});
  const file=await getStore('private-kyc').get(record.data.fileKey,{type:'arrayBuffer'});if(!file)return new Response('Not found',{status:404});
  return new Response(file,{headers:{'Content-Type':record.data.fileType,'Content-Disposition':'attachment; filename="verification-document"','Cache-Control':'no-store','X-Content-Type-Options':'nosniff'}});
 }catch{return new Response('Document temporarily unavailable',{status:503});}
};
