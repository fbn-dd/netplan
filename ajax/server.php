<?php

require_once 'HTML/AJAX/Server.php';

class AutoServer extends HTML_AJAX_Server {
        // this flag must be set for your init methods to be used
        var $initMethods = true;

        function initRemoteMap() {
                require_once dirname(__FILE__) . '/../include/remote-map.php';
                $remoteMap = new RemoteMap();
                $this->registerClass($remoteMap);
        }

        function initRemoteStats() {
                require_once dirname(__FILE__) . '/../include/remote-stats.php';
                $remoteStats = new RemoteStats();
                $this->registerClass($remoteStats);
        }

        function initRemoteEdit() {
                require_once dirname(__FILE__) . '/../include/remote-edit.php';
                $remoteEdit = new RemoteEdit();
                $this->registerClass($remoteEdit);
        }

}

$server = new AutoServer();
$server->handleRequest();

?>
