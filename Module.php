<?php
namespace ZoteroCitations;

use Omeka\Module\AbstractModule;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;

class Module extends AbstractModule
{
    const CITATION_STYLES = [
        'american-medical-association' => 'AMA',
        'apa' => 'APA',
        'chicago-author-date' => 'Chicago (author-date)',
        'chicago-note-bibliography' => 'Chicago (note, bibliography)',
        'elsevier-harvard' => 'Elsevier Harvard',
        'harvard-cite-them-right' => 'Harvard Cite Them Right',
        'ieee' => 'IEEE',
        'modern-humanities-research-association' => 'MHRA',
        'modern-language-association' => 'MLA',
        'nature' => 'Nature',
        'vancouver' => 'Vancouver',
    ];

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
                // Build the path to the Zotero CKEditor plugin.
                $pluginPath = $view->assetUrl('js/ckeditor/plugins/zotero/', 'ZoteroCitations');
                $pluginPath = explode('?', $pluginPath)[0];
                // Convert citation styles to a CKEditor-compatible format.
                $citationStyles = [];
                foreach (self::CITATION_STYLES as $key => $value) {
                    $citationStyles[] = [$value, $key];
                }
                // Set global variables for use by Zotero CKEditor plugin.
                $script = sprintf(<<<'EOD'
                    const ZoteroCitationsCkeditorPluginPath = "%s";
                    const ZoteroCitationsCitationStyles = "%s";
                    const ZoteroCitationsCitationStyle = "%s";
                    const ZoteroCitationsApiLibraryType = "%s";
                    const ZoteroCitationsApiLibraryId = "%s";
                    const ZoteroCitationsApiKey = "%s";
                    const ZoteroCitationsSearchSort = "%s";
                    EOD,
                    $view->escapeJs($pluginPath),
                    $view->escapeJs(json_encode($citationStyles)),
                    $view->escapeJs($view->userSetting('zotero_citations_citation_style', 'chicago-author-date')),
                    $view->escapeJs($view->userSetting('zotero_citations_api_library_type', 'users')),
                    $view->escapeJs($view->userSetting('zotero_citations_api_library_id')),
                    $view->escapeJs($view->userSetting('zotero_citations_api_key')),
                    $view->escapeJs($view->userSetting('zotero_citations_search_sort', 'title'))
                );
                $view->headScript()->appendScript($script);
                $view->headScript()->appendFile($view->assetUrl('js/zotero-citations.js', 'ZoteroCitations'));
            }
        );
        $sharedEventManager->attach(
            'Omeka\Form\UserForm',
            'form.add_elements',
            function (Event $event) {
                $form = $event->getTarget();
                $form->get('user-settings')->add([
                    'type' => 'select',
                    'name' => 'zotero_citations_citation_style',
                    'options' => [
                        'label' => 'Zotero Citation: Citation style', // @translate
                        'value_options' => self::CITATION_STYLES,
                    ],
                    'attributes' => [
                        'class' => 'chosen-select',
                        'data-placeholder' => 'Select a citation style', // @translate
                        'value' => $form->getUserSettings()->get('zotero_citations_citation_style', 'chicago-author-date'),
                    ],
                ]);
                $form->get('user-settings')->add([
                    'type' => 'select',
                    'name' => 'zotero_citations_api_library_type',
                    'options' => [
                        'label' => 'Zotero Citation: API library type', // @translate
                        'value_options' => [
                            'users' => 'User', // @translate
                            'groups' => 'Group', // @translate
                        ],
                    ],
                    'attributes' => [
                        'class' => 'chosen-select',
                        'value' => $form->getUserSettings()->get('zotero_citations_api_library_type', 'users'),
                    ],
                ]);
                $form->get('user-settings')->add([
                    'type' => 'number',
                    'name' => 'zotero_citations_api_library_id',
                    'options' => [
                        'label' => 'Zotero Citation: API library ID', // @translate
                    ],
                    'attributes' => [
                        'value' => $form->getUserSettings()->get('zotero_citations_api_library_id'),
                    ],
                ]);
                $form->get('user-settings')->add([
                    'type' => 'text',
                    'name' => 'zotero_citations_api_key',
                    'options' => [
                        'label' => 'Zotero Citation: API key', // @translate
                    ],
                    'attributes' => [
                        'value' => $form->getUserSettings()->get('zotero_citations_api_key'),
                    ],
                ]);
                $form->get('user-settings')->add([
                    'type' => 'select',
                    'name' => 'zotero_citations_search_sort',
                    'options' => [
                        'label' => 'Zotero Citation: Search sort by', // @translate
                        'value_options' => [
                            'title' => 'Title', // @translate
                            'creator' => 'Creator', // @translate
                            'dateModified' => 'Date modified', // @translate
                        ],
                    ],
                    'attributes' => [
                        'class' => 'chosen-select',
                        'value' => $form->getUserSettings()->get('zotero_citations_search_sort', 'title'),
                    ],
                ]);
            }
        );
        $sharedEventManager->attach(
            'Omeka\Form\UserForm',
            'form.add_input_filters',
            function (Event $event) {
                $form = $event->getTarget();
                $inputFilter = $form->getInputFilter();
                $inputFilter->get('user-settings')->add([
                    'name' => 'zotero_citations_citation_style',
                    'allow_empty' => true,
                ]);
                $inputFilter->get('user-settings')->add([
                    'name' => 'zotero_citations_api_library_id',
                    'allow_empty' => true,
                ]);
            }
        );
    }
}
