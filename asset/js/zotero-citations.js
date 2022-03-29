$(document).on('o:ckeditor-config', function(event, config) {
    // Make CKEditor aware of the zotero plugin.
    CKEDITOR.plugins.addExternal('zotero', ZoteroCitationsCkeditorPluginPath);
    // Configure CKEditor to include the zotero plugin.
    config.toolbar[0].items.push('-');
    config.toolbar[0].items.push('Zotero');
    config.extraPlugins.push('zotero');
    // Configure the zotero plugin.
    config.zoteroCitationStyles = ZoteroCitationsCitationStyles;
    config.zoteroCitationStyle = ZoteroCitationsCitationStyle;
    config.zoteroBibliographyLocales = ZoteroCitationsBibliographyLocales;
    config.zoteroBibliographyLocale = ZoteroCitationsBibliographyLocale;
    config.zoteroBibliographyLinkwrap = ZoteroCitationsBibliographyLinkwrap;
    config.zoteroApiLibraryType = ZoteroCitationsApiLibraryType;
    config.zoteroApiLibraryId = ZoteroCitationsApiLibraryId;
    config.zoteroApiKey = ZoteroCitationsApiKey;
    config.zoteroSearchSort = ZoteroCitationsSearchSort;
});
