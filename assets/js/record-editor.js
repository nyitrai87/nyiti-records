(function (wp) {
  const { registerPlugin } = wp.plugins;
  const { PluginDocumentSettingPanel } = wp.editPost;
  const { TextControl } = wp.components;
  const { useSelect, useDispatch } = wp.data;
  const { createElement: el } = wp.element;

  const RecordDetailsPanel = () => {
    const postType = useSelect(
      (select) => select("core/editor").getCurrentPostType(),
      [],
    );
    if (postType !== "record") return null;

    const meta = useSelect(
      (select) => select("core/editor").getEditedPostAttribute("meta"),
      [],
    );
    const { editPost } = useDispatch("core/editor");

    const artist = meta?._nyiti_artist || "";
    const album = meta?._nyiti_album || "";
    const variant = meta?._nyiti_variant || "";

    return el(
      PluginDocumentSettingPanel,
      {
        name: "nyiti-record-details",
        title: "Record details",
        className: "nyiti-record-details",
      },
      el(TextControl, {
        label: "Artist / Band name",
        value: artist,
        onChange: (value) =>
          editPost({ meta: { ...meta, _nyiti_artist: value } }),
      }),
      el(TextControl, {
        label: "Album title",
        value: album,
        onChange: (value) =>
          editPost({ meta: { ...meta, _nyiti_album: value } }),
      }),
      el(TextControl, {
        label: "Variant",
        help: "Example: Black/Gold split with white splatter",
        value: variant,
        onChange: (value) =>
          editPost({ meta: { ...meta, _nyiti_variant: value } }),
      }),
    );
  };

  registerPlugin("nyiti-record-details-plugin", {
    render: RecordDetailsPanel,
  });
})(window.wp);
