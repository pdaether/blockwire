import '../css/editor.css';

window.blockwire = (config) => {
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

        // JSON Modal state
        showJsonModal: false,
        jsonPayload: '',
        loadingJson: false,
        copyStatus: '',

        insertBeforeClasses: ['after:opacity-100', 'after:top-0', 'after:h-[5px]'],

        insertAfterClasses: ['after:opacity-100', 'after:bottom-0', 'after:h-[5px]'],

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

        init() {
            this.iframe = document.getElementById("frame");

            this.dropList = document.querySelector("[drop-list]");

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

            this.iframe.addEventListener("load", () => {
                this.initListeners()

                if (this.activeBlockId !== null && this.activeBlockId !== false) {
                    let root = this.iframe.contentWindow.document;
                    let activeBlock = root.querySelector('[drag-item][data-block="' + this.activeBlockId + '"]');
                    if (activeBlock) {
                        root.querySelectorAll('[drag-item]').forEach(item => {
                            item.classList.remove('active');
                        });
                        activeBlock.classList.add('active');
                    }
                }

                this.iframe.contentWindow.scrollTo(0, this.lastTopPos)
            })

            Livewire.on('activeBlockIndexChanged', (data) => {
                this.activeBlockId = data;

                if (this.iframe && this.iframe.contentWindow) {
                    let root = this.iframe.contentWindow.document;
                    root.querySelectorAll('[drag-item]').forEach(item => {
                        item.classList.remove('active');
                    });

                    if (data !== false && data !== null) {
                        let activeBlock = root.querySelector('[drag-item][data-block="' + data + '"]');
                        if (activeBlock) {
                            activeBlock.classList.add('active');
                        }
                    }
                }
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
            let root = this.iframe.contentWindow.document;

            root.addEventListener('keydown', (e) => this.undo(e, this));
            root.addEventListener('keydown', (e) => this.redo(e, this));

            this.dropList.querySelectorAll('[drag-item]').forEach(el => {
                el.addEventListener("dragstart", e => {
                    e.target.setAttribute('inserting', true)
                })

                el.addEventListener('dragend', e => {
                    e.target.removeAttribute('inserting')
                })

                el.addEventListener('dragover', e => e.preventDefault())
            })

            root.querySelectorAll('[drop-placeholder]').forEach(el => {
                el.addEventListener('dragover', e => e.preventDefault())

                el.addEventListener('dragenter', e => {
                    e.preventDefault()

                    e.target.classList.add('bg-gray-200/50', 'animate-pulse');
                })

                el.addEventListener('dragleave', e => {
                    e.preventDefault()

                    e.target.classList.remove('bg-gray-200/50', 'animate-pulse');
                })

                el.addEventListener('drop', e => {
                    e.preventDefault()

                    if (!e.target.closest('[drop-placeholder]')) {
                        return;
                    }

                    let insertingEl = document.querySelector('[inserting]')

                    if (insertingEl != null) {
                        this.component().call('insertBlock', insertingEl.dataset.block, 0)

                        insertingEl.removeAttribute('inserting')

                        insertingEl = false;

                        return
                    }
                })
            })

            root.querySelectorAll('[drag-item]').forEach(el => {
                let cloneBtn = el.querySelector('.action-clone');
                if (cloneBtn) {
                    cloneBtn.addEventListener('click', e => {
                        e.stopPropagation();
                        let blockId = e.target.closest('[drag-item]').dataset.block;
                        this.component().call('cloneBlock', blockId);
                    });
                }

                let deleteBtn = el.querySelector('.action-delete');
                if (deleteBtn) {
                    deleteBtn.addEventListener('click', e => {
                        e.stopPropagation();
                        let blockId = e.target.closest('[drag-item]').dataset.block;
                        this.component().call('deleteBlock', blockId);
                    });
                }

                el.addEventListener('click', e => {
                    if (e.target.closest('.action-clone') || e.target.closest('.action-delete')) {
                        return;
                    }

                    let dragItem = e.target.closest('[drag-item]');
                    let blockId = dragItem.dataset.block;

                    this.activeBlockId = blockId;

                    root.querySelectorAll('[drag-item]').forEach(item => {
                        item.classList.remove('active');
                    });

                    dragItem.classList.add('active');

                    Livewire.dispatch('blockEditComponentSelected', {
                        blockId: blockId
                    });
                }, false)

                el.addEventListener('dragstart', e => {
                    e.target.setAttribute('dragging', true)
                    this.currentDragItem = el
                })

                el.addEventListener('dragover', e => {
                    e.preventDefault()

                    let dragitem = e.target.closest('[drag-item]')

                    if (this.currentDragItem === dragitem) {
                        return;
                    }

                    let placement = this.beforeOrAfterEl(e, dragitem)
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
                })

                el.addEventListener('dragend', e => {
                    e.target.removeAttribute('dragging')
                    this.currentDragItem = null
                })

                el.addEventListener('dragenter', e => {
                    if (e.target.hasAttribute('drag-item')) {
                        e.target.setAttribute('is-target', true)
                    }
                })

                el.addEventListener('dragleave', e => {
                    e.preventDefault()

                    if (e.target.hasAttribute('is-target')) {
                        e.target.classList.remove(...this.insertAfterClasses, ...this.insertBeforeClasses);
                    }
                })

                el.addEventListener('drop', e => {
                    e.preventDefault()

                    let draggingEl = root.querySelector('[dragging]')
                    let insertingEl = document.querySelector('[inserting]')

                    if (!e.target.closest('[drag-item]')) {
                        return;
                    }

                    if (e.target.hasAttribute('drag-item')) {
                        e.target.classList.remove(...this.insertAfterClasses, ...this.insertBeforeClasses);
                    }

                    this.lastTopPos = root.documentElement.scrollTop

                    let placement = this.beforeOrAfterEl(e, e.target.closest('[drag-item]'))

                    if (insertingEl != null) {
                        this.component().call('insertBlock', insertingEl.dataset.block, e.target.closest('[drag-item]').dataset.block, placement)

                        insertingEl.removeAttribute('inserting')

                        insertingEl = false;

                        return
                    }

                    if (placement === 'after') {
                        e.target.closest('[drag-item]').after(draggingEl)
                    } else {
                        e.target.closest('[drag-item]').before(draggingEl)
                    }

                    let orderIds = Array.from(root.querySelectorAll('[drag-item]'))
                        .map(itemEl => itemEl.dataset.block)

                    this.component().call('reorder', orderIds)
                })
            })
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
                frame.closest('[wire\\:id]').getAttribute('wire:id')
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
