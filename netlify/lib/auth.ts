import { getUser } from '@netlify/identity';
export class HttpError extends Error { constructor(public status: number, message: string) { super(message); } }
export async function authenticate(req: Request) {
 if (!['GET','HEAD'].includes(req.method)) {
  const origin = req.headers.get('origin');
  if (!origin || origin !== new URL(req.url).origin) throw new HttpError(403, 'Request origin is not allowed.');
  if (Number(req.headers.get('content-length') || 0) > 4_500_000) throw new HttpError(413,'Upload must be smaller than 4 MB.');
 }
 const user = await getUser();
 if (!user) throw new HttpError(401,'Sign in to continue.');
 return user;
}
export function fail(error: unknown) {
 if (error instanceof HttpError) return Response.json({ error: error.message },{status:error.status,headers:{'Cache-Control':'no-store'}});
 if (error instanceof SyntaxError) return Response.json({ error: 'Invalid request body.' }, { status:400 });
 return Response.json({error:'The request could not be completed. Please try again.'},{status:503,headers:{'Cache-Control':'no-store'}});
}
export function ok(data: unknown, status = 200) { return Response.json(data,{status,headers:{'Cache-Control':'no-store'}}); }
