/* SPDX-License-Identifier: AGPL-3.0-or-later */
(() => {
 'use strict';
 const ruleFor=(policy,filename)=>{
  const name=filename.split(/[\\/]/).pop().toLowerCase();
  for(const rule of policy.rules) for(const extension of rule.extensions) {
   if(extension==='*'||name.endsWith('.'+extension)) return {...rule,extension,maxBytes:Math.round(rule.maxMiB*1048576)};
  }
  return null;
 };
 const violation=(policy,filename,bytes)=>{
  if(!policy.enabled)return null;
  const rule=ruleFor(policy,filename);
  if(!rule||(!rule.blocked&&bytes<=rule.maxBytes))return null;
  const values={filename:filename.split(/[\\/]/).pop(),limit:String(rule.maxMiB),extension:rule.extension};
  return {message:rule.message.replace(/\{(filename|limit|extension)\}/g,(_,key)=>values[key]),status:rule.blocked?403:413};
 };
 globalThis.UploadSizeNoticePolicy=Object.freeze({ruleFor,violation});
})();
