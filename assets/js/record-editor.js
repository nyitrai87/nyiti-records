(function (wp) {
    const { registerPlugin } = wp.plugins;
    const { PluginDocumentSettingPanel } = wp.editPost;
    const { TextControl } = wp.components;
    const { useSelect, useDispatch } = wp.data;
    const { createElement: el } = wp.element;

    const RecordDetailsPanel = () => {
        const postType = useSelect((select) => select('core/editor').getCurrentPostType());
        if (postType !== 'record') return null;

        // IMPORTANT: NO dependency array here, so it updates live
        const meta = useSelect(
            (select) => select('core/editor').getEditedPostAttribute('meta') || {},
        );

        const { editPost } = useDispatch('core/editor');

        const updateMeta = (key, value) => {
            editPost({
                meta: {
                    ...(meta || {}),
                    [key]: value,
                },
            });
        };

        return el(
            PluginDocumentSettingPanel,
            {
                name: 'nyiti-record-details',
                title: 'Record details',
                className: 'nyiti-record-details',
            },
            el(TextControl, {
                label: 'Artist / Band name',
                value: meta._nyiti_artist || '',
                onChange: (value) => updateMeta('_nyiti_artist', value),
            }),
            el(TextControl, {
                label: 'Album title',
                value: meta._nyiti_album || '',
                onChange: (value) => updateMeta('_nyiti_album', value),
            }),
            el(TextControl, {
                label: 'Variant',
                help: 'Example: Black/Gold split with white splatter',
                value: meta._nyiti_variant || '',
                onChange: (value) => updateMeta('_nyiti_variant', value),
            }),
        );
    };

    registerPlugin('nyiti-record-details-plugin', {
        render: RecordDetailsPanel,
    });
})(window.wp);
