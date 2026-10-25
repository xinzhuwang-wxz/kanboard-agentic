<?php
namespace Kanboard\Plugin\AgenticEverything;
use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Security\Role;
class Plugin extends Base
{
    public function initialize()
    {
        $this->template->hook->attach('template:layout:bottom', 'AgenticEverything:assistant');
        $this->template->hook->attach('template:config:sidebar', 'AgenticEverything:sidebar');
        $this->hook->on('template:layout:css', ['template'=>'plugins/AgenticEverything/Assets/assistant.css']);
        $this->hook->on('template:layout:js', ['template'=>'plugins/AgenticEverything/Assets/assistant.js']);
        $this->projectAccessMap->add('AeController', ['read','prepare','confirm','receipt','cancel'], Role::PROJECT_VIEWER);
        $this->applicationAccessMap->add('AeController', ['read','prepare','confirm','receipt','cancel'], Role::APP_USER);
    }
    public function getPluginName() { return 'Agentic Everything'; }
    public function getPluginDescription() { return 'Bounded task assistant. Host sessions and permissions remain authoritative.'; }
    public function getPluginAuthor() { return 'Agentic Everything contributors'; }
    public function getPluginVersion() { return '1.0.3'; }
    public function getCompatibleVersion() { return '1.2.56'; }
}
