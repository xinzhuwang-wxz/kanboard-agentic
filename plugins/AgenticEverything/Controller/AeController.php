<?php
namespace Kanboard\Plugin\AgenticEverything\Controller;
use Kanboard\Controller\BaseController;
use Kanboard\Core\Controller\AccessForbiddenException;
class AeController extends BaseController
{
    private function enabled()
    {
        if (DB_DRIVER !== 'sqlite' || $this->configModel->get('ae_enabled', '1') !== '1') {
            throw new \RuntimeException('Assistant unavailable: disabled or database is not qualified SQLite.');
        }
    }
    private function task($id, $write = false, $field = '')
    {
        $task = $this->taskFinderModel->getById($id);
        if (!$task || !$this->userSession->isLogged() || !$this->helper->user->hasProjectAccess('TaskViewController', 'show', $task['project_id'])) {
            throw new AccessForbiddenException();
        }
        if ($write) {
            $controller = $field === 'owner_id' ? 'TaskModificationController' : 'TaskStatusController';
            $action = $field === 'owner_id' ? 'update' : 'close';
            if (!$this->helper->user->hasProjectAccess($controller, $action, $task['project_id']) || !$this->helper->projectRole->canUpdateTask($task)) {
                throw new AccessForbiddenException();
            }
            if ($field === 'owner_id' && !$this->helper->projectRole->canChangeAssignee($task)) throw new AccessForbiddenException();
            if ($field === 'is_active' && !$this->helper->projectRole->canChangeTaskStatusInColumn($task['project_id'], $task['column_id'])) throw new AccessForbiddenException();
            if (!empty($task['external_uri']) || $this->actionModel->getAllByProject($task['project_id'])) throw new \RuntimeException('External tasks and projects with automated actions require separate qualification. Use the original host controls.');
        }
        return $task;
    }
    private function snapshot($task)
    {
        $subtasks = $this->db->table('subtasks')->eq('task_id', $task['id'])->asc('id')->findAll();
        return hash('sha256', json_encode([$task, $subtasks], JSON_THROW_ON_ERROR));
    }
    private function safeTask($task)
    {
        return ['id'=>(int)$task['id'], 'title'=>$task['title'], 'project_id'=>(int)$task['project_id'], 'is_active'=>(int)$task['is_active'], 'owner_id'=>(int)$task['owner_id'], 'version'=>$this->snapshot($task)];
    }
    private function respond($fn)
    {
        try { $this->enabled(); $this->response->json(['ok'=>true, 'result'=>$fn()]); }
        catch (AccessForbiddenException $e) { $this->response->json(['ok'=>false,'error'=>'Permission denied'],403); }
        catch (\Throwable $e) { $this->response->json(['ok'=>false,'error'=>$e->getMessage()],409); }
    }
    private function post()
    {
        if (!$this->request->isPost()) throw new AccessForbiddenException();
        $this->checkReusableCSRFParam();
    }
    public function read()
    {
        $this->respond(function () {
            $task=$this->task($this->request->getIntegerParam('task_id'));
            return ['task'=>$this->safeTask($task),'assignees'=>$this->projectUserRoleModel->getAssignableUsersList($task['project_id']), 'mode'=>'Deterministic bounded assistant; no model API'];
        });
    }
    public function hostAuthorize()
    {
        if (PHP_SAPI !== 'cli') throw new AccessForbiddenException();
        $this->respond(function () {
            $this->post();$v=$this->request->getRawFormValues();$field=$v['field'] ?? '';
            if (!in_array($field,['owner_id','is_active'],true)) throw new \RuntimeException('Unsupported field');
            $task=$this->task((int)($v['task_id'] ?? 0),true,$field);
            if ($field==='owner_id' && !array_key_exists((int)$v['value'],$this->projectUserRoleModel->getAssignableUsersList($task['project_id']))) throw new AccessForbiddenException();
            if ($field==='is_active' && !in_array((int)$v['value'],[0,1],true)) throw new AccessForbiddenException();
            return $this->safeTask($task);
        });
    }
    public function hostPrepare()
    {
        if (PHP_SAPI !== 'cli') throw new AccessForbiddenException();
        $this->respond(function () {
            $this->post(); $v=$this->request->getRawFormValues();
            $field=$v['field'] ?? ''; if (!in_array($field,['is_active','owner_id'],true)) throw new \RuntimeException('Unsupported field');
            if (!isset($v['value']) || !preg_match('/^\d+$/D',(string)$v['value'])) throw new \RuntimeException('Invalid value');
            $value=(int)$v['value']; $task=$this->task((int)($v['task_id'] ?? 0),true,$field);
            if ($field==='is_active' && !in_array($value,[0,1],true)) throw new \RuntimeException('Invalid status');
            if ($field==='owner_id' && !array_key_exists($value,$this->projectUserRoleModel->getAssignableUsersList($task['project_id']))) throw new \RuntimeException('Assignee is not assignable in this project');
            if ((int)$task[$field]===$value) throw new \RuntimeException('No change requested');
            $snapshot=$this->snapshot($task); if (!hash_equals($snapshot,(string)($v['version'] ?? ''))) throw new \RuntimeException('Task changed. Read it again before preparing.');
            $id=$v['operation_id'] ?? bin2hex(random_bytes(24)); if (!preg_match('/^[a-f0-9]{48,64}$/D',$id)) throw new \RuntimeException('Invalid operation ID');$args=['field'=>$field,'value'=>$value,'before'=>(int)$task[$field]];$expires=time()+300;
            $this->db->table('ae_task_operations')->insert(['id'=>$id,'subject_id'=>$this->userSession->getId(),'task_id'=>$task['id'],'snapshot'=>$snapshot,'arguments'=>json_encode($args),'expires'=>$expires,'state'=>'pending','receipt'=>'']);
            return ['operation_id'=>$id,'task'=>$this->safeTask($task),'change'=>$args,'expires'=>$expires,'impact'=>$field==='is_active' && $value===0 ? 'Closing a task also closes its subtasks, as in Kanboard.' : 'Uses the original Kanboard task model and events.'];
        });
    }
    private function operation($id)
    {
        $op=$this->db->table('ae_task_operations')->eq('id',$id)->findOne();
        if (!$op || (int)$op['subject_id'] !== (int)$this->userSession->getId()) throw new AccessForbiddenException();
        $this->task($op['task_id']); return $op;
    }
    public function receipt()
    {
        $this->respond(function () { $op=$this->operation($this->request->getStringParam('operation_id'));return ['operation_id'=>$op['id'],'state'=>$op['state'],'receipt'=>$op['receipt'] ? json_decode($op['receipt'],true) : null,'retry_allowed'=>false]; });
    }
    public function hostConfirm()
    {
        if (PHP_SAPI !== 'cli') throw new AccessForbiddenException();
        $this->respond(function () {
            $this->post();$id=$this->request->getRawValue('operation_id');$op=$this->operation($id);
            if ($op['state'] !== 'pending') throw new \RuntimeException('Confirmation already consumed. Check the receipt; never retry this write.');
            if ((int)$op['expires'] < time()) throw new \RuntimeException('Confirmation expired');
            $args=json_decode($op['arguments'],true);$task=$this->task($op['task_id'],true,$args['field']);
            if (!hash_equals($op['snapshot'],$this->snapshot($task))) throw new \RuntimeException('Stale task; prepare a new change');
            if ($args['field']==='owner_id' && !array_key_exists($args['value'],$this->projectUserRoleModel->getAssignableUsersList($task['project_id']))) throw new AccessForbiddenException();
            $pdo=$this->db->getConnection();
            $claim=$pdo->prepare("UPDATE ae_task_operations SET state='executing' WHERE id=? AND state='pending'");$claim->execute([$id]);
            if ($claim->rowCount()!==1) throw new \RuntimeException('Confirmation already consumed');
            try {
                $pdo->beginTransaction();
                // Acquire SQLite write lock before the final version check. The receipt was claimed durably first.
                $pdo->prepare("UPDATE ae_task_operations SET state='executing' WHERE id=?")->execute([$id]);
                $task=$this->task($op['task_id'],true,$args['field']);
                if (!hash_equals($op['snapshot'],$this->snapshot($task))) throw new \RuntimeException('Stale task; no mutation performed');
                // Re-query target eligibility under the same write lock as the mutation.
                if ($args['field']==='owner_id' && !array_key_exists($args['value'],$this->projectUserRoleModel->getAssignableUsersList($task['project_id']))) throw new AccessForbiddenException();
                if ($args['field']==='owner_id') $ok=$this->taskModificationModel->update(['id'=>$task['id'],'owner_id'=>$args['value']]);
                else $ok=$args['value']===0 ? $this->taskStatusModel->close($task['id']) : $this->taskStatusModel->open($task['id']);
                if (!$ok) throw new \RuntimeException('Host did not confirm the change');
                $receipt=['operation_id'=>$id,'state'=>'completed','task'=>$this->safeTask($this->taskFinderModel->getById($task['id']))];
                $pdo->prepare("UPDATE ae_task_operations SET state='completed',receipt=? WHERE id=?")->execute([json_encode($receipt),$id]);
                if ($pdo->inTransaction()) $pdo->commit();
                return $receipt;
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $pdo->prepare("UPDATE ae_task_operations SET state='unknown' WHERE id=? AND state='executing'")->execute([$id]);
                throw new \RuntimeException('Outcome requires host review; automatic retry is blocked. '.$e->getMessage());
            }
        });
    }
    private function bridge($action)
    {
        $this->respond(function () use ($action) {
            $this->post();
            $port=getenv('AE_BRIDGE_PORT');$token=getenv('AE_BRIDGE_TOKEN');
            if (!$token || !preg_match('/^[0-9]{1,5}$/D',(string)$port)) throw new \RuntimeException('Start the trusted AE launcher before using governed changes.');
            $principal=['tenantId'=>'kanboard-local','applicationId'=>'kanboard','subjectId'=>(string)$this->userSession->getId(),'roles'=>[$this->userSession->getRole()],'scopes'=>['tasks:read','tasks:write']];
            $data=$this->request->getRawFormValues();unset($data['csrf_token']);
            $context=stream_context_create(['http'=>['method'=>'POST','header'=>"Content-Type: application/json\r\nAuthorization: Bearer ".$token."\r\n",'content'=>json_encode(['action'=>$action,'principal'=>$principal,'data'=>$data]),'timeout'=>25,'ignore_errors'=>true]]);
            $raw=file_get_contents('http://127.0.0.1:'.$port.'/',false,$context);
            if ($raw===false) throw new \RuntimeException('AE response unavailable. Check the operation receipt; do not retry the write.');
            $result=json_decode($raw,true,32,JSON_THROW_ON_ERROR);
            if (!$result['ok']) throw new \RuntimeException($result['error']);
            return $result['result'];
        });
    }
    public function prepare() { $this->bridge('start'); }
    public function confirm() { $this->bridge('decide'); }
    public function cancel() { $this->bridge('cancel'); }
    public function settings()
    {
        if (!$this->userSession->isAdmin()) throw new AccessForbiddenException();
        if ($this->request->isPost()) { $this->checkCSRFForm();$this->configModel->save(['ae_enabled'=>$this->request->getRawValue('ae_enabled')==='1'?'1':'0']); }
        $this->response->html($this->helper->layout->config('AgenticEverything:settings',['title'=>'Agentic Everything','enabled'=>$this->configModel->get('ae_enabled','1')]));
    }
}
