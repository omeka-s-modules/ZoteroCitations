CKEDITOR.dialog.add('zoteroDialog', function(editor) {
    const fetchApiResponse = async function(dialog, url, params) {
        const urlObj = new URL(url);
        urlObj.search = new URLSearchParams(params).toString();
        return await window.fetch(urlObj, {
            headers: {
                'Zotero-API-Key': dialog.getValueOf('tab-settings', 'api-key'),
            }
        });
    };
    const getItems = async function(dialog) {
        const libraryType = dialog.getValueOf('tab-settings', 'api-library-type');
        const libraryId = dialog.getValueOf('tab-settings', 'api-library-id');
        const params = {
            q: dialog.getValueOf('tab-citation', 'search-query'),
            qmode: 'titleCreatorYear',
        };
        const url = `https://api.zotero.org/${libraryType}/${libraryId}/items/top`;
        const response = await fetchApiResponse(dialog, url, params);
        return await response.json();
    }
    const getItemByKey = async function(dialog, itemKey) {
        const libraryType = dialog.getValueOf('tab-settings', 'api-library-type');
        const libraryId = dialog.getValueOf('tab-settings', 'api-library-id');
        const params = {
            include: 'citation',
            style: dialog.getValueOf('tab-settings', 'citation-style'),
        };
        const url = `https://api.zotero.org/${libraryType}/${libraryId}/items/${itemKey}`;
        const response = await fetchApiResponse(dialog, url, params);
        return await response.json();
    };
    const getBibliography = async function(dialog) {
        const citations = $(editor.getData()).find('span[class^="zotero-citation-"]');
        const itemKeys = $.map(citations, function(citation) {
            return citation.className.split('-').pop();
        });
        const libraryType = dialog.getValueOf('tab-settings', 'api-library-type');
        const libraryId = dialog.getValueOf('tab-settings', 'api-library-id');
        const params = {
            itemKey: itemKeys.join(','),
            format: 'bib',
            style: dialog.getValueOf('tab-settings', 'citation-style'),
        };
        const url = `https://api.zotero.org/${libraryType}/${libraryId}/items`;
        const response = await fetchApiResponse(dialog, url, params);
        return await response.text();
    };
    return {
        title: 'Zotero',
        minWidth: 500,
        minHeight: 300,
        contents: [
            {
                id: 'tab-citation',
                label: 'Add citation',
                elements: [
                    {
                        type: 'text',
                        id: 'search-query',
                    },
                    {
                        type: 'button',
                        id: 'search-button',
                        label: 'Search library',
                        onClick: function() {
                            const button = this;
                            const dialog = button.getDialog();
                            const containerDiv = $(button.getElement().$).closest('div[name="tab-citation"]');
                            containerDiv.find('.zotero-search-results').empty();
                            getItems(dialog).then(items => {
                                items.forEach(item => {
                                    const title = item.data.title ? item.data.title.substr(0, 100) : '[no title]';
                                    const creator = item.meta.creatorSummary ? item.meta.creatorSummary : '[no author]';
                                    const itemDiv = $('<div>');
                                    const itemLabel = $('<label>');
                                    const itemInput = $('<input>', {
                                        type: 'radio',
                                        class: 'ckeditor-zotero-item',
                                        name: 'ckeditor-zotero-item',
                                        value: item.key,
                                    });
                                    itemInput.appendTo(itemLabel);
                                    itemLabel.appendTo(itemDiv);
                                    itemLabel.append(` ${title} (${creator})`);
                                    containerDiv.find('.zotero-search-results').append(itemDiv);
                                });
                            });
                        },
                    },
                    {
                        type: 'html',
                        html: '<div class="zotero-search-results"></div>',
                    }
                ]
            },
            {
                id: 'tab-bib',
                label: 'Add bibliography',
                elements: [
                    {
                        type: 'checkbox',
                        id: 'add-bib',
                        label: 'Check and press "OK" to add a bibliography',
                    },
                ],
            },
            {
                id: 'tab-settings',
                label: 'Settings',
                elements: [
                    {
                        type: 'select',
                        id: 'citation-style',
                        label: 'Citation style',
                        items: JSON.parse(editor.config.zoteroCitationStyles),
                        default: editor.config.zoteroCitationStyle,
                    },
                    {
                        type: 'select',
                        id: 'api-library-type',
                        label: 'Library type',
                        items: [
                            ['User', 'users'],
                            ['Group', 'groups']
                        ],
                        default: editor.config.zoteroApiLibraryType,
                    },
                    {
                        type: 'text',
                        id: 'api-library-id',
                        label: 'Library ID',
                        default: editor.config.zoteroApiLibraryId,
                    },
                    {
                        type: 'text',
                        id: 'api-key',
                        label: 'API key',
                        default: editor.config.zoteroApiKey,
                    },
                ]
            }
        ],
        onOk: function() {
            const dialog = this;
            const checkedItem = $(dialog.getElement().$).find('input[name="ckeditor-zotero-item"]:checked');
            const addBib = dialog.getValueOf('tab-bib', 'add-bib');
            // Add bibliography
            if (addBib) {
                getBibliography(dialog).then(bib => {
                    editor.insertHtml(bib);
                });
                return;
            // Add citation.
            } else if (checkedItem.length) {
                getItemByKey(dialog, checkedItem.val()).then(item => {
                    const ckSpan = editor.document.createElement('span');
                    ckSpan.setAttribute('class', 'zotero-citation-' + item.key);
                    ckSpan.setHtml(item.citation);
                    editor.insertElement(ckSpan);
                });

            }
        }
    };
});
