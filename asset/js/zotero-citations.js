$(document).ready(function() {
    $(document).on('o:ckeditor-config', function(event, config) {
        config.toolbar[0].items.push('-');
        config.toolbar[0].items.push('Zotero');
        config.extraPlugins.push('zotero');
    });
    if ('object' === typeof CKEDITOR) {
        CKEDITOR.plugins.addExternal('zotero', ZoteroCkeditorPluginPath);
        CKEDITOR.zoteroDefaultSettings = {
            apiKey: '',
            apiLibraryType: 'users',
            apiLibraryId: 15,
            citationStyle: 'chicago-author-date',
        };
    }
});
