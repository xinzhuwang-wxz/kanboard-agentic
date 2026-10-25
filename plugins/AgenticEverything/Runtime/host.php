<?php
// Private stdin transport, never a web endpoint. Identity is supplied only by the local AE owner.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
try {
    $raw=stream_get_contents(STDIN,65537);if(strlen($raw)>65536)throw new RuntimeException('Request too large');
    $r=json_decode($raw,true,32,JSON_THROW_ON_ERROR);$p=$r['principal'];
    if($p['tenantId']!=='kanboard-local'||$p['applicationId']!=='kanboard'||!preg_match('/^[1-9][0-9]*$/D',$p['subjectId']))throw new RuntimeException('Identity denied');
    require dirname(__DIR__,3).'/app/common.php';
    $user=$container['userModel']->getById((int)$p['subjectId']);
    if(!$user || !$user['is_active'])throw new RuntimeException('Identity denied');
    if($container['configModel']->get('ae_enabled','1')!=='1')throw new RuntimeException('Assistant disabled');
    $_SESSION=[];$container['userSession']->initialize($user);
    if($r['action']==='current') { echo json_encode(['ok'=>true,'result'=>['tenantId'=>'kanboard-local','applicationId'=>'kanboard','subjectId'=>(string)$user['id'],'roles'=>[$user['role']],'scopes'=>['tasks:read','tasks:write']]]);exit; }
    $args=$r['arguments'] ?? [];
    $_GET=$args;$_POST=$args;$_POST['csrf_token']=$container['token']->getReusableCSRFToken();$_SERVER['REQUEST_METHOD']='POST';$_SERVER['SERVER_PROTOCOL']='HTTP/1.1';
    $container['request']=new \Kanboard\Core\Http\Request($container,$_SERVER,$_GET,$_POST);
    require_once dirname(__DIR__).'/Controller/AeController.php';
    $controller=new \Kanboard\Plugin\AgenticEverything\Controller\AeController($container);
    $methods=['authorize'=>'hostAuthorize','read'=>'read','prepare'=>'hostPrepare','confirm'=>'hostConfirm','receipt'=>'receipt'];
    if(!isset($methods[$r['action']]))throw new RuntimeException('Unknown operation');
    $controller->{$methods[$r['action']]}();
} catch(Throwable $e) { echo json_encode(['ok'=>false,'error'=>$e->getMessage()]); }
