<?php if ($this->user->getId() > 0): ?>
<div id="ae-assistant" data-endpoint="<?= $this->url->href('AeController','read',['plugin'=>'AgenticEverything']) ?>" data-csrf="<?= $this->text->e($this->app->getToken()->getReusableCSRFToken()) ?>">
<button id="ae-launcher" type="button" aria-label="Open task assistant" aria-expanded="false" title="Task assistant · drag to move">AE</button>
<section id="ae-panel" hidden role="dialog" aria-label="Task assistant">
<header><strong>Task assistant</strong><button id="ae-close" type="button" aria-label="Close assistant">×</button></header>
<p class="ae-muted">A focused Kanboard companion. Read one task, review a change, then confirm. No paid AI or external data transfer.</p>
<form id="ae-read-form"><label for="ae-task-id">Task number</label><div class="ae-row"><input id="ae-task-id" type="number" min="1" required placeholder="e.g. 1"><button type="submit">Read task</button></div></form>
<div id="ae-message" role="status" aria-live="polite">Choose a task you can access.</div>
<form id="ae-change-form" hidden><label for="ae-field">Change</label><select id="ae-field"><option value="owner_id">Assignee</option><option value="is_active">Status</option></select><label for="ae-value">New value</label><select id="ae-value"></select><button type="submit">Preview change</button></form>
<div id="ae-confirmation" hidden><p id="ae-preview"></p><button id="ae-confirm" type="button">Confirm this change</button><button id="ae-cancel" type="button">Cancel</button></div>
<button id="ae-receipt" type="button" hidden>Check operation receipt</button>
<footer>Deterministic demo · host permissions enforced · 5-minute confirmation</footer>
</section></div>
<?php endif ?>
