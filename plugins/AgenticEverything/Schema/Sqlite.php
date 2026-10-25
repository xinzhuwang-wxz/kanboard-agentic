<?php
namespace Kanboard\Plugin\AgenticEverything\Schema;
const VERSION = 1;
function version_1($pdo)
{
    $pdo->exec('CREATE TABLE ae_task_operations (id TEXT PRIMARY KEY, subject_id INTEGER NOT NULL, task_id INTEGER NOT NULL, snapshot TEXT NOT NULL, arguments TEXT NOT NULL, expires INTEGER NOT NULL, state TEXT NOT NULL, receipt TEXT NOT NULL DEFAULT "")');
}
