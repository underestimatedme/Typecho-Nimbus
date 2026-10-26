<?php
/** Real HTTP collector regression; all writes go to a disposable Typecho/SQLite clone. */
declare(strict_types=1);
$root = dirname(__DIR__);
$source = $root . '/.dev/typecho';
if (!is_file($source . '/config.inc.php')) { fwrite(STDERR, "Run tests/dev-site.sh first\n"); exit(1); }
$site = sys_get_temp_dir() . '/nimbus-growth-' . bin2hex(random_bytes(4));
mkdir($site . '/usr/themes', 0777, true); mkdir($site . '/usr/plugins');
foreach (['var','index.php'] as $f) symlink($source . '/' . $f, $site . '/' . $f);
// Admin common.php resolves config relative to __DIR__; copy it so login cannot touch the source DB.
$adminFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source . '/admin', FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
mkdir($site . '/admin');
foreach ($adminFiles as $file) {
 $target = $site . '/admin/' . $adminFiles->getSubPathName();
 if ($file->isDir()) mkdir($target); else copy($file->getPathname(), $target);
}
symlink($root . '/Nimbus', $site . '/usr/themes/Nimbus');
symlink($root . '/NimbusKit', $site . '/usr/plugins/NimbusKit');
copy($source . '/usr/nimbus.db', $site . '/usr/nimbus.db');
file_put_contents($site . '/config.inc.php', str_replace($source . '/usr/nimbus.db', $site . '/usr/nimbus.db', file_get_contents($source . '/config.inc.php')));
$db = new PDO('sqlite:' . $site . '/usr/nimbus.db'); $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sock = stream_socket_server('tcp://127.0.0.1:0'); $port = (int)substr(strrchr(stream_socket_get_name($sock, false), ':'), 1); fclose($sock);
$base = 'http://127.0.0.1:' . $port;
$db->prepare("UPDATE typecho_options SET value=? WHERE name='siteUrl' AND user=0")->execute([$base]);
$db->exec('DROP TABLE IF EXISTS typecho_nimbus_growth');
$config = ['analyticsEnable'=>'1','analyticsGoals'=>"code|Source|https://github.com/example/theme\nintro|Intro|/intro.html",'features'=>['views','likes','search','ajaxComment','sitemap']];
// Exercise the real config upgrade hook in a new PHP process, without web/admin credentials.
$enable = $site . '/enable.php';
file_put_contents($enable, '<?php require ' . var_export($site . '/config.inc.php', true) . '; $_SERVER["HTTP_HOST"]=' . var_export('127.0.0.1:'.$port,true) . '; $_SERVER["REQUEST_URI"]="/"; \\Widget\\Init::alloc(); \\TypechoPlugin\\NimbusKit\\Plugin::configHandle(' . var_export($config, true) . ', false);');
file_put_contents($enable, '
$tdb=\\Typecho\\Db::get(); $tdb->query($tdb->update("table.users")->rows(["password"=>\\Typecho\\Common::hash("growth-test")])->where("name = ?", "admin"));', FILE_APPEND);
$server = null; $fail=0;
function check_growth(bool $ok,string $name):void { global $fail; echo ($ok?'ok ':'FAIL ').$name."\n"; if(!$ok)$fail++; }
function growth_request(array $data=[],array $headers=[],string $jar='anonymous',string $method='POST'):array {
 global $base,$site;
 $args=['curl','--silent','--show-error','--max-time','10','-D',$site.'/headers','-o',$site.'/body','-w','%{http_code}','-c',$site.'/'.$jar.'.cookies','-b',$site.'/'.$jar.'.cookies','-X',$method,'-H','Origin: '.$base];
 foreach($headers as $h)$args=array_merge($args,['-H',$h]);
 if($method==='POST')$args=array_merge($args,['--data',http_build_query($data)]);
 $args[]=$base.'/index.php/nimbus-api/growth';
 $p=proc_open($args,[['pipe','r'],['pipe','w'],['pipe','w']],$pipes); fclose($pipes[0]);$status=(int)stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);proc_close($p);
 return [$status,json_decode((string)file_get_contents($site.'/body'),true),(string)file_get_contents($site.'/headers'),$err];
}
function growth_login(): void {
 global $base,$site;
 $jar=$site.'/admin.cookies';
 $request=function($url,$data=null)use($jar,$base){
  $args=['curl','--silent','--show-error','--max-time','10','-c',$jar,'-b',$jar];
  if($data!==null)$args=array_merge($args,['--referer',$base.'/admin/login.php','--data',http_build_query($data)]);
  $args[]=$url;$proc=proc_open($args,[['pipe','r'],['pipe','w'],['pipe','w']],$pipes);fclose($pipes[0]);$body=stream_get_contents($pipes[1]);fclose($pipes[1]);fclose($pipes[2]);proc_close($proc);return $body;
 };
 $login=$request($base.'/admin/login.php');
 if(!preg_match('/<form[^>]*action="([^"]+)"/',$login,$m))throw new RuntimeException('local login form missing');
 $url=html_entity_decode($m[1],ENT_QUOTES,'UTF-8');if(strpos($url,'http')!==0)$url=$base.'/'.ltrim($url,'/');
 $request($url,['name'=>'admin','password'=>'growth-test','referer'=>$base.'/admin/']);
}
try {
 $proc=proc_open([PHP_BINARY,'-d','error_reporting=8191',$enable],[['pipe','r'],['file',$site.'/enable.log','a'],['file',$site.'/enable.log','a']],$pipes); foreach($pipes as $pipe)if(is_resource($pipe))fclose($pipe);$exit=proc_close($proc);
 check_growth($exit===0,'saving opt-in config upgrades an old database');
 $table=$db->query("SELECT name FROM sqlite_master WHERE name='typecho_nimbus_growth'")->fetchColumn();check_growth((bool)$table,'upgrade creates aggregate table');
 if(!$table){echo file_get_contents($site.'/enable.log');throw new RuntimeException('upgrade missing');}
 $server=proc_open([PHP_BINARY,'-d','error_reporting=8191','-S','127.0.0.1:'.$port,'-t',$site,$root.'/tests/router.php'],[['pipe','r'],['file',$site.'/server.log','a'],['file',$site.'/server.log','a']],$pipes);
 for($i=0;$i<40;$i++){if($s=@fsockopen('127.0.0.1',$port)){fclose($s);break;}usleep(50000);}
 $r=growth_request(['kind'=>'entry','utm'=>'juejin','referrer'=>'']);check_growth($r[0]===200&&($r[1]['data']['counted']??false),'first attributed entry counts');
 $r=growth_request(['kind'=>'entry','utm'=>'github']);check_growth(($r[1]['data']['counted']??true)===false,'refresh does not count another arrival within cookie window');
 $r=growth_request(['kind'=>'goal','goal'=>'code','utm'=>'github']);check_growth(($r[1]['data']['source']??'')==='juejin','internal navigation and later campaigns preserve first source');
 check_growth((int)$db->query("SELECT hits FROM typecho_nimbus_growth WHERE kind='goal' AND goal='code' AND source='juejin'")->fetchColumn()===1,'configured goal is stored under the arrival source');
 $r=growth_request(['kind'=>'goal','goal'=>'code']);check_growth(($r[1]['data']['counted']??true)===false,'repeat goal clicks are deduplicated');
 $r=growth_request(['kind'=>'goal','goal'=>'unconfigured']);check_growth($r[0]===404,'unconfigured goals are rejected');
 $entryBefore=(int)$db->query("SELECT SUM(hits) FROM typecho_nimbus_growth WHERE kind='entry'")->fetchColumn();
 $r=growth_request(['kind'=>'goal','goal'=>'intro','utm'=>'github'],[],'goal-first');
 check_growth($r[0]===200&&$entryBefore===(int)$db->query("SELECT SUM(hits) FROM typecho_nimbus_growth WHERE kind='entry'")->fetchColumn(),'a goal arriving before its entry request does not fabricate another arrival');
 $r=growth_request(['kind'=>'entry'],[],'goal-first');
 check_growth(($r[1]['data']['counted']??false)===true&&($r[1]['data']['source']??'')==='github','a later entry still counts once under the source saved by the first goal');
 $before=(int)$db->query('SELECT SUM(hits) FROM typecho_nimbus_growth')->fetchColumn();
 foreach(['DNT: 1','Sec-GPC: 1'] as $i=>$header){$r=growth_request(['kind'=>'entry','utm'=>'github'],[$header],'privacy'.$i);check_growth(($r[1]['data']['counted']??true)===false,'privacy preference suppresses collection: '.$header);}
 $r=growth_request(['kind'=>'entry'],['Origin: https://evil.test'],'foreign');check_growth($r[0]===403,'cross-origin writes are rejected');
 $r=growth_request([],[],'get','GET');check_growth($r[0]===405,'GET cannot write analytics');
 $r=growth_request(['kind'=>'entry','utm'=>['bad']]);check_growth($r[0]===400,'array input is rejected before counting');
 check_growth($before===(int)$db->query('SELECT SUM(hits) FROM typecho_nimbus_growth')->fetchColumn(),'rejected and privacy-suppressed requests do not change totals');
 growth_login(); $r=growth_request(['kind'=>'entry','utm'=>'github'],[],'admin');
 check_growth(($r[1]['data']['reason']??'')==='signed-in'&&($r[1]['data']['counted']??true)===false,'authenticated Typecho visits are excluded');
 check_growth($before===(int)$db->query('SELECT SUM(hits) FROM typecho_nimbus_growth')->fetchColumn(),'logged-in checking does not inflate analytics');
 $db->prepare("UPDATE typecho_options SET value=? WHERE name='plugin:NimbusKit' AND user=0")->execute([serialize(array_merge($config,['analyticsEnable'=>'0']))]);
 $r=growth_request(['kind'=>'entry'],[],'disabled');check_growth($r[0]===404,'disabled collection rejects events');
} catch(Throwable $e){check_growth(false,$e->getMessage());} finally {
 if(is_resource($server)){proc_terminate($server);proc_close($server);} $db=null;
 $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($site,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);foreach($it as $f){if($f->isDir()&&!$f->isLink())rmdir($f->getPathname());else unlink($f->getPathname());}rmdir($site);
}
exit($fail?1:0);
