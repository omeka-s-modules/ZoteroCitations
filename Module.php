<?php
namespace ZoteroCitations;

use Omeka\Module\AbstractModule;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class Module extends AbstractModule
{
    /**
     * Citation styles.
     *
     * Zotero comes packaged with these styles. Add more here if needed.
     */
    const CITATION_STYLES = [
        'american-chemical-society' => 'American Chemical Society (ACS)',
        'american-medical-association' => 'American Medical Association (AMA)',
        'american-political-science-association' => 'American Political Science Association (APSA)',
        'apa' => 'American Psychological Association (APA)',
        'american-sociological-association' => 'American Sociological Association (ASA)',
        'chicago-author-date' => 'Chicago Manual of Style (author-date)',
        'chicago-fullnote-bibliography' => 'Chicago Manual of Style (full note)',
        'chicago-note-bibliography' => 'Chicago Manual of Style (note)',
        'harvard-cite-them-right' => 'Cite Them Right - Harvard',
        'elsevier-harvard' => 'Elsevier - Harvard (with titles)',
        'ieee' => 'IEEE',
        'modern-humanities-research-association' => 'Modern Humanities Research Association (note with bibliography) (MHRA)',
        'modern-language-association' => 'Modern Language Association (MLA)',
        'nature' => 'Nature',
        'vancouver' => 'Vancouver',
    ];

    /**
     * Bibliography locales.
     *
     * @see https://github.com/citation-style-language/locales
     */
    const BIBLIOGRAPHY_LOCALES = [
        'af-ZA' => 'Afrikaans',
        'ar' => 'العربية',
        'bg-BG' => 'Български',
        'ca-AD' => 'Català',
        'cs-CZ' => 'Čeština',
        'cy-GB' => 'Cymraeg',
        'da-DK' => 'Dansk',
        'de-AT' => 'Deutsch (Österreich)',
        'de-CH' => 'Deutsch (Schweiz)',
        'de-DE' => 'Deutsch (Deutschland)',
        'el-GR' => 'Ελληνικά',
        'en-GB' => 'English (UK)',
        'en-US' => 'English (US)',
        'es-CL' => 'Español (Chile)',
        'es-ES' => 'Español (España)',
        'es-MX' => 'Español (México)',
        'et-EE' => 'Eesti keel',
        'eu' => 'Euskara',
        'fa-IR' => 'فارسی',
        'fi-FI' => 'Suomi',
        'fr-CA' => 'Français (Canada)',
        'fr-FR' => 'Français (France)',
        'he-IL' => 'עברית',
        'hi-IN' => 'हिंदी',
        'hr-HR' => 'Hrvatski',
        'hu-HU' => 'Magyar',
        'id-ID' => 'Bahasa Indonesia',
        'is-IS' => 'Íslenska',
        'it-IT' => 'Italiano',
        'ja-JP' => '日本語',
        'km-KH' => 'ភាសាខ្មែរ',
        'ko-KR' => '한국어',
        'la' => 'Latina',
        'lt-LT' => 'Lietuvių kalba',
        'lv-LV' => 'Latviešu',
        'mn-MN' => 'Монгол',
        'nb-NO' => 'Norsk bokmål',
        'nl-NL' => 'Nederlands',
        'nn-NO' => 'Norsk nynorsk',
        'pl-PL' => 'Polski',
        'pt-BR' => 'Português (Brasil)',
        'pt-PT' => 'Português (Portugal)',
        'ro-RO' => 'Română',
        'ru-RU' => 'Русский',
        'sk-SK' => 'Slovenčina',
        'sl-SI' => 'Slovenščina',
        'sr-RS' => 'Српски / Srpski',
        'sv-SE' => 'Svenska',
        'th-TH' => 'ไทย',
        'tr-TR' => 'Türkçe',
        'uk-UA' => 'Українська',
        'vi-VN' => 'Tiếng Việt',
        'zh-CN' => '中文 (中国大陆)',
        'zh-TW' => '中文 (台灣)',
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
                if (!$view->status()->isAdminRequest()) {
                    // A user must be logged in to use this module.
                    return;
                }
                // Build the path to the Zotero CKEditor plugin.
                $pluginPath = $view->assetUrl('js/ckeditor/plugins/zotero/', 'ZoteroCitations');
                $pluginPath = explode('?', $pluginPath)[0];
                // Convert citation styles to a CKEditor-compatible format.
                $citationStyles = [];
                foreach (self::CITATION_STYLES as $key => $value) {
                    $citationStyles[] = [$value, $key];
                }
                // Convert bibliography locales to a CKEditor-compatible format.
                $bibliographyLocales = [];
                foreach (self::BIBLIOGRAPHY_LOCALES as $key => $value) {
                    $bibliographyLocales[] = [$value, $key];
                }
                $scriptTemplate = <<<'EOD'
const ZoteroCitationsCkeditorPluginPath = "%s";
const ZoteroCitationsCitationStyles = "%s";
const ZoteroCitationsCitationStyle = "%s";
const ZoteroCitationsBibliographyLocales = "%s";
const ZoteroCitationsBibliographyLocale = "%s";
const ZoteroCitationsBibliographyLinkwrap = "%s";
const ZoteroCitationsApiLibraryType = "%s";
const ZoteroCitationsApiLibraryId = "%s";
const ZoteroCitationsApiKey = "%s";
const ZoteroCitationsSearchSort = "%s";
EOD;
                // Set global variables for use by Zotero CKEditor plugin.
                $script = sprintf(
                    $scriptTemplate,
                    $view->escapeJs($pluginPath),
                    $view->escapeJs(json_encode($citationStyles)),
                    $view->escapeJs($view->userSetting('zotero_citations_citation_style', 'chicago-author-date')),
                    $view->escapeJs(json_encode($bibliographyLocales)),
                    $view->escapeJs($view->userSetting('zotero_citations_bibliography_locale', 'en-US')),
                    $view->escapeJs($view->userSetting('zotero_citations_bibliography_linkwrap', 0)),
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
                $elementGroups = $form->get('user-settings')->getOption('element_groups', []);
                $elementGroups['zotero_citation'] = 'Zotero Citation';
                $form->get('user-settings')->setOption('element_groups', $elementGroups);
                $form->get('user-settings')->add([
                    'type' => 'select',
                    'name' => 'zotero_citations_citation_style',
                    'options' => [
                        'element_group' => 'zotero_citation',
                        'label' => 'Citation style', // @translate
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
                    'name' => 'zotero_citations_bibliography_locale',
                    'options' => [
                        'element_group' => 'zotero_citation',
                        'label' => 'Bibliography locale', // @translate
                        'value_options' => self::BIBLIOGRAPHY_LOCALES,
                    ],
                    'attributes' => [
                        'class' => 'chosen-select',
                        'data-placeholder' => 'Select a bibliography locale', // @translate
                        'value' => $form->getUserSettings()->get('zotero_citations_bibliography_locale', 'en-US'),
                    ],
                ]);
                $form->get('user-settings')->add([
                    'type' => 'checkbox',
                    'name' => 'zotero_citations_bibliography_linkwrap',
                    'options' => [
                        'element_group' => 'zotero_citation',
                        'label' => 'Bibliography link wrap', // @translate
                        'use_hidden_element' => true,
                        'checked_value' => '1',
                        'unchecked_value' => '0',
                    ],
                    'attributes' => [
                        'value' => $form->getUserSettings()->get('zotero_citations_bibliography_linkwrap', 0),
                    ],
                ]);
                $form->get('user-settings')->add([
                    'type' => 'select',
                    'name' => 'zotero_citations_api_library_type',
                    'options' => [
                        'element_group' => 'zotero_citation',
                        'label' => 'API library type', // @translate
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
                        'element_group' => 'zotero_citation',
                        'label' => 'API library ID', // @translate
                    ],
                    'attributes' => [
                        'value' => $form->getUserSettings()->get('zotero_citations_api_library_id'),
                    ],
                ]);
                $form->get('user-settings')->add([
                    'type' => 'text',
                    'name' => 'zotero_citations_api_key',
                    'options' => [
                        'element_group' => 'zotero_citation',
                        'label' => 'API key', // @translate
                    ],
                    'attributes' => [
                        'value' => $form->getUserSettings()->get('zotero_citations_api_key'),
                    ],
                ]);
                $form->get('user-settings')->add([
                    'type' => 'select',
                    'name' => 'zotero_citations_search_sort',
                    'options' => [
                        'element_group' => 'zotero_citation',
                        'label' => 'Search sort by', // @translate
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
