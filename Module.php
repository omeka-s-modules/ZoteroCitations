<?php
namespace ZoteroCitations;

use Omeka\Module\AbstractModule;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include sprintf('%s/config/module.config.php', __DIR__);
    }

    public function install(ServiceLocatorInterface $services)
    {
    }

    public function uninstall(ServiceLocatorInterface $services)
    {
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            '*',
            'view.layout',
            function (Event $event) {
                $view = $event->getTarget();
                $pluginPath = $view->assetUrl('js/ckeditor/plugins/zotero/', 'ZoteroCitations');
                $pluginPath = explode('?', $pluginPath)[0];
                $view->headScript()->appendScript(sprintf('const ZoteroCkeditorPluginPath = "%s";', $view->escapeJs($pluginPath)));
                $view->headScript()->appendFile($view->assetUrl('js/zotero-citations.js', 'ZoteroCitations'));
            }
        );
    }
}
