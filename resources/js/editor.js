import '../css/editor.css';

window.blockwire = (config) => {
    config = config ?? {};

    return {
        iframe: null,

        dropList: null,

        insert: false,

        device: 'desktop',

        desktopPreviewWidth: 1024,

        sidebarVisible: true,

        panelWidth: 380,

        panelMinWidth: 260,

        previewMinWidth: 320,

        isResizingPanel: false,

        movePanelResizeHandler: null,

        stopPanelResizeHandler: null,

        resizeWindowHandler: null,

        panelWidthStorageKey: 'blockwire-panel-width',

        panelWidthCustomizedStorageKey: 'blockwire-panel-width-customized',

        lastTopPos: 0,

        cursorPos: 0,

        currentDragItem: null,

        activeBlockId: null,

        pendingPreviewChange: null,

        previewPositionsBeforeUpdate: null,

        previewMode: config.previewMode ?? 'debounced',

        previewDebounceMs: Number(config.previewDebounceMs ?? 150),

        previewDirty: Boolean(config.previewDirty ?? false),

        previewRefreshQueued: false,

        previewRefreshInFlight: false,

        previewRefreshTimer: null,

        // JSON Modal state
        showJsonModal: false,
        jsonPayload: '',
        loadingJson: false,
        copyStatus: '',

        insertBeforeClasses: ['after:opacity-100', 'after:top-0', 'after:h-[4px]', 'after:bg-gray-500', 'after:rounded-full'],

        insertAfterClasses: ['after:opacity-100', 'after:bottom-0', 'after:h-[4px]', 'after:bg-gray-500', 'after:rounded-full'],

        async openJsonModal() {
            this.loadingJson = true;
            this.copyStatus = '';
            try {
                const json = await this.component().call('getJsonSnapshot');
                this.jsonPayload = json;
                this.showJsonModal = true;
            } catch (e) {
                console.error('Failed to fetch JSON snapshot:', e);
            } finally {
                this.loadingJson = false;
            }
        },

        async copyToClipboard() {
            try {
                // Try modern clipboard API first
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(this.jsonPayload);
                } else {
                    // Fallback for non-secure contexts (e.g., localhost HTTP)
                    const textArea = document.createElement('textarea');
                    textArea.value = this.jsonPayload;
                    textArea.style.position = 'fixed';
                    textArea.style.left = '-9999px';
                    textArea.style.top = '-9999px';
                    document.body.appendChild(textArea);
                    textArea.focus();
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                }
                this.copyStatus = 'Copied!';
                setTimeout(() => this.copyStatus = '', 2000);
            } catch (e) {
                console.error('Copy failed:', e);
                this.copyStatus = 'Failed to copy';
            }
        },

        closeJsonModal() {
            this.showJsonModal = false;
            this.copyStatus = '';
        },

        componentId() {
            return this.$root.closest('[wire\\:id]')?.getAttribute('wire:id') ?? null;
        },

        normalizeEventPayload(value) {
            if (Array.isArray(value)) {
                if (value.length === 1) {
                    return this.normalizeEventPayload(value[0]);
                }

                return {};
            }

            if (value && typeof value === 'object' && Object.prototype.hasOwnProperty.call(value, 'detail')) {
                return this.normalizeEventPayload(value.detail);
            }

            if (value && typeof value === 'object') {
                return value;
            }

            return {};
        },

        isForThisComponent(value) {
            let payload = this.normalizeEventPayload(value);

            return ! payload.componentId || payload.componentId === this.componentId();
        },

        clearPreviewRefreshTimer() {
            if (this.previewRefreshTimer === null) {
                return;
            }

            window.clearTimeout(this.previewRefreshTimer);
            this.previewRefreshTimer = null;
        },

        queuePreviewRefresh(delay = null) {
            this.clearPreviewRefreshTimer();

            this.previewRefreshQueued = true;

            this.previewRefreshTimer = window.setTimeout(() => {
                this.refreshPreview();
            }, delay ?? this.previewDebounceMs);
        },

        async refreshPreview() {
            if (this.previewRefreshInFlight) {
                if (this.previewMode === 'debounced') {
                    this.queuePreviewRefresh();
                }

                return;
            }

            if (! this.previewDirty && ! this.previewRefreshQueued) {
                return;
            }

            this.clearPreviewRefreshTimer();
            this.previewRefreshQueued = false;
            this.previewRefreshInFlight = true;

            try {
                await this.component().call('refreshPreview');
            } catch (e) {
                console.error('Failed to refresh preview:', e);
            } finally {
                this.previewRefreshInFlight = false;

                if (this.previewMode === 'debounced' && this.previewDirty) {
                    this.queuePreviewRefresh();
                }
            }
        },

        getFrameWindow() {
            return this.iframe ? this.iframe.contentWindow : null;
        },

        getFrameDocument() {
            let frameWindow = this.getFrameWindow();

            if (! frameWindow) {
                return null;
            }

            return frameWindow.document;
        },

        toNumber(value) {
            if (value === false || value === null || typeof value === 'undefined' || value === '' || value === 'false') {
                return null;
            }

            let number = Number(value);

            if (! Number.isFinite(number)) {
                return null;
            }

            return number;
        },

        normalizeActiveBlockId(value) {
            if (Array.isArray(value)) {
                if (value.length === 0) {
                    return false;
                }

                return this.normalizeActiveBlockId(value[0]);
            }

            if (value && typeof value === 'object') {
                if (Object.prototype.hasOwnProperty.call(value, 'detail')) {
                    return this.normalizeActiveBlockId(value.detail);
                }

                if (Object.prototype.hasOwnProperty.call(value, 'activeBlockIndex')) {
                    return this.normalizeActiveBlockId(value.activeBlockIndex);
                }

                if (Object.prototype.hasOwnProperty.call(value, 'blockId')) {
                    return this.normalizeActiveBlockId(value.blockId);
                }

                let values = Object.values(value);

                if (values.length === 1) {
                    return this.normalizeActiveBlockId(values[0]);
                }
            }

            let number = this.toNumber(value);

            if (number === null) {
                return false;
            }

            return number;
        },

        applyActiveBlockState(root = null) {
            let frameRoot = root ?? this.getFrameDocument();

            if (! frameRoot) {
                return;
            }

            frameRoot.querySelectorAll('[drag-item]').forEach(item => {
                item.classList.remove('active');
            });

            let activeBlockId = this.normalizeActiveBlockId(this.activeBlockId);
            this.activeBlockId = activeBlockId;

            if (activeBlockId === false) {
                return;
            }

            let activeBlock = frameRoot.querySelector(`[drag-item][data-block="${activeBlockId}"]`);

            if (activeBlock) {
                activeBlock.classList.add('active');
            }
        },

        capturePreviewPositions(root) {
            let positions = [];

            root.querySelectorAll('[drag-item]').forEach(item => {
                let index = this.toNumber(item.dataset.block);

                if (index === null) {
                    return;
                }

                let rect = item.getBoundingClientRect();

                positions[index] = {
                    top: rect.top,
                    left: rect.left,
                };
            });

            return positions;
        },

        resolveInsertedIndex(change) {
            if (change.previousCount === 0) {
                return 0;
            }

            if (change.index === null) {
                return change.previousCount;
            }

            if (change.placement === 'before') {
                return change.index - 1 === -1 ? 0 : change.index - 1;
            }

            return change.index + 1;
        },

        oldIndexForNewIndex(newIndex, change) {
            if (change.type === 'clone') {
                if (newIndex === change.insertedIndex) {
                    return null;
                }

                return newIndex;
            }

            if (change.type === 'insert') {
                if (newIndex === change.insertedIndex) {
                    return null;
                }

                return newIndex < change.insertedIndex ? newIndex : newIndex - 1;
            }

            if (change.type === 'delete') {
                return newIndex < change.index ? newIndex : newIndex + 1;
            }

            return newIndex;
        },

        animateFromPreviousRect(item, previousRect, frameWindow) {
            let currentRect = item.getBoundingClientRect();

            let deltaX = previousRect.left - currentRect.left;
            let deltaY = previousRect.top - currentRect.top;

            if (Math.abs(deltaX) < 1 && Math.abs(deltaY) < 1) {
                return;
            }

            item.style.transition = 'none';
            item.style.transform = `translate(${deltaX}px, ${deltaY}px)`;
            item.style.willChange = 'transform';

            frameWindow.requestAnimationFrame(() => {
                item.style.transition = 'transform 280ms cubic-bezier(0.22, 1, 0.36, 1)';
                item.style.transform = '';

                frameWindow.setTimeout(() => {
                    item.style.transition = '';
                    item.style.willChange = '';
                }, 320);
            });
        },

        queuePreviewChange(type, payload = {}) {
            let root = this.getFrameDocument();

            if (! root) {
                return;
            }

            this.lastTopPos = root.documentElement.scrollTop;

            this.pendingPreviewChange = {
                type: type,
                previousCount: root.querySelectorAll('[drag-item]').length,
                ...payload,
            };

            if (this.pendingPreviewChange.type === 'clone') {
                this.pendingPreviewChange.insertedIndex = this.pendingPreviewChange.previousCount;
            }

            if (this.pendingPreviewChange.type === 'insert') {
                this.pendingPreviewChange.insertedIndex = this.resolveInsertedIndex(this.pendingPreviewChange);
            }

            this.previewPositionsBeforeUpdate = this.capturePreviewPositions(root);
        },

        resetPreviewChangeQueue() {
            this.pendingPreviewChange = null;
            this.previewPositionsBeforeUpdate = null;
        },

        applyPendingPreviewAnimation() {
            let root = this.getFrameDocument();
            let frameWindow = this.getFrameWindow();

            if (! root || ! frameWindow) {
                this.resetPreviewChangeQueue();

                return;
            }

            if (! this.pendingPreviewChange || ! this.previewPositionsBeforeUpdate) {
                return;
            }

            frameWindow.requestAnimationFrame(() => {
                root.querySelectorAll('[drag-item]').forEach(item => {
                    let newIndex = this.toNumber(item.dataset.block);

                    if (newIndex === null) {
                        return;
                    }

                    let oldIndex = this.oldIndexForNewIndex(newIndex, this.pendingPreviewChange);

                    if (oldIndex === null) {
                        return;
                    }

                    let previousRect = this.previewPositionsBeforeUpdate[oldIndex];

                    if (! previousRect) {
                        return;
                    }

                    this.animateFromPreviousRect(item, previousRect, frameWindow);
                });

                let insertedIndex = this.pendingPreviewChange.insertedIndex;

                if (typeof insertedIndex === 'number') {
                    let insertedItem = root.querySelector(`[drag-item][data-block="${insertedIndex}"]`);

                    if (insertedItem) {
                        insertedItem.classList.add('blockwire-entering');
                        frameWindow.setTimeout(() => insertedItem.classList.remove('blockwire-entering'), 360);
                    }
                }

                this.resetPreviewChangeQueue();
            });
        },

        animateCurrentLayoutShift(root, mutator) {
            let before = this.capturePreviewPositions(root);
            let frameWindow = this.getFrameWindow();

            mutator();

            if (! frameWindow) {
                return;
            }

            root.querySelectorAll('[drag-item]').forEach(item => {
                let index = this.toNumber(item.dataset.block);

                if (index === null) {
                    return;
                }

                let previousRect = before[index];

                if (! previousRect) {
                    return;
                }

                this.animateFromPreviousRect(item, previousRect, frameWindow);
            });
        },

        cloneActiveBlock() {
            let blockIndex = this.toNumber(this.$wire.activeBlockIndex);

            if (blockIndex === null) {
                return;
            }

            this.queuePreviewChange('clone', {
                index: blockIndex,
            });

            this.component().call('cloneBlock', blockIndex);
        },

        deleteBlockWithAnimation(blockIndex, blockElement = null) {
            let index = this.toNumber(blockIndex);

            if (index === null) {
                return;
            }

            this.queuePreviewChange('delete', {
                index: index,
            });

            let root = this.getFrameDocument();
            let frameWindow = this.getFrameWindow();
            let target = blockElement;

            if (! target && root) {
                target = root.querySelector(`[drag-item][data-block="${index}"]`);
            }

            if (! target || ! frameWindow) {
                this.component().call('deleteBlock', index);

                return;
            }

            if (target.dataset.bwRemoving === '1') {
                return;
            }

            target.dataset.bwRemoving = '1';
            target.classList.add('blockwire-removing');

            frameWindow.setTimeout(() => {
                delete target.dataset.bwRemoving;
                this.component().call('deleteBlock', index);
            }, 180);
        },

        deleteActiveBlock() {
            let blockIndex = this.toNumber(this.$wire.activeBlockIndex);

            if (blockIndex === null) {
                return;
            }

            this.deleteBlockWithAnimation(blockIndex);
        },

        init() {
            this.iframe = document.getElementById("frame");

            this.dropList = document.querySelector("[drop-list]");
            this.activeBlockId = this.normalizeActiveBlockId(this.$wire.activeBlockIndex);
            this.previewDebounceMs = Number.isFinite(Number(this.previewDebounceMs))
                ? Math.max(0, Number(this.previewDebounceMs))
                : 150;

            this.restorePanelWidth();

            this.$nextTick(() => {
                this.captureDesktopPreviewWidth();
                this.panelWidth = this.clampPanelWidth(this.panelWidth);
            });

            this.resizeWindowHandler = () => {
                this.captureDesktopPreviewWidth();
                this.panelWidth = this.clampPanelWidth(this.panelWidth);
            };
            window.addEventListener('resize', this.resizeWindowHandler);

            document.addEventListener('keydown', (e) => this.undo(e, this));
            document.addEventListener('keydown', (e) => this.redo(e, this));

            this.initListeners()

            const onIframeReady = () => {
                this.initListeners()
                this.activeBlockId = this.normalizeActiveBlockId(this.$wire.activeBlockIndex);
                this.applyActiveBlockState(this.iframe.contentWindow.document);

                this.iframe.contentWindow.scrollTo(0, this.lastTopPos)

                this.applyPendingPreviewAnimation()
            };

            this.iframe.addEventListener("load", () => {
                onIframeReady();
            })

            // Handle race condition: if the iframe (using srcdoc) has already
            // loaded before the load listener was attached, trigger manually.
            if (this.iframe.contentDocument && this.iframe.contentDocument.readyState === 'complete' && this.iframe.contentDocument.documentElement) {
                onIframeReady();
            }

            Livewire.on('activeBlockIndexChanged', (data) => {
                let activeBlockId = this.normalizeActiveBlockId(data);

                if (activeBlockId === false) {
                    activeBlockId = this.normalizeActiveBlockId(this.$wire.activeBlockIndex);
                }

                this.activeBlockId = activeBlockId;
                this.applyActiveBlockState();
            });

            Livewire.on('blockwirePreviewDirty', (data) => {
                if (! this.isForThisComponent(data)) {
                    return;
                }

                let payload = this.normalizeEventPayload(data);

                this.previewDirty = true;

                if (typeof payload.debounceMs !== 'undefined') {
                    let debounceMs = Number(payload.debounceMs);

                    if (Number.isFinite(debounceMs)) {
                        this.previewDebounceMs = Math.max(0, debounceMs);
                    }
                }

                if (this.previewMode === 'debounced') {
                    this.queuePreviewRefresh();
                }
            });

            Livewire.on('blockwirePreviewClean', (data) => {
                if (! this.isForThisComponent(data)) {
                    return;
                }

                this.previewDirty = false;
                this.previewRefreshQueued = false;
                this.clearPreviewRefreshTimer();
            });
        },

        restorePanelWidth() {
            try {
                let hasCustomWidth = window.localStorage.getItem(this.panelWidthCustomizedStorageKey);
                if (hasCustomWidth !== '1') {
                    return;
                }

                let storedWidth = window.localStorage.getItem(this.panelWidthStorageKey);
                if (storedWidth === null) {
                    return;
                }

                let parsedWidth = Number(storedWidth);
                if (Number.isFinite(parsedWidth)) {
                    this.panelWidth = parsedWidth;
                }
            } catch (e) {
                // Ignore localStorage issues (e.g. in restricted browser contexts)
            }
        },

        persistPanelWidth() {
            try {
                window.localStorage.setItem(this.panelWidthStorageKey, String(this.panelWidth));
                window.localStorage.setItem(this.panelWidthCustomizedStorageKey, '1');
            } catch (e) {
                // Ignore localStorage issues (e.g. in restricted browser contexts)
            }
        },

        captureDesktopPreviewWidth() {
            if (!this.$refs.previewContainer) {
                return;
            }

            let width = Math.floor(this.$refs.previewContainer.getBoundingClientRect().width);

            if (width >= this.previewMinWidth) {
                this.desktopPreviewWidth = width;
            }
        },

        previewWidth() {
            if (this.device === 'mobile') {
                return 320;
            }

            if (this.device === 'tablet') {
                return 768;
            }

            return this.desktopPreviewWidth;
        },

        panelStyle() {
            return `width: ${this.panelWidth}px`;
        },

        clampPanelWidth(width) {
            let maxPanelWidth = this.getPanelMaxWidth();
            return Math.max(this.panelMinWidth, Math.min(Math.floor(width), maxPanelWidth));
        },

        getPanelMaxWidth() {
            if (!this.$refs.workspace) {
                return this.panelWidth;
            }

            let workspaceWidth = Math.floor(this.$refs.workspace.getBoundingClientRect().width);
            if (workspaceWidth <= 0) {
                return this.panelWidth;
            }
            let maxPanelWidth = workspaceWidth - this.previewMinWidth;

            return Math.max(this.panelMinWidth, maxPanelWidth);
        },

        resizePanelBy(delta) {
            this.panelWidth = this.clampPanelWidth(this.panelWidth + delta);
            this.persistPanelWidth();
        },

        toggleSidebar() {
            if (this.isResizingPanel) {
                this.stopPanelResize();
            }

            this.sidebarVisible = !this.sidebarVisible;

            if (this.sidebarVisible) {
                this.panelWidth = this.clampPanelWidth(this.panelWidth);
            }

            this.$nextTick(() => {
                this.captureDesktopPreviewWidth();
            });
        },

        startPanelResize(event) {
            if (!this.sidebarVisible) {
                return;
            }

            event.preventDefault();

            this.isResizingPanel = true;
            document.body.style.userSelect = 'none';

            this.movePanelResizeHandler = (moveEvent) => {
                this.updatePanelWidthFromPointer(moveEvent);
            };

            this.stopPanelResizeHandler = () => {
                this.stopPanelResize();
            };

            window.addEventListener('mousemove', this.movePanelResizeHandler);
            window.addEventListener('touchmove', this.movePanelResizeHandler, { passive: false });
            window.addEventListener('mouseup', this.stopPanelResizeHandler, { once: true });
            window.addEventListener('touchend', this.stopPanelResizeHandler, { once: true });

            this.updatePanelWidthFromPointer(event);
        },

        stopPanelResize() {
            this.isResizingPanel = false;
            document.body.style.userSelect = '';

            if (this.movePanelResizeHandler) {
                window.removeEventListener('mousemove', this.movePanelResizeHandler);
                window.removeEventListener('touchmove', this.movePanelResizeHandler);
                this.movePanelResizeHandler = null;
            }

            if (this.stopPanelResizeHandler) {
                window.removeEventListener('mouseup', this.stopPanelResizeHandler);
                window.removeEventListener('touchend', this.stopPanelResizeHandler);
                this.stopPanelResizeHandler = null;
            }
        },

        updatePanelWidthFromPointer(event) {
            if (!this.$refs.workspace) {
                return;
            }

            if (event.cancelable) {
                event.preventDefault();
            }

            let clientX = event.touches ? event.touches[0].clientX : event.clientX;
            if (typeof clientX !== 'number') {
                return;
            }

            let workspaceRect = this.$refs.workspace.getBoundingClientRect();
            let nextPanelWidth = workspaceRect.right - clientX;

            this.panelWidth = this.clampPanelWidth(nextPanelWidth);
            this.persistPanelWidth();
        },

        initListeners() {
            let root = this.getFrameDocument();

            if (! root || ! root.documentElement) {
                return;
            }

            if (! root.documentElement.hasAttribute('data-bw-shortcuts-bound')) {
                root.addEventListener('keydown', (e) => this.undo(e, this));
                root.addEventListener('keydown', (e) => this.redo(e, this));
                root.documentElement.setAttribute('data-bw-shortcuts-bound', '1');
            }

            if (this.dropList) {
                this.dropList.querySelectorAll('[drag-item]').forEach(el => {
                    if (el.dataset.bwPickerBound === '1') {
                        return;
                    }

                    el.dataset.bwPickerBound = '1';

                    el.addEventListener("dragstart", e => {
                        e.target.setAttribute('inserting', true);
                    });

                    el.addEventListener('dragend', e => {
                        e.target.removeAttribute('inserting');
                    });

                    el.addEventListener('dragover', e => e.preventDefault());
                });
            }

            root.querySelectorAll('[drop-placeholder]').forEach(el => {
                if (el.dataset.bwPlaceholderBound === '1') {
                    return;
                }

                el.dataset.bwPlaceholderBound = '1';

                el.addEventListener('dragover', e => e.preventDefault());

                el.addEventListener('dragenter', e => {
                    e.preventDefault();
                    e.target.classList.add('border-gray-400', 'bg-gray-100/50', 'text-gray-500');
                    e.target.classList.remove('border-gray-300', 'text-gray-400');
                });

                el.addEventListener('dragleave', e => {
                    e.preventDefault();
                    e.target.classList.remove('border-gray-400', 'bg-gray-100/50', 'text-gray-500');
                    e.target.classList.add('border-gray-300', 'text-gray-400');
                });

                el.addEventListener('drop', e => {
                    e.preventDefault();

                    if (! e.target.closest('[drop-placeholder]')) {
                        return;
                    }

                    let insertingEl = document.querySelector('[inserting]');

                    if (insertingEl != null) {
                        this.queuePreviewChange('insert', {
                            index: 0,
                            placement: null,
                        });

                        this.component().call('insertBlock', insertingEl.dataset.block, 0);

                        insertingEl.removeAttribute('inserting');

                        insertingEl = false;
                    }
                });
            });

            root.querySelectorAll('[drag-item]').forEach(el => {
                if (el.dataset.bwPreviewBound === '1') {
                    return;
                }

                el.dataset.bwPreviewBound = '1';

                let cloneBtn = el.querySelector('.action-clone');
                if (cloneBtn) {
                    cloneBtn.addEventListener('click', e => {
                        e.stopPropagation();
                        let blockId = this.toNumber(e.target.closest('[drag-item]').dataset.block);

                        if (blockId === null) {
                            return;
                        }

                        this.queuePreviewChange('clone', {
                            index: blockId,
                        });

                        this.component().call('cloneBlock', blockId);
                    });
                }

                let deleteBtn = el.querySelector('.action-delete');
                if (deleteBtn) {
                    deleteBtn.addEventListener('click', e => {
                        e.stopPropagation();

                        let target = e.target.closest('[drag-item]');

                        if (! target) {
                            return;
                        }

                        this.deleteBlockWithAnimation(target.dataset.block, target);
                    });
                }

                el.addEventListener('click', e => {
                    if (e.target.closest('.action-clone') || e.target.closest('.action-delete')) {
                        return;
                    }

                    let dragItem = e.target.closest('[drag-item]');

                    if (! dragItem) {
                        return;
                    }

                    let blockId = dragItem.dataset.block;

                    this.activeBlockId = this.normalizeActiveBlockId(blockId);
                    this.applyActiveBlockState(root);

                    Livewire.dispatch('blockEditComponentSelected', {
                        blockId: blockId
                    });
                }, false);

                el.addEventListener('dragstart', e => {
                    e.target.setAttribute('dragging', true);
                    this.currentDragItem = el;
                });

                el.addEventListener('dragover', e => {
                    e.preventDefault();

                    let dragitem = e.target.closest('[drag-item]');

                    if (! dragitem || this.currentDragItem === dragitem) {
                        return;
                    }

                    let placement = this.beforeOrAfterEl(e, dragitem);
                    let isPreviousSibling = this.currentDragItem != null ? this.currentDragItem.previousElementSibling : false;
                    let isNextSibling = this.currentDragItem != null ? this.currentDragItem.nextElementSibling : false;

                    if (dragitem != isNextSibling && placement === 'before') {
                        dragitem.classList.remove(...this.insertAfterClasses);
                        dragitem.classList.add(...this.insertBeforeClasses);
                    } else if (dragitem != isPreviousSibling && placement === 'after') {
                        dragitem.classList.remove(...this.insertBeforeClasses);
                        dragitem.classList.add(...this.insertAfterClasses);
                    } else {
                        dragitem.classList.remove(...this.insertBeforeClasses, ...this.insertAfterClasses);
                    }
                });

                el.addEventListener('dragend', e => {
                    e.target.removeAttribute('dragging');
                    this.currentDragItem = null;
                });

                el.addEventListener('dragenter', e => {
                    if (e.target.hasAttribute('drag-item')) {
                        e.target.setAttribute('is-target', true);
                    }
                });

                el.addEventListener('dragleave', e => {
                    e.preventDefault();

                    if (e.target.hasAttribute('is-target')) {
                        e.target.classList.remove(...this.insertAfterClasses, ...this.insertBeforeClasses);
                    }
                });

                el.addEventListener('drop', e => {
                    e.preventDefault();

                    let targetItem = e.target.closest('[drag-item]');
                    let draggingEl = root.querySelector('[dragging]');
                    let insertingEl = document.querySelector('[inserting]');

                    if (! targetItem) {
                        return;
                    }

                    if (e.target.hasAttribute('drag-item')) {
                        e.target.classList.remove(...this.insertAfterClasses, ...this.insertBeforeClasses);
                    }

                    this.lastTopPos = root.documentElement.scrollTop;

                    let placement = this.beforeOrAfterEl(e, targetItem);

                    if (insertingEl != null) {
                        this.queuePreviewChange('insert', {
                            index: this.toNumber(targetItem.dataset.block),
                            placement: placement,
                        });

                        this.component().call('insertBlock', insertingEl.dataset.block, targetItem.dataset.block, placement);

                        insertingEl.removeAttribute('inserting');

                        insertingEl = false;

                        return;
                    }

                    if (! draggingEl) {
                        return;
                    }

                    this.resetPreviewChangeQueue();

                    this.animateCurrentLayoutShift(root, () => {
                        if (placement === 'after') {
                            targetItem.after(draggingEl);
                        } else {
                            targetItem.before(draggingEl);
                        }
                    });

                    let orderIds = Array.from(root.querySelectorAll('[drag-item]'))
                        .map(itemEl => itemEl.dataset.block);

                    this.component().call('reorder', orderIds);
                });
            });
        },

        isBefore(container, target, current) {
            let targetFound = false;
            let currentFound = false;
            let before = false;

            container.querySelectorAll('[drag-item]').forEach(el => {
                if (before) {
                    return;
                }

                targetFound = targetFound ? true : el == target;
                currentFound = currentFound ? true : el == current;

                if (targetFound === false && currentFound === true) {
                    before = true;
                    return;
                }
            })

            return before;
        },

        beforeOrAfterEl(e, el) {
            let bounding = el.getBoundingClientRect()

            let upperHalfStart = bounding.y;
            let upperHalfEnd = upperHalfStart + (bounding.height / 2);

            let bottomHalfStart = upperHalfEnd;
            let bottomHalfEnd = bottomHalfStart + (bounding.height / 2)

            let isTopHalf = e.clientY >= upperHalfStart && e.clientY <= upperHalfEnd
            let isBottomHalf = e.clientY >= bottomHalfStart && e.clientY <= bottomHalfEnd

            if (isTopHalf) {
                return 'before'
            } else if (isBottomHalf) {
                return 'after'
            }

            return false
        },

        component() {
            return Livewire.find(
                this.componentId()
            );
        },

        undo(e, editor) {
            if ((e.ctrlKey || e.metaKey) && ! e.shiftKey && e.key === 'z') {
                e.preventDefault();

                if (navigator.userAgent.indexOf('Safari') != -1 && navigator.userAgent.indexOf('Chrome') == -1) {
                    window.history.forward();
                }

                editor.component().call('undo')
            }
        },

        redo(e, editor) {
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'z') {
                e.preventDefault();

                if (navigator.userAgent.indexOf('Safari') != -1 && navigator.userAgent.indexOf('Chrome') == -1) {
                    window.history.forward();
                }

                editor.component().call('redo')
            }
        },
    }
}
