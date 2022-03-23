$(document).ready(function() {
    // Configure CKEditor to include the zotero plugin.
    $(document).on('o:ckeditor-config', function(event, config) {
        config.toolbar[0].items.push('-');
        config.toolbar[0].items.push('Zotero');
        config.extraPlugins.push('zotero');
    });
    if ('object' === typeof CKEDITOR) {
        // Make CKEditor aware of the zotero plugin.
        CKEDITOR.plugins.addExternal('zotero', ZoteroCitationsCkeditorPluginPath);
        // Set defualt settings for the zotero plugin.
        CKEDITOR.zoteroDefaultSettings = {
            citationStyle: ZoteroCitationsCitationStyle,
            apiLibraryType: ZoteroCitationsApiLibraryType,
            apiLibraryId: ZoteroCitationsApiLibraryId,
            apiKey: ZoteroCitationsApiKey,
        };
    }
});
