<div class="page-header"><h2>Agentic Everything</h2></div>
<p>A local, bounded assistant for Kanboard 1.2.56. Your existing users, project permissions and task models remain authoritative.</p>
<form method="post" action="<?= $this->url->href('AeController','settings',['plugin'=>'AgenticEverything']) ?>">
<?= $this->form->csrf() ?>
<label for="ae-enabled">Assistant availability</label><select name="ae_enabled" id="ae-enabled"><option value="1" <?= $enabled==='1'?'selected':'' ?>>Enabled</option><option value="0" <?= $enabled!=='1'?'selected':'' ?>>Disabled (including pending confirmations)</option></select><button class="btn btn-blue" type="submit">Save</button>
</form>
<h3>Capabilities and limits</h3><ul><li>Read a single authorized task and its assignable members.</li><li>Preview and confirm an assignee or open/closed status change.</li><li>Confirmation binds the current user, exact task snapshot, change and expiry; every execution rechecks host permissions.</li><li>Receipts persist in the host database. Repeated confirmations are rejected; unknown outcomes require host review, never blind retry.</li><li>Qualified scope: SQLite, local tasks, projects without automated actions. Original Kanboard controls remain available outside this scope.</li><li>No model provider configured: deterministic command interface, no model quality claim and no paid calls.</li></ul>
<p>Disable this plugin to remove the assistant without removing task data. Keep operation receipts for audit. Back up the database before upgrades.</p>
