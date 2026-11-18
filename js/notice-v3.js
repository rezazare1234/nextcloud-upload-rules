/* SPDX-License-Identifier: AGPL-3.0-or-later
 * Nextcloud 34 adapter: picker/batch uploads and both native drop handlers.
 * Server DAV enforcement is independent of this preflight UI.
 */
(() => {
 'use strict';
 const state=document.getElementById('initial-state-upload_size_notice-config');
 if (!state) return;
 const config=JSON.parse(new TextDecoder().decode(Uint8Array.from(atob(state.value),c=>c.charCodeAt(0))));
 const engine=globalThis.UploadSizeNoticePolicy;
 const wrapped=new WeakSet(), replayedDrops=new WeakSet();
 function notify(message) {
  let notice=document.getElementById('upload-size-notice');
  if (notice) {notice.querySelector('span').textContent=message;return;}
  notice=document.createElement('div');notice.id='upload-size-notice';
  notice.setAttribute('role','alert');notice.setAttribute('dir','rtl');notice.setAttribute('lang','fa');
  const text=document.createElement('span');text.textContent=message;
  const close=document.createElement('button');close.type='button';close.textContent='×';close.setAttribute('aria-label','بستن');
  close.addEventListener('click',()=>notice.remove());notice.append(text,close);document.body.append(notice);
 }
 async function refreshPolicy() {
  const abort=new AbortController();const timeout=setTimeout(()=>abort.abort(),10000);
  try {
   const response=await fetch(config.url,{credentials:'same-origin',cache:'no-store',signal:abort.signal});
   if(!response.ok)throw new Error('Cannot load upload policy');
   const policy=await response.json();
   if(typeof policy.enabled!=='boolean'||!Array.isArray(policy.rules))throw new Error('Invalid upload policy');
   return policy;
  } catch(error) {
   notify('دریافت قوانین آپلود انجام نشد. لطفاً دوباره تلاش کنید.');throw error;
  } finally {clearTimeout(timeout);}
 }
 function acceptable(policy,file) {
  if(typeof file.size!=='number')return true;
  const issue=engine.violation(policy,file.name,file.size);
  if(issue){notify(issue.message);return false;}return true;
 }
 async function allowed(nodes,policy) {
  const accepted=[];
  for(const node of nodes){
   let file=node;
   if(node.isFile===true&&typeof node.file==='function')file=await new Promise((resolve,reject)=>node.file(resolve,reject));
   if(acceptable(policy,file))accepted.push(node);
  }
  return accepted;
 }
 function install(uploader) {
  if(!uploader||wrapped.has(uploader)||typeof uploader.batchUpload!=='function')return uploader;
  wrapped.add(uploader);const original=uploader.batchUpload;
  uploader.batchUpload=function(destination,files,callback){
   let policyPromise;
   const filteredCallback=async(nodes,path)=>{
    let policy;
    try{policy=await(policyPromise??=refreshPolicy());}catch{return [];}
    const accepted=await allowed(nodes,policy);if(!accepted.length)return [];
    const selected=callback?await callback(accepted,path):accepted;
    return selected===false?false:allowed(selected,policy);
   };
   return original.call(this,destination,files,filteredCallback);
  };
  return uploader;
 }
 function fileEntry(file) {
  return {name:file.name,fullPath:'/'+file.name,isFile:true,isDirectory:false,
   cachedFile:file,file:resolve=>resolve(file)};
 }
 async function filterEntry(entry,policy) {
  if(entry.isFile){
   const file=await new Promise((resolve,reject)=>entry.file(resolve,reject));
   return acceptable(policy,file)?fileEntry(file):null;
  }
  const reader=entry.createReader(),children=[];
  while(true){
   const batch=await new Promise((resolve,reject)=>reader.readEntries(resolve,reject));
   if(!batch.length)break;children.push(...batch);
  }
  const accepted=[];
  for(const child of children){const filtered=await filterEntry(child,policy);if(filtered)accepted.push(filtered);}
  if(children.length&&!accepted.length)return null;
  return {name:entry.name,fullPath:entry.fullPath,isFile:false,isDirectory:true,
   createReader(){let read=false;return {readEntries(resolve){const result=read?[]:accepted;read=true;resolve(result);}};}};
 }
 document.addEventListener('drop',event=>{
  if(replayedDrops.has(event)||!event.dataTransfer||!Array.from(event.dataTransfer.types).includes('Files'))return;
  // Capture before the first async yield; native dataTransfer becomes protected.
  const captured=Array.from(event.dataTransfer.items).filter(item=>item.kind==='file')
   .map(item=>({entry:item.getAsEntry?.()??item.webkitGetAsEntry?.(),file:item.getAsFile()}));
  if(!captured.length)return;
  event.preventDefault();event.stopImmediatePropagation();const target=event.target;
  const options={bubbles:true,cancelable:true,composed:true,button:event.button,
   clientX:event.clientX,clientY:event.clientY,screenX:event.screenX,screenY:event.screenY,
   ctrlKey:event.ctrlKey,altKey:event.altKey,shiftKey:event.shiftKey,metaKey:event.metaKey};
  (async()=>{
   let policy;try{policy=await refreshPolicy();}catch{return;}
   const entries=[];
   for(const item of captured){
    const original=item.entry??(item.file?fileEntry(item.file):null);if(!original)continue;
    try{const filtered=await filterEntry(original,policy);if(filtered)entries.push(filtered);}
    catch(error){console.warn('Upload rule could not read entry; using server validation',error);entries.push(original);}
   }
   if(!entries.length){
    // Let native drag UI reset even when the entire selection was rejected.
    target.dispatchEvent(new DragEvent('dragleave',{bubbles:true}));return;
   }
   const items=entries.map(entry=>({kind:'file',type:entry.cachedFile?.type||'',
    getAsEntry:()=>entry,webkitGetAsEntry:()=>entry,getAsFile:()=>entry.cachedFile||null}));
   const transfer={items,types:['Files'],files:entries.filter(e=>e.cachedFile).map(e=>e.cachedFile),
    dropEffect:'copy',effectAllowed:'all',getData:()=>'',setData(){},clearData(){}};
   const replay=new DragEvent('drop',options);Object.defineProperty(replay,'dataTransfer',{value:transfer});
   replayedDrops.add(replay);target.dispatchEvent(replay);
  })().catch(error=>{console.error('Upload preflight failed',error);notify('بررسی فایل انجام نشد. لطفاً دوباره تلاش کنید.');});
 },true);
 const descriptor=Object.getOwnPropertyDescriptor(window,'_nc_uploader');
 if(!descriptor||(descriptor.configurable&&'value' in descriptor)){
  let current=install(window._nc_uploader);
  Object.defineProperty(window,'_nc_uploader',{configurable:true,enumerable:descriptor?descriptor.enumerable:true,
   get:()=>current,set:value=>{current=install(value);}});
 }else{install(window._nc_uploader);}
})();
