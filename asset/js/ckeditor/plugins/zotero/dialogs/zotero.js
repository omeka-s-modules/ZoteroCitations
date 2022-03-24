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
            sort: 'title',
            limit: 100,
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
                            const dialog = this.getDialog();
                            const searchResultsContainer = $(dialog.getContentElement('tab-citation', 'search-results').getElement().$);
                            searchResultsContainer.find('.zotero-search-results').empty();
                            searchResultsContainer.find('.zotero-search-results-loading').show();
                            const serchResultsTable = $(`
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr>
                                        <th style="padding: 3px 10px 3px 3px; font-weight: bold;"></th>
                                        <th style="padding: 3px; font-weight: bold;">Title</th>
                                        <th style="padding: 3px; font-weight: bold;">Creator</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>`);
                            getItems(dialog).then(items => {
                                items.forEach(item => {
                                    const radio = $('<input>', {
                                        type: 'radio',
                                        class: 'ckeditor-zotero-item',
                                        name: 'ckeditor-zotero-item',
                                        value: item.key,
                                    });
                                    const title = item.data.title ? item.data.title.substr(0, 100) : '[no title]';
                                    const creator = item.meta.creatorSummary ? item.meta.creatorSummary : '[no creator]';
                                    const searchResultsRow = $(`
                                    <tr style="border-bottom: 1px solid #D1D1D1;">
                                        <td class="zotero-search-results-radio" style="padding: 3px 10px 3px 3px;"></td>
                                        <td class="zotero-search-results-title" style="padding: 3px;"></td>
                                        <td class="zotero-search-results-creator" style="padding: 3px;"></td>
                                    </tr>`);
                                    searchResultsRow.find('.zotero-search-results-title').append(title);
                                    searchResultsRow.find('.zotero-search-results-creator').append(creator);
                                    searchResultsRow.find('.zotero-search-results-radio').append(radio);
                                    serchResultsTable.find('tbody').append(searchResultsRow);
                                });
                                searchResultsContainer.find('.zotero-search-results-loading').hide();
                                searchResultsContainer.find('.zotero-search-results').append(serchResultsTable);
                            });
                        },
                    },
                    {
                        type: 'html',
                        id: 'search-results',
                        html: `
                        <div class="zotero-search-results-wrapper">
                            <div class="zotero-search-results-loading" style="display: none;">Loading...</div>
                            <div class="zotero-search-results"></div>
                        </div>`,
                        onHide: function() {
                            // Reset the search results container.
                            const searchResultsContainer = $(this.getDialog().getContentElement('tab-citation', 'search-results').getElement().$);
                            searchResultsContainer.find('.zotero-search-results').empty();
                            searchResultsContainer.find('.zotero-search-results-loading').hide();
                        },
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
