import { defineConfig } from 'vite';
import { resolve } from 'node:path';
export default defineConfig({root:'web',plugins:[{name:'portal-route',configureServer(server){server.middlewares.use((req,_res,next)=>{if(req.url?.split('?')[0]==='/portal')req.url=req.url.replace('/portal','/portal.html');next();});}}],build:{outDir:'../dist',emptyOutDir:true,rollupOptions:{input:{home:resolve('web/index.html'),portal:resolve('web/portal.html')}}}});
