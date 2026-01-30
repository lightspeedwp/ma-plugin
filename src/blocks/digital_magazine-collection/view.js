/**
 * Frontend view script for the digital-magazine-collection block
 * Handles DOM events and extensibility hooks.
 */
(function() {
  const blockSelector = '.wp-block-ma_plugin-digital-magazine-collection';

  function triggerEvent(element, eventName, detail = {}) {
    const event = new CustomEvent(eventName, { detail, bubbles: true });
    element.dispatchEvent(event);
  }

  function initCollectionBlock(block) {
    // Example: trigger collectionInit event
    triggerEvent(block, 'collectionInit', { block });

    // Example: listen for filter changes
    block.addEventListener('collectionFilter', (e) => {
      // Custom extensibility point
      if (window.ma_pluginCollectionFilterHandler) {
        window.ma_pluginCollectionFilterHandler(e.detail, block);
      }
    });
    // Add more event listeners as needed for extensibility
  }

  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll(blockSelector).forEach(initCollectionBlock);
  });
})();
