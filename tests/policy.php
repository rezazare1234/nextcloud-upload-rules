<?php
declare(strict_types=1);
require __DIR__.'/../lib/Service/Policy.php';
use OCA\UploadSizeNotice\Service\Policy;
$n=0;
function check(bool $ok,string $name): void { global $n; if (!$ok) throw new RuntimeException($name); $n++; }
$p=Policy::defaults();$base=$p['rules'][0];
check($base['extensions']===['*'] && $base['maxMiB']===300,'migration default');
$special=$base; $special['id']='test';$special['extensions']=['.MP4','mkv'];$special['maxMiB']=1;$special['message']='custom {filename} {limit} {extension}';$special['audience']='selected';$special['groups']=['staff'];$special['users']=['direct'];$special['excludedUsers']=['excluded'];
$p['rules']=[$special];$p=Policy::validate($p);
foreach ([['member',['staff'],true],['direct',[],true],['other',[],false],['excluded',['staff'],false],[null,[],false]] as [$uid,$groups,$denied]) {
 $effective=Policy::forActor($p,$uid,$groups);
 check((Policy::violation($effective,'video.MP4',1048577)!==null)===$denied,'actor '.($uid??'guest'));
 check(Policy::violation($effective,'video.mp4',1048576)===null,'boundary');
 check(Policy::violation($effective,'video.pdf',1048577)===null,'unmatched file');
 foreach ($effective['rules'] as $r) check(!isset($r['users'])&&!isset($r['groups'])&&!isset($r['excludedUsers']),'no identity leak');
}
$effective=Policy::forActor($p,'direct',[]);
check(Policy::violation($effective,'video.mp4',1048577)['message']==='custom video.mp4 1 mp4','message placeholders');
$p['rules'][]=$base;
check(Policy::rule(Policy::forActor($p,'direct',[]),'x.mp4')['maxMiB']===1,'first rule');
check(Policy::rule(Policy::forActor($p,'excluded',['staff']),'x.mp4')['maxMiB']===300,'exception next rule');
$p['rules'][0]['enabled']=false;
check(Policy::rule(Policy::forActor($p,'direct',[]),'x.mp4')['maxMiB']===300,'disabled skipped');
$p['rules'][0]['enabled']=true;$p['rules'][0]['blocked']=true;
check(Policy::violation(Policy::forActor($p,'direct',[]),'x.mp4',0)['status']===403,'blocked empty file');
$p['enabled']=false;check(Policy::violation(Policy::forActor($p,'direct',[]),'x.mp4',999999999)===null,'master disabled');
$old=['enabled'=>true,'defaultMiB'=>300,'message'=>'limit','blockedMessage'=>'blocked','rules'=>[['extension'=>'gz','blocked'=>false,'maxMiB'=>2],['extension'=>'tar.gz','blocked'=>false,'maxMiB'=>3]]];
check(Policy::rule(Policy::forActor(Policy::validate($old),null,[]),'a.tar.gz')['maxMiB']===3,'migration longest extension preserved');
foreach (['empty-selected','bad-size','duplicate-id','bad-extension','bad-message'] as $bad) {
 $q=Policy::defaults();
 switch($bad){case 'empty-selected':$q['rules'][0]['audience']='selected';break;case 'bad-size':$q['rules'][0]['maxMiB']=-1;break;case 'duplicate-id':$q['rules'][]=$q['rules'][0];break;case 'bad-extension':$q['rules'][0]['extensions']=['<script>'];break;case 'bad-message':$q['rules'][0]['message']='';break;}
 try { Policy::validate($q);throw new RuntimeException('accepted '.$bad); } catch (InvalidArgumentException) { $n++; }
}
$old['rules']=[];
for ($i=0;$i<100;$i++) $old['rules'][]=['extension'=>'ext'.$i,'blocked'=>false,'maxMiB'=>1];
$large=Policy::validate($old);
check(count(Policy::validate($large)['rules'])===101,'maximum legacy policy can be saved and reloaded');
echo "$n policy assertions passed\n";
