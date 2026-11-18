/* SPDX-License-Identifier: AGPL-3.0-or-later */
(() => {
 'use strict';
 function init(){
  const root=document.getElementById('upload-rules-admin');if(!root)return;
  const $=id=>root.querySelector('#'+id), dialog=$('ur-dialog'),form=$('ur-editor');
  let policy=JSON.parse(root.dataset.policy),busy=false,editing=null,pickers={},dirty=false;
  const labels={users:new Map(),groups:new Map()};
  const el=(tag,text,cls)=>{const n=document.createElement(tag);if(text!==undefined)n.textContent=text;if(cls)n.className=cls;return n;};
  const button=(text,fn)=>{const n=el('button',text);n.type='button';n.addEventListener('click',fn);return n;};
  const show=(node,text,kind='')=>{node.textContent=text;node.dataset.kind=kind;};
  async function directory(kind,search='',offset=0,ids=[]){
   const url=new URL(root.dataset.directoryUrl,location.origin);url.searchParams.set('kind',kind);url.searchParams.set('search',search);url.searchParams.set('offset',offset);ids.forEach(id=>url.searchParams.append('ids[]',id));
   const response=await fetch(url,{credentials:'same-origin',cache:'no-store',headers:{requesttoken:OC.requestToken}});
   if(!response.ok)throw new Error('دریافت فهرست انجام نشد؛ دوباره جست‌وجو کنید.');
   const data=await response.json();data.items.forEach(item=>labels[kind].set(item.id,item.label+(item.missing?' (یافت نشد)':'')));return data;
  }
  const display=(kind,ids)=>ids.map(id=>labels[kind].get(id)||id).join('، ');
  async function resolveLabels(){
   const ids={users:new Set(),groups:new Set()};
   for(const r of policy.rules){r.groups.forEach(id=>ids.groups.add(id));[...r.users,...r.excludedUsers].forEach(id=>ids.users.add(id));}
   try {await Promise.all(Object.entries(ids).flatMap(([kind,set])=>{const all=[...set];const jobs=[];for(let i=0;i<all.length;i+=100)jobs.push(directory(kind,'',0,all.slice(i,i+100)));return jobs;}));render();}
   catch(error){show($('ur-status'),error.message,'error');}
  }
  function render(){
   $('ur-enabled').checked=policy.enabled;$('ur-list').replaceChildren();$('ur-empty').hidden=policy.rules.length>0;
   policy.rules.forEach((r,index)=>{
    const card=el('article',undefined,'ur-card'+(r.enabled?'':' off'));card.dataset.ruleId=r.id;
    const head=el('div',undefined,'ur-heading');head.append(el('h3',(index+1)+'. '+r.name),el('span',r.enabled?'فعال':'غیرفعال','ur-badge'));card.append(head);
    card.append(el('p',(r.extensions.includes('*')?'همهٔ پسوندها':r.extensions.join('، '))+' · '+(r.blocked?'آپلود ممنوع':r.maxMiB+' مگابایت')));
    card.append(el('p','مشمول: '+(r.audience==='all'?'همهٔ کاربران و مهمان‌ها':[(r.groups.length?'گروه‌ها: '+display('groups',r.groups):''),(r.users.length?'کاربران: '+display('users',r.users):'')].filter(Boolean).join(' | '))));
    card.append(el('p','افراد مستثنا: '+(r.excludedUsers.length?display('users',r.excludedUsers):'ندارد')));
    card.append(el('p','پیام: '+r.message));
    const actions=el('div',undefined,'ur-card-actions');
    actions.append(button('ویرایش',()=>open(r)),button(r.enabled?'غیرفعال کردن':'فعال کردن',()=>change(p=>{p.rules[index].enabled=!r.enabled;})));
    const up=button('↑ اولویت بالاتر',()=>change(p=>{[p.rules[index-1],p.rules[index]]=[p.rules[index],p.rules[index-1]];}));up.disabled=index===0;
    const down=button('↓ اولویت پایین‌تر',()=>change(p=>{[p.rules[index+1],p.rules[index]]=[p.rules[index],p.rules[index+1]];}));down.disabled=index===policy.rules.length-1;
    actions.append(up,down,button('حذف',()=>{if(confirm('قانون «'+r.name+'» حذف شود؟'))change(p=>{p.rules.splice(index,1);});}));card.append(actions);$('ur-list').append(card);
   });
  }
  async function save(next){
   if(busy)return false;busy=true;root.setAttribute('aria-busy','true');
   const controls=[...root.querySelectorAll('button,input,select,textarea')].map(n=>[n,n.disabled]);controls.forEach(([n])=>n.disabled=true);
   show(dialog.open?$('ur-editor-status'):$('ur-status'),'در حال ذخیره…');
   try{
    const response=await fetch(root.dataset.saveUrl,{method:'PUT',credentials:'same-origin',headers:{'Content-Type':'application/json',requesttoken:OC.requestToken},body:JSON.stringify({policy:next})});
    const result=await response.json();if(!response.ok)throw new Error(result.message||'ذخیره انجام نشد.');policy=result;render();show($('ur-status'),'تغییرات ذخیره شد و روی آپلودهای جدید اعمال می‌شود.','success');return true;
   }catch(error){show(dialog.open?$('ur-editor-status'):$('ur-status'),error.message||'ارتباط برقرار نشد.','error');$('ur-enabled').checked=policy.enabled;return false;}
   finally{busy=false;root.removeAttribute('aria-busy');controls.forEach(([n,disabled])=>n.disabled=disabled);}
  }
  async function change(fn){if(busy)return;const next=structuredClone(policy);fn(next);await save(next);}
  function picker(container,kind,title,initial){
   container.replaceChildren();container.className='ur-picker';const selected=new Set(initial);let generation=0,timer,offset=0;
   const label=el('label',title);const input=el('input');input.type='search';input.placeholder='جست‌وجو و انتخاب…';input.id=container.id+'-search';label.htmlFor=input.id;
   const chips=el('div',undefined,'ur-chips'),results=el('div',undefined,'ur-results'),info=el('small'),more=button('نمایش موارد بیشتر',()=>load(true));more.hidden=true;
   container.append(label,chips,input,results,info,more);
   function draw(){chips.replaceChildren();selected.forEach(id=>{const name=labels[kind].get(id)||id;const remove=button(name+' ×',()=>{selected.delete(id);dirty=true;draw();load();});remove.setAttribute('aria-label','حذف '+name+' از انتخاب‌ها');chips.append(remove);});}
   async function load(append=false){
    if(!append)offset=0;const version=++generation;const query=input.value;show(info,'در حال جست‌وجو…');more.hidden=true;
    try{const data=await directory(kind,query,offset);if(version!==generation||!container.isConnected)return;
     if(!append)results.replaceChildren();data.items.forEach(item=>{const b=button(item.label+' ('+item.id+')',()=>{if(selected.size>=100){show(info,'حداکثر ۱۰۰ انتخاب مجاز است.');return;}selected.add(item.id);dirty=true;draw();b.disabled=true;});b.disabled=selected.has(item.id);results.append(b);});
     offset+=25;more.hidden=!data.more;show(info,results.children.length?'برای انتخاب، روی نام کلیک کنید.':'موردی یافت نشد.');draw();
    }catch(error){if(version===generation)show(info,error.message);}
   }
   input.addEventListener('input',()=>{generation++;clearTimeout(timer);timer=setTimeout(()=>load(),250);});draw();load();
   return {values:()=>[...selected],dispose:()=>{generation++;clearTimeout(timer);}};
  }
  function open(rule){
   if(busy)return;if(!rule&&policy.rules.length>=200){show($('ur-status'),'حداکثر ۱۰۰ قانون مجاز است.','error');return;}
   Object.values(pickers).forEach(p=>p.dispose());editing=rule?.id||null;dirty=false;
   const r=rule||{name:'',extensions:[],blocked:false,maxMiB:300,message:'فایل شما بیشتر از حجم مجاز می باشد.',audience:'selected',groups:[],users:[],excludedUsers:[],enabled:true};
   $('ur-dialog-title').textContent=rule?'ویرایش قانون':'افزودن قانون جدید';$('ur-name').value=r.name;$('ur-extensions').value=r.extensions.join(', ');$('ur-mode').value=r.blocked?'block':'limit';$('ur-size').value=r.blocked?300:r.maxMiB;$('ur-size').disabled=r.blocked;$('ur-message').value=r.message;$('ur-audience').value=r.audience;$('ur-targets').hidden=r.audience==='all';$('ur-rule-enabled').checked=r.enabled;show($('ur-editor-status'),'');
   pickers={groups:picker($('ur-groups'),'groups','گروه‌های مشمول',r.groups),users:picker($('ur-users'),'users','کاربران مشمول',r.users),excludedUsers:picker($('ur-excluded'),'users','افراد مستثنا از این قانون',r.excludedUsers)};
   dialog.showModal();$('ur-name').focus();
  }
  function close(){if(busy)return;if(dirty&&!confirm('تغییرات ذخیره‌نشده کنار گذاشته شود؟'))return;Object.values(pickers).forEach(p=>p.dispose());dialog.close();dirty=false;}
  form.addEventListener('input',()=>dirty=true);$('ur-close').addEventListener('click',close);$('ur-cancel').addEventListener('click',close);dialog.addEventListener('cancel',event=>{event.preventDefault();close();});
  $('ur-mode').addEventListener('change',()=>{$('ur-size').disabled=$('ur-mode').value==='block';});$('ur-audience').addEventListener('change',()=>{$('ur-targets').hidden=$('ur-audience').value==='all';});
  $('ur-enabled').addEventListener('change',()=>change(p=>{p.enabled=$('ur-enabled').checked;}));$('ur-add').addEventListener('click',()=>open());
  form.addEventListener('submit',async event=>{
   event.preventDefault();if(busy||!form.reportValidity())return;
   const extensions=[...new Set($('ur-extensions').value.split(/[,،\s]+/).filter(Boolean).map(e=>e.replace(/^\.+/,'').toLowerCase()))];
   if(!extensions.length||extensions.some(e=>e!=='*'&&(!/^[a-z0-9]+(?:[._+-][a-z0-9]+)*$/.test(e)||e.length>40))){show($('ur-editor-status'),'پسوند معتبر مانند mp4، pdf یا * وارد کنید.','error');return;}
   const groups=pickers.groups.values(),users=pickers.users.values(),audience=$('ur-audience').value;
   if(audience==='selected'&&!groups.length&&!users.length){show($('ur-editor-status'),'حداقل یک گروه یا کاربر مشمول انتخاب کنید.','error');return;}
   const blocked=$('ur-mode').value==='block';
   const r={id:editing||'rule-'+Array.from(crypto.getRandomValues(new Uint8Array(12)),n=>n.toString(16).padStart(2,'0')).join(''),name:$('ur-name').value.trim(),enabled:$('ur-rule-enabled').checked,extensions,blocked,maxMiB:blocked?0:Number($('ur-size').value),message:$('ur-message').value.trim(),audience,groups,users,excludedUsers:pickers.excludedUsers.values()};
   const next=structuredClone(policy);if(editing)next.rules[next.rules.findIndex(x=>x.id===editing)]=r;else next.rules.unshift(r);
   if(await save(next)){dirty=false;dialog.close();Object.values(pickers).forEach(p=>p.dispose());}
  });
  window.addEventListener('beforeunload',event=>{if(dirty){event.preventDefault();event.returnValue='';}});
  render();resolveLabels();
 }
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
