<x-student-layout title="Read Ebook">

<!-- Include PDF.js library locally -->
<script src="{{ asset('js/pdf.min.js') }}"></script>

<style>
    /* variables copied from ebook config */
    :root {
        --s-bg: #fafbfc;
        --s-surface: #ffffff;
        --s-surface-hover: #f8f9fb;
        --s-border: #e9ebee;
        --s-border-hover: #d4d6da;
        --t-primary: #0d1117;
        --t-secondary: #57606a;
        --t-tertiary: #8b949e;
        --t-placeholder: #afb8c1;
        --r-sm: 10px;
        --r: 14px;
        --r-lg: 18px;
        --r-xl: 22px;
        --r-2xl: 28px;
        --shadow-sm: 0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.03);
        --shadow: 0 4px 12px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.03);
        --shadow-xl: 0 24px 64px rgba(0,0,0,0.08), 0 8px 16px rgba(0,0,0,0.03);
        --ease: cubic-bezier(0.16, 1, 0.3, 1);
    }

    .ebook-page-header {
        display: flex; align-items: flex-end; justify-content: space-between; gap: 18px;
        padding-bottom: 18px; border-bottom: 1px solid var(--s-border);
        margin-bottom: 24px;
    }
    .ebook-eyebrow {
        margin: 0 0 6px; color: #be185d; font-size: 11px; font-weight: 850;
        text-transform: uppercase; letter-spacing: .75px;
    }
    .ebook-title { margin: 0; color: var(--t-primary); font-size: 26px; line-height: 1.15; font-weight: 900; letter-spacing: -0.02em; }
    .ebook-subtitle { margin: 7px 0 0; color: var(--t-secondary); font-size: 14px; font-weight: 650; max-width: 680px; }
    .ebook-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

    .ebook-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        min-height: 38px; padding: 8px 14px; border-radius: var(--r-sm);
        border: 1px solid transparent; font-size: 13px; font-weight: 800;
        transition: all 140ms var(--ease); cursor: pointer; white-space: nowrap;
        text-decoration: none;
    }
    .ebook-btn-primary { background: #be185d; color: #fff; }
    .ebook-btn-primary:hover { background: #9d174d; transform: translateY(-1px); box-shadow: var(--shadow-sm); }
    .ebook-btn-muted { background: #fff; border-color: var(--s-border); color: var(--t-secondary); }
    .ebook-btn-muted:hover { background: var(--s-surface-hover); border-color: var(--s-border-hover); color: var(--t-primary); }

    .ebook-icon-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 36px; height: 36px; border-radius: 10px; border: 0;
        background: var(--s-surface-hover); color: var(--t-secondary); transition: all 140ms var(--ease);
    }
    .ebook-icon-btn:hover { background: #eef2f6; color: var(--t-primary); }

    .ebook-reader-stage {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        min-height: 600px; border: 1.5px solid #fbcfe8; border-radius: var(--r-2xl);
        background: #4c0519; padding: 24px; box-shadow: var(--shadow-xl);
    }
    .ebook-reader-controls {
        width: min(100%, 560px); margin-top: 22px; display: flex; align-items: center; justify-content: space-between; gap: 14px;
        background: rgba(255,255,255,.94); border: 1px solid rgba(255,255,255,.5); border-radius: var(--r-lg);
        padding: 13px; box-shadow: var(--shadow);
    }
    .ebook-reader-control-group { display: flex; align-items: center; gap: 8px; }
    .ebook-reader-page-label { text-align: center; min-width: 150px; color: var(--t-secondary); font-size: 12px; font-weight: 850; }
    .ebook-reader-page-label strong { display: block; color: var(--t-primary); font-size: 14px; }
    
    .ebook-loading {
        min-height: 420px; display: flex; flex-direction: column; align-items: center; justify-content: center;
        text-align: center; background: #fff; border: 1px solid var(--s-border); border-radius: var(--r-2xl); box-shadow: var(--shadow-sm);
        padding: 24px;
    }
    .ebook-spinner {
        width: 42px; height: 42px; border: 4px solid #fbcfe8; border-top-color: #be185d;
        border-radius: 50%; animation: ebook-spin 800ms linear infinite;
    }
    @keyframes ebook-spin { to { transform: rotate(360deg); } }

    /* Flipbook viewer custom style rules */
    .ebook-reader-stage {
        position: relative !important;
        box-shadow: none !important;
    }
    .floating-toolbar {
        position: absolute;
        top: 1.25rem;
        right: 1.25rem;
        z-index: 40;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        background: rgba(15, 23, 42, 0.85);
        border: 1px solid rgba(255, 255, 255, 0.15);
        padding: 6px 10px;
        border-radius: 12px;
        box-shadow: none;
        transition: none;
        pointer-events: auto;
    }
    .toolbar-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: none;
        background: transparent;
        color: rgba(255, 255, 255, 0.8);
        cursor: pointer;
        transition: none;
    }
    .toolbar-btn:hover:not(:disabled) {
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
    }
    .toolbar-btn:active:not(:disabled) {
        transform: scale(0.95);
    }
    .toolbar-btn:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }
    .toolbar-btn-highlight {
        background: #be185d;
        color: #fff;
    }
    .toolbar-btn-highlight:hover {
        background: #9d174d;
    }
    .toolbar-text {
        color: #fff;
        font-size: 12px;
        font-weight: 800;
        min-width: 46px;
        text-align: center;
        user-select: none;
    }
    .toolbar-separator {
        width: 1px;
        height: 18px;
        background: rgba(255, 255, 255, 0.15);
        margin: 0 4px;
    }
    @media (max-width: 640px) {
        .floating-toolbar {
            top: 0.75rem;
            right: 0.75rem;
            padding: 4px 8px;
        }
        .toolbar-btn {
            width: 28px;
            height: 28px;
        }
        .toolbar-text {
            font-size: 11px;
            min-width: 38px;
        }
    }
    #book-zoom-container {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: none;
    }
    #book-zoom-container.is-zoomed {
        align-items: flex-start;
        justify-content: flex-start;
        overscroll-behavior: contain;
        scroll-behavior: auto;
        touch-action: none;
    }
    #book-wrapper {
        display: flex;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        transition: none;
        box-sizing: border-box !important;
    }
    #book {
        position: relative;
        perspective: 2000px;
        transform-style: preserve-3d;
        transition: transform 0.6s cubic-bezier(0.25, 1, 0.5, 1);
        max-width: 100%;
        display: block;
    }
    .flip-sheet {
        position: absolute;
        top: 0;
        transform-style: preserve-3d;
        transition: transform 0.6s cubic-bezier(0.25, 1, 0.5, 1);
    }
    .front-side, .back-side {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        backface-visibility: hidden;
        -webkit-backface-visibility: hidden;
        box-shadow: none;
        border-radius: 0 4px 4px 0;
        overflow: hidden;
        background-color: white;
    }
    .back-side {
        transform: rotateY(180deg);
        border-radius: 4px 0 0 4px;
    }
    
    div:fullscreen {
        background-color: #4c0519 !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        width: 100vw !important;
        height: 100vh !important;
        padding: 0 !important;
        box-sizing: border-box !important;
        overflow: visible !important;
    }
    div::backdrop {
        background-color: #4c0519 !important;
    }
    div:fullscreen #book-zoom-container {
        width: 100% !important;
        height: 100% !important;
        max-height: 100% !important;
        box-sizing: border-box !important;
        overflow-y: auto !important;
        overflow-x: auto !important;
    }
    div:fullscreen #book-wrapper {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        box-sizing: border-box !important;
        padding: 1rem !important;
        min-width: 100% !important;
        min-height: 100% !important;
    }
    div:fullscreen #book {
        position: relative !important;
        margin: 0 auto !important;
        left: 0 !important;
        top: 0 !important;
    }
    div:fullscreen .ebook-reader-controls {
        position: fixed !important;
        bottom: 1.5rem !important;
        left: 50% !important;
        transform: translateX(-50%) !important;
        width: calc(100% - 3rem) !important;
        max-width: 32rem !important;
        z-index: 50 !important;
        margin-top: 0 !important;
        background: rgba(255,255,255,.94) !important;
    }
</style>

<script>
    const initAlpineReader = () => {
        Alpine.data('reader', (config) => {
            let pdfDoc = null;
            let panFrame = null;
            let panContainer = null;
            let panLastX = 0;
            let panLastY = 0;

            const savedPage = localStorage.getItem(`book_bookmark_${config.bookId}`);
            const initialPage = savedPage ? Math.min(parseInt(savedPage, 10), 10000) : 0;

            return {
                currentPage: initialPage,
                totalPages: 0,
                isFullscreen: false,
                orientation: 'landscape',
                loading: true,
                loadingProgress: 0,
                renderedPages: new Set(),
                renderTasks: {},
                
                pdfAspectRatio: 0.75, // default fallback 3:4
                resizeTimeout: null,
                resizeHandler: null,
                
                zoom: 1.0,
                panStartX: 0,
                panStartY: 0,
                scrollStartX: 0,
                scrollStartY: 0,
                isPanning: false,
                isPinching: false,
                initialPinchDistance: 0,
                initialPinchZoom: 1.0,

                showContextMenu: false,
                contextMenuX: 0,
                contextMenuY: 0,

                pageWidth: 450,
                pageHeight: 600,
                bookWidth: 900,
                bookHeight: 600,
                overrideZIndexSheet: null,
                overrideTimeout: null,

                loadingStatus: 'Downloading document...',
                get loadingPercent() {
                    switch (this.loadingStatus) {
                        case 'Downloading document...': return 25;
                        case 'Analyzing pages...': return 50;
                        case 'Rendering cover page...': return 75;
                        case 'Rendering bookmarked page...': return 75;
                        case 'Opening book...': return 100;
                        default: return 10;
                    }
                },

                handleWheel(e) {
                    if (!this.isFullscreen) return;
                    e.preventDefault();
                    if (e.deltaY < 0) {
                        this.zoomIn();
                    } else {
                        this.zoomOut();
                    }
                },

                handleContextMenu(e) {
                    if (!this.isFullscreen) return;
                    e.preventDefault();
                    
                    const rect = this.$refs.readerContainer.getBoundingClientRect();
                    let x = e.clientX - rect.left;
                    let y = e.clientY - rect.top;
                    
                    const menuW = 192;
                    const menuH = 175;
                    
                    if (x + menuW > rect.width) {
                        x = rect.width - menuW - 10;
                    }
                    if (y + menuH > rect.height) {
                        y = rect.height - menuH - 10;
                    }
                    
                    this.contextMenuX = Math.max(10, x);
                    this.contextMenuY = Math.max(10, y);
                    this.showContextMenu = true;
                    
                    this.$nextTick(() => {
                        if (window.lucide) {
                            window.lucide.createIcons();
                        }
                    });
                },

                zoomIn() {
                    const previousZoom = this.zoom;
                    const scrollCenter = this.getScrollCenter();
                    this.zoom = Math.min(2.5, this.zoom + 0.25);
                    this.updateSheetStyles();
                    this.restoreZoomScroll(previousZoom, scrollCenter);
                },
                zoomOut() {
                    const previousZoom = this.zoom;
                    const scrollCenter = this.getScrollCenter();
                    this.zoom = Math.max(1.0, this.zoom - 0.25);
                    if (this.zoom <= 1) {
                        this.resetZoom();
                    } else {
                        this.updateSheetStyles();
                        this.restoreZoomScroll(previousZoom, scrollCenter);
                    }
                },
                resetZoom() {
                    this.zoom = 1.0;
                    const container = document.getElementById('book-zoom-container');
                    if (container) {
                        container.scrollLeft = 0;
                        container.scrollTop = 0;
                    }
                    this.updateSheetStyles();
                },
                getScrollCenter() {
                    const container = document.getElementById('book-zoom-container');
                    if (!container || this.zoom <= 1) {
                        return { x: 0.5, y: 0.5 };
                    }

                    return {
                        x: (container.scrollLeft + (container.clientWidth / 2)) / Math.max(container.scrollWidth, 1),
                        y: (container.scrollTop + (container.clientHeight / 2)) / Math.max(container.scrollHeight, 1)
                    };
                },
                restoreZoomScroll(previousZoom, scrollCenter) {
                    this.$nextTick(() => {
                        requestAnimationFrame(() => {
                            const container = document.getElementById('book-zoom-container');
                            if (!container || this.zoom <= 1) return;

                            const maxLeft = Math.max(0, container.scrollWidth - container.clientWidth);
                            const maxTop = Math.max(0, container.scrollHeight - container.clientHeight);
                            const targetX = previousZoom <= 1
                                ? maxLeft / 2
                                : (scrollCenter.x * container.scrollWidth) - (container.clientWidth / 2);
                            const targetY = previousZoom <= 1
                                ? maxTop / 2
                                : (scrollCenter.y * container.scrollHeight) - (container.clientHeight / 2);

                            container.scrollLeft = Math.min(maxLeft, Math.max(0, targetX));
                            container.scrollTop = Math.min(maxTop, Math.max(0, targetY));
                        });
                    });
                },

                startPan(e) {
                    if (e.touches && e.touches.length === 2) {
                        this.isPinching = true;
                        this.initialPinchDistance = Math.hypot(
                            e.touches[0].clientX - e.touches[1].clientX,
                            e.touches[0].clientY - e.touches[1].clientY
                        );
                        this.initialPinchZoom = this.zoom;
                        return;
                    }

                    if (this.zoom <= 1) return;
                    this.isPanning = true;
                    
                    const touch = e.touches ? e.touches[0] : e;
                    
                    e.stopPropagation();
                    if (!e.touches) {
                        e.preventDefault();
                    }
                    
                    panContainer = document.getElementById('book-zoom-container');
                    if (!panContainer) {
                        this.isPanning = false;
                        return;
                    }

                    this.panStartX = touch.clientX;
                    this.panStartY = touch.clientY;
                    panLastX = touch.clientX;
                    panLastY = touch.clientY;
                    this.scrollStartX = panContainer.scrollLeft;
                    this.scrollStartY = panContainer.scrollTop;
                },
                
                pan(e) {
                    if (e.touches && e.touches.length === 2 && this.isPinching) {
                        e.preventDefault();
                        const currentDistance = Math.hypot(
                            e.touches[0].clientX - e.touches[1].clientX,
                            e.touches[0].clientY - e.touches[1].clientY
                        );
                        const factor = currentDistance / this.initialPinchDistance;
                        let targetZoom = Math.min(2.5, Math.max(1.0, this.initialPinchZoom * factor));
                        
                        this.zoom = parseFloat(targetZoom.toFixed(2));
                        this.updateSheetStyles();
                        return;
                    }

                    if (!this.isPanning || this.zoom <= 1) return;
                    
                    if (e.cancelable) {
                        e.preventDefault();
                    }

                    const touch = e.touches ? e.touches[0] : e;
                    panLastX = touch.clientX;
                    panLastY = touch.clientY;
                    
                    if (panFrame) return;

                    panFrame = requestAnimationFrame(() => {
                        panFrame = null;
                        if (!this.isPanning || !panContainer) return;

                        const dx = panLastX - this.panStartX;
                        const dy = panLastY - this.panStartY;

                        panContainer.scrollLeft = this.scrollStartX - dx;
                        panContainer.scrollTop = this.scrollStartY - dy;
                    });
                },
                
                endPan() {
                    this.isPinching = false;
                    if (panFrame) {
                        cancelAnimationFrame(panFrame);
                        panFrame = null;
                    }
                    if (panContainer) {
                        const dx = panLastX - this.panStartX;
                        const dy = panLastY - this.panStartY;
                        panContainer.scrollLeft = this.scrollStartX - dx;
                        panContainer.scrollTop = this.scrollStartY - dy;
                    }
                    this.isPanning = false;
                    panContainer = null;
                },
                
                calculateBookSize(pdfAspectRatio) {
                    const container = document.getElementById('book-zoom-container');
                    let containerWidth = window.innerWidth * 0.9;
                    let containerHeight = window.innerHeight * 0.75;
                    
                    if (container && container.clientWidth > 0 && container.clientHeight > 0) {
                        containerWidth = container.clientWidth;
                        containerHeight = container.clientHeight;
                    }
                    
                    const paddingX = 64;
                    const paddingY = 80;
                    
                    const availableW = Math.max(200, containerWidth - paddingX);
                    const availableH = Math.max(250, containerHeight - paddingY);
                    
                    let pageW, pageH;
                    const isLandscape = availableW > availableH;
                    
                    if (isLandscape) {
                        const bookRatio = 2 * pdfAspectRatio;
                        if (availableW / availableH > bookRatio) {
                            pageH = availableH;
                            pageW = pageH * pdfAspectRatio;
                        } else {
                            pageW = availableW / 2;
                            pageH = pageW / pdfAspectRatio;
                        }
                    } else {
                        const bookRatio = pdfAspectRatio;
                        if (availableW / availableH > bookRatio) {
                            pageH = availableH;
                            pageW = pageH * pdfAspectRatio;
                        } else {
                            pageW = availableW;
                            pageH = pageW / pdfAspectRatio;
                        }
                    }
                    
                    pageW = Math.max(150, Math.min(1000, pageW));
                    pageH = Math.max(200, Math.min(1400, pageH));
                    
                    return {
                        width: Math.floor(pageW),
                        height: Math.floor(pageH)
                    };
                },

                registerResizeListener() {
                    if (this.resizeHandler) {
                        window.removeEventListener('resize', this.resizeHandler);
                    }
                    this.resizeHandler = () => {
                        clearTimeout(this.resizeTimeout);
                        this.resizeTimeout = setTimeout(() => {
                            this.resizeReader();
                        }, 250);
                    };
                    window.addEventListener('resize', this.resizeHandler);
                },

                resizeReader() {
                    if (this.loading || this.totalPages === 0) return;

                    Object.keys(this.renderTasks).forEach((pageStr) => {
                        const pageNum = parseInt(pageStr, 10);
                        const task = this.renderTasks[pageNum];
                        if (task && typeof task.cancel === 'function') {
                            try {
                                task.cancel();
                            } catch (e) {}
                        }
                    });
                    this.renderTasks = {};
                    this.renderedPages.clear();
                    
                    const dims = this.calculateBookSize(this.pdfAspectRatio);
                    this.pageWidth = dims.width;
                    this.pageHeight = dims.height;

                    const container = document.getElementById('book-zoom-container');
                    const containerW = (container && container.clientWidth > 0) ? container.clientWidth : window.innerWidth * 0.9;
                    const containerH = (container && container.clientHeight > 0) ? container.clientHeight : window.innerHeight * 0.75;

                    if (containerW > containerH) {
                        this.orientation = 'landscape';
                        this.bookWidth = this.pageWidth * 2;
                        this.bookHeight = this.pageHeight;
                    } else {
                        this.orientation = 'portrait';
                        this.bookWidth = this.pageWidth;
                        this.bookHeight = this.pageHeight;
                    }
                    
                    const bookEl = document.getElementById('book');
                    if (bookEl) {
                        bookEl.style.width = `${this.bookWidth}px`;
                        bookEl.style.height = `${this.bookHeight}px`;
                    }
                    
                    this.updateSheetStyles();
                    this.renderLazyPages(this.currentPage);
                },

                initReader() {
                    const streamUrl = config.streamUrl;
                    this.loadingStatus = 'Downloading document...';
                    
                    pdfjsLib.GlobalWorkerOptions.workerSrc = config.workerUrl;
                    
                    pdfjsLib.getDocument(streamUrl).promise.then((pdf) => {
                        pdfDoc = pdf;
                        const pdfPageCount = pdf.numPages;
                        this.totalPages = pdfPageCount;
                        this.loadingStatus = 'Analyzing pages...';
                        
                        pdf.getPage(1).then((page) => {
                            const viewport = page.getViewport({ scale: 1.0 });
                            this.pdfAspectRatio = viewport.width / viewport.height;
                            
                            this.loadingStatus = this.currentPage > 0 ? 'Rendering bookmarked page...' : 'Rendering cover page...';
                            
                            const dims = this.calculateBookSize(this.pdfAspectRatio);
                            this.pageWidth = dims.width;
                            this.pageHeight = dims.height;

                            const containerW = window.innerWidth * 0.9;
                            const containerH = window.innerHeight * 0.75;

                            if (containerW > containerH) {
                                this.orientation = 'landscape';
                                this.bookWidth = this.pageWidth * 2;
                                this.bookHeight = this.pageHeight;
                            } else {
                                this.orientation = 'portrait';
                                this.bookWidth = this.pageWidth;
                                this.bookHeight = this.pageHeight;
                            }

                            const bookEl = document.getElementById('book');
                            if (bookEl) {
                                bookEl.innerHTML = '';
                                this.buildFlipbookDOM(this.totalPages);
                                bookEl.style.width = `${this.bookWidth}px`;
                                bookEl.style.height = `${this.bookHeight}px`;
                                this.updateSheetStyles();
                            }

                            const initialPages = [this.currentPage + 1];
                            if (this.orientation === 'landscape' && this.currentPage > 0 && (this.currentPage + 2) <= this.totalPages) {
                                initialPages.push(this.currentPage + 2);
                            }

                            Promise.all(initialPages.map(pageNo => this.renderPageCanvasPromise(pageNo))).then(() => {
                                this.loadingStatus = 'Opening book...';
                                this.loading = false;
                                
                                this.$nextTick(() => {
                                    this.initFlipbook();
                                    this.registerResizeListener();
                                });
                            }).catch((err) => {
                                console.error('Failed to render initial pages, opening anyway:', err);
                                this.loading = false;
                                this.$nextTick(() => {
                                    this.initFlipbook();
                                    this.registerResizeListener();
                                });
                            });
                        }).catch((err) => {
                            console.error('Failed to get page 1 for aspect ratio, using 3:4 fallback:', err);
                            this.pdfAspectRatio = 0.75;
                            this.loading = false;
                            this.$nextTick(() => {
                                this.initFlipbook();
                                this.registerResizeListener();
                            });
                        });
                    }).catch((err) => {
                        console.error('Failed to load secure PDF:', err);
                        
                        const debugEl = document.getElementById('debug-error-message');
                        if (debugEl) {
                            debugEl.style.display = 'block';
                            debugEl.innerText += '\n[PDF Load Error]: ' + err.message + '\nStack: ' + (err.stack || '');
                        }
                        
                        alert('Unable to load ebook file securely. Please try again.');
                        this.loading = false;
                    });
                },

                buildFlipbookDOM(pdfPageCount) {
                    const bookEl = document.getElementById('book');
                    if (!bookEl) return;
                    bookEl.innerHTML = '';
                    
                    const numSheets = Math.ceil(pdfPageCount / 2);
                    
                    for (let s = 1; s <= numSheets; s++) {
                        const sheetEl = document.createElement('div');
                        sheetEl.id = `sheet-${s}`;
                        sheetEl.className = 'flip-sheet';
                        
                        const pFront = 2 * s - 1;
                        const pBack = 2 * s;
                        
                        let frontHTML = '';
                        if (pFront <= pdfPageCount) {
                            frontHTML = `
                                <div class="front-side">
                                    <div style="width: 100%; height: 100%; display: flex; flex-direction: column; justify-content: space-between; background: white;">
                                        <div style="flex: 1; position: relative; background: white; display: flex; align-items: center; justify-content: center; padding: 0.5rem; overflow: hidden; min-height: 0; min-width: 0;">
                                            <canvas id="canvas-page-${pFront}" style="max-width: 100%; max-height: 100%; object-fit: contain; pointer-events: none; display: block;"></canvas>
                                            <div id="spinner-page-${pFront}" style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: white; z-index: 10;">
                                                <div class="animate-spin rounded-full h-6 w-6 border-2 border-pink-500 border-t-transparent"></div>
                                            </div>
                                        </div>
                                        <div style="padding: 0.6rem 1.5rem; background: #fff1f2; border-top: 1px solid #fecdd3; display: flex; align-items: center; justify-content: space-between; font-size: 10px; color: #be185d; font-weight: 800; user-select: none;">
                                            <span>AMIS Student e-Book</span>
                                            <span>Page ${pFront} of ${pdfPageCount}</span>
                                        </div>
                                    </div>
                                </div>
                             `;
                        } else {
                            frontHTML = `<div class="front-side"><div style="width: 100%; height: 100%; background: #f8fafc;"></div></div>`;
                        }
                        
                        let backHTML = '';
                        if (pBack <= pdfPageCount) {
                            backHTML = `
                                <div class="back-side">
                                    <div style="width: 100%; height: 100%; display: flex; flex-direction: column; justify-content: space-between; background: white;">
                                        <div style="flex: 1; position: relative; background: white; display: flex; align-items: center; justify-content: center; padding: 0.5rem; overflow: hidden; min-height: 0; min-width: 0;">
                                            <canvas id="canvas-page-${pBack}" style="max-width: 100%; max-height: 100%; object-fit: contain; pointer-events: none; display: block;"></canvas>
                                            <div id="spinner-page-${pBack}" style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: white; z-index: 10;">
                                                <div class="animate-spin rounded-full h-6 w-6 border-2 border-pink-500 border-t-transparent"></div>
                                            </div>
                                        </div>
                                        <div style="padding: 0.6rem 1.5rem; background: #fff1f2; border-top: 1px solid #fecdd3; display: flex; align-items: center; justify-content: space-between; font-size: 10px; color: #be185d; font-weight: 800; user-select: none;">
                                            <span>AMIS Student e-Book</span>
                                            <span>Page ${pBack} of ${pdfPageCount}</span>
                                        </div>
                                    </div>
                                </div>
                            `;
                        } else {
                            backHTML = `<div class="back-side"><div style="width: 100%; height: 100%; background: #f8fafc;"></div></div>`;
                        }
                        
                        sheetEl.innerHTML = frontHTML + backHTML;
                        bookEl.appendChild(sheetEl);
                    }
                },

                initFlipbook() {
                    if (this.loading || this.totalPages === 0) return;
                    
                    const bookEl = document.getElementById('book');
                    if (!bookEl) return;

                    const preRendered = new Set(this.renderedPages);

                    if (bookEl.children.length === 0) {
                        bookEl.innerHTML = '';
                        this.buildFlipbookDOM(this.totalPages);
                    }

                    this.renderedPages.clear();
                    preRendered.forEach(p => this.renderedPages.add(p));

                    const dims = this.calculateBookSize(this.pdfAspectRatio);
                    this.pageWidth = dims.width;
                    this.pageHeight = dims.height;

                    const container = document.getElementById('book-zoom-container');
                    const containerW = (container && container.clientWidth > 0) ? container.clientWidth : window.innerWidth * 0.9;
                    const containerH = (container && container.clientHeight > 0) ? container.clientHeight : window.innerHeight * 0.75;

                    if (containerW > containerH) {
                        this.orientation = 'landscape';
                        this.bookWidth = this.pageWidth * 2;
                        this.bookHeight = this.pageHeight;
                    } else {
                        this.orientation = 'portrait';
                        this.bookWidth = this.pageWidth;
                        this.bookHeight = this.pageHeight;
                    }

                    bookEl.style.width = `${this.bookWidth}px`;
                    bookEl.style.height = `${this.bookHeight}px`;

                    this.updateSheetStyles();
                    bookEl.classList.remove('opacity-0');
                    this.renderLazyPages(this.currentPage);

                    if (window.lucide) {
                        window.lucide.createIcons();
                    }
                },

                updateSheetStyles() {
                    const numSheets = Math.ceil(this.totalPages / 2);
                    
                    const bookEl = document.getElementById('book');
                    if (bookEl) {
                        bookEl.style.transform = this.getBookTransform();
                    }

                    const currentSheet = Math.floor(this.currentPage / 2) + 1;
                    const visibleRange = 2;

                    for (let s = 1; s <= numSheets; s++) {
                        const sheetEl = document.getElementById(`sheet-${s}`);
                        if (!sheetEl) continue;

                        const isVisible = Math.abs(s - currentSheet) <= visibleRange;

                        if (!isVisible) {
                            sheetEl.style.display = 'none';
                            continue;
                        }

                        sheetEl.style.width = `${this.pageWidth}px`;
                        sheetEl.style.height = `${this.pageHeight}px`;

                        const frontSide = sheetEl.querySelector('.front-side');
                        const backSide = sheetEl.querySelector('.back-side');

                        if (this.orientation === 'landscape') {
                            sheetEl.style.position = 'absolute';
                            sheetEl.style.top = '0';
                            sheetEl.style.left = '50%';
                            sheetEl.style.transformOrigin = 'left center';
                            sheetEl.style.display = 'block';
                            sheetEl.style.opacity = '1';
                            sheetEl.style.pointerEvents = 'auto';

                            if (frontSide) {
                                frontSide.style.display = 'block';
                                frontSide.style.opacity = '1';
                            }
                            if (backSide) {
                                backSide.style.display = 'block';
                                backSide.style.opacity = '1';
                                backSide.style.transform = 'rotateY(180deg)';
                            }

                            const isFlipped = this.currentPage >= (2 * s - 1);
                            
                            if (isFlipped) {
                                sheetEl.style.transform = 'rotateY(-180deg)';
                            } else {
                                sheetEl.style.transform = 'rotateY(0deg)';
                            }

                            if (this.overrideZIndexSheet === s) {
                                sheetEl.style.zIndex = '99';
                            } else {
                                if (isFlipped) {
                                    sheetEl.style.zIndex = `${s}`;
                                } else {
                                    sheetEl.style.zIndex = `${numSheets - s + 1}`;
                                }
                            }
                        } else {
                            sheetEl.style.position = 'absolute';
                            sheetEl.style.top = '0';
                            sheetEl.style.left = '0';
                            sheetEl.style.transformOrigin = 'center center';
                            sheetEl.style.transform = 'none';

                            const isFrontActive = (this.currentPage === 2 * s - 2);
                            const isBackActive = (this.currentPage === 2 * s - 1);
                            const isActive = isFrontActive || isBackActive;

                            if (isActive) {
                                sheetEl.style.display = 'block';
                                sheetEl.style.opacity = '1';
                                sheetEl.style.zIndex = '10';
                                sheetEl.style.pointerEvents = 'auto';

                                if (frontSide) {
                                    frontSide.style.display = isFrontActive ? 'block' : 'none';
                                    frontSide.style.opacity = isFrontActive ? '1' : '0';
                                }
                                if (backSide) {
                                    backSide.style.display = isBackActive ? 'block' : 'none';
                                    backSide.style.opacity = isBackActive ? '1' : '0';
                                    backSide.style.transform = 'none';
                                }
                            } else {
                                sheetEl.style.display = 'none';
                                sheetEl.style.opacity = '0';
                                sheetEl.style.zIndex = '1';
                                sheetEl.style.pointerEvents = 'none';
                            }
                        }
                    }
                },

                getBookTransform() {
                    if (this.orientation === 'portrait') {
                        return `scale(${this.zoom})`;
                    }
                    let tx = 0;
                    if (this.zoom === 1.0) {
                        if (this.currentPage === 0) {
                            tx = -25;
                        } else if (this.currentPage === this.totalPages - 1 && this.totalPages % 2 === 0) {
                            tx = 25;
                        }
                    }
                    if (tx === 0) {
                        return `scale(${this.zoom})`;
                    }
                    return `scale(${this.zoom}) translateX(${tx}%)`;
                },

                triggerFlip(targetPage) {
                    if (this.orientation !== 'landscape') {
                        this.currentPage = targetPage;
                        localStorage.setItem(`book_bookmark_${config.bookId}`, this.currentPage);
                        this.renderLazyPages(this.currentPage);
                        this.updateSheetStyles();
                        return;
                    }

                    let flippingSheetIndex = 0;
                    if (targetPage > this.currentPage) {
                        flippingSheetIndex = Math.floor(targetPage / 2) + 1;
                    } else {
                        flippingSheetIndex = Math.floor(this.currentPage / 2) + 1;
                    }

                    this.overrideZIndexSheet = flippingSheetIndex;
                    this.currentPage = targetPage;
                    localStorage.setItem(`book_bookmark_${config.bookId}`, this.currentPage);
                    
                    this.renderLazyPages(this.currentPage);
                    this.updateSheetStyles();

                    clearTimeout(this.overrideTimeout);
                    this.overrideTimeout = setTimeout(() => {
                        this.overrideZIndexSheet = null;
                        this.updateSheetStyles();
                    }, 600);
                },

                handleBookClick(e) {
                    if (this.zoom > 1.0) return;

                    const bookEl = document.getElementById('book');
                    if (!bookEl) return;

                    const rect = bookEl.getBoundingClientRect();
                    const clickX = e.clientX - rect.left;
                    const bookW = rect.width;

                    if (clickX > bookW / 2) {
                        this.nextPage();
                    } else {
                        this.prevPage();
                    }
                },

                cancelInvisibleRenderTasks(visiblePages) {
                    Object.keys(this.renderTasks).forEach((pageStr) => {
                        const pageNum = parseInt(pageStr, 10);
                        if (!visiblePages.has(pageNum)) {
                            const task = this.renderTasks[pageNum];
                            if (task && typeof task.cancel === 'function') {
                                try {
                                    task.cancel();
                                    console.log(`Cancelled render task for page ${pageNum}`);
                                } catch (e) {}
                            }
                            delete this.renderTasks[pageNum];
                            this.renderedPages.delete(pageNum);
                        }
                    });
                },

                renderLazyPages(flipbookPageIndex) {
                    const pagesToLoad = new Set();
                    
                    pagesToLoad.add(flipbookPageIndex + 1);
                    
                    if (this.orientation === 'landscape') {
                        if (flipbookPageIndex > 0) {
                            pagesToLoad.add(flipbookPageIndex + 2);
                        }
                        
                        pagesToLoad.add(flipbookPageIndex + 3);
                        pagesToLoad.add(flipbookPageIndex + 4);
                        
                        if (flipbookPageIndex > 1) {
                            pagesToLoad.add(flipbookPageIndex);
                            pagesToLoad.add(flipbookPageIndex - 1);
                        }
                    } else {
                        pagesToLoad.add(flipbookPageIndex + 2);
                        if (flipbookPageIndex > 0) {
                            pagesToLoad.add(flipbookPageIndex);
                        }
                    }
                    
                    this.cancelInvisibleRenderTasks(pagesToLoad);

                    Array.from(pagesToLoad)
                        .filter(num => num >= 1 && num <= this.totalPages)
                        .forEach(num => this.renderPageCanvas(num));
                },

                renderPageCanvasPromise(pageNumber) {
                    if (this.renderedPages.has(pageNumber)) {
                        return Promise.resolve();
                    }
                    this.renderedPages.add(pageNumber);

                    if (this.renderTasks[pageNumber]) {
                        return Promise.resolve();
                    }

                    return pdfDoc.getPage(pageNumber).then((page) => {
                        if (!this.renderedPages.has(pageNumber)) {
                            return;
                        }

                        const canvas = document.getElementById('canvas-page-' + pageNumber);
                        if (!canvas) {
                            this.renderedPages.delete(pageNumber);
                            return;
                        }

                        const context = canvas.getContext('2d');
                        const viewport = page.getViewport({ scale: 2.0 });
                        canvas.height = viewport.height;
                        canvas.width = viewport.width;

                        const renderContext = {
                            canvasContext: context,
                            viewport: viewport
                        };

                        const renderTask = page.render(renderContext);
                        this.renderTasks[pageNumber] = renderTask;

                        const spinner = document.getElementById('spinner-page-' + pageNumber);
                        if (spinner) {
                            spinner.style.display = 'flex';
                        }

                        return renderTask.promise.then(() => {
                            delete this.renderTasks[pageNumber];
                            if (spinner) {
                                spinner.style.display = 'none';
                            }
                        }).catch((err) => {
                            delete this.renderTasks[pageNumber];
                            this.renderedPages.delete(pageNumber);
                            if (spinner) {
                                spinner.style.display = 'flex';
                            }
                            if (err.name !== 'RenderingCancelledException' && err.message !== 'Rendering cancelled, closed or replaced.') {
                                throw err;
                            }
                        });
                    }).catch((err) => {
                        if (err.name !== 'RenderingCancelledException' && err.message !== 'Rendering cancelled, closed or replaced.') {
                            console.error('Error rendering page:', pageNumber, err);
                            this.renderedPages.delete(pageNumber);
                            throw err;
                        }
                    });
                },

                renderPageCanvas(pageNumber) {
                    this.renderPageCanvasPromise(pageNumber).catch((err) => {
                        console.error('Lazy render error:', pageNumber, err);
                    });
                },

                nextPage() {
                    let nextVal = this.currentPage;
                    if (this.orientation === 'landscape') {
                        if (this.currentPage === 0) {
                            nextVal = 1;
                        } else {
                            nextVal = Math.min(this.totalPages - 1, this.currentPage + 2);
                        }
                    } else {
                        nextVal = Math.min(this.totalPages - 1, this.currentPage + 1);
                    }
                    if (nextVal !== this.currentPage) {
                        this.triggerFlip(nextVal);
                    }
                },
                prevPage() {
                    let prevVal = this.currentPage;
                    if (this.orientation === 'landscape') {
                        if (this.currentPage <= 2) {
                            prevVal = 0;
                        } else {
                            prevVal = Math.max(0, this.currentPage - 2);
                        }
                    } else {
                        prevVal = Math.max(0, this.currentPage - 1);
                    }
                    if (prevVal !== this.currentPage) {
                        this.triggerFlip(prevVal);
                    }
                },
                goToPage(num) {
                    if (num >= 0 && num < this.totalPages && num !== this.currentPage) {
                        let target = num;
                        if (this.orientation === 'landscape' && target > 0) {
                            if (target === this.totalPages - 1 && this.totalPages % 2 !== 0) {
                                // Keep last spread
                            } else if (target % 2 === 0) {
                                target = target - 1;
                            }
                        }
                        this.triggerFlip(target);
                    }
                },
                toggleFullscreen() {
                    const el = this.$refs.readerContainer;
                    if (!document.fullscreenElement) {
                        if (el.requestFullscreen) {
                            el.requestFullscreen().catch(() => {
                                this.isFullscreen = true;
                                this.$nextTick(() => window.dispatchEvent(new Event('resize')));
                            });
                        } else if (el.webkitRequestFullscreen) {
                            el.webkitRequestFullscreen();
                        } else {
                            this.isFullscreen = true;
                            this.$nextTick(() => window.dispatchEvent(new Event('resize')));
                        }
                    } else {
                        if (document.exitFullscreen) {
                            document.exitFullscreen();
                        } else if (document.webkitExitFullscreen) {
                            document.webkitExitFullscreen();
                        }
                        this.isFullscreen = false;
                        this.$nextTick(() => window.dispatchEvent(new Event('resize')));
                    }
                }
            };
        });
    };

    if (window.Alpine) {
        initAlpineReader();
    } else {
        document.addEventListener('alpine:init', initAlpineReader);
    }
</script>

<div class="space-y-6" x-data="reader({
    bookId: {{ Js::from($ebook->id) }},
    streamUrl: {{ Js::from($streamUrl) }},
    title: {{ Js::from($ebook->title) }},
    workerUrl: '{{ asset('js/pdf.worker.min.js') }}'
})" x-init="initReader()" @keydown.right.window="nextPage()" @keydown.left.window="prevPage()" @fullscreenchange.window="isFullscreen = !!document.fullscreenElement; if (!isFullscreen) { resetZoom(); } $nextTick(() => window.dispatchEvent(new Event('resize')))">

    <!-- Diagnostic Log for Debugging -->
    <div id="debug-error-message" style="display: none; background-color: #fee2e2; border: 1px solid #f87171; color: #991b1b; padding: 16px; border-radius: 12px; font-family: monospace; font-size: 13px; white-space: pre-wrap; margin-bottom: 16px; z-index: 9999;">
        <strong>Diagnostic Log:</strong>
    </div>

    <!-- Header bar -->
    <header class="ebook-page-header">
        <div>
            <p class="ebook-eyebrow">Interactive eBook Reader</p>
            <h1 class="ebook-title">{{ $ebook->title }}</h1>
            <p class="ebook-subtitle">By {{ $ebook->author ?: 'AMIS Faculty' }} · Assigned to {{ $ebook->grade_level }}</p>
        </div>
        <div class="ebook-actions">
            <button @click="toggleFullscreen()" class="ebook-btn ebook-btn-muted">
                <span class="inline-flex items-center gap-1.5">
                    <span x-show="!isFullscreen" class="inline-flex">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-maximize-2"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
                    </span>
                    <span x-show="isFullscreen" x-cloak class="inline-flex">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-minimize-2"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="14" y1="10" x2="21" y2="3"/><line x1="10" y1="14" x2="3" y2="21"/></svg>
                    </span>
                    <span x-text="isFullscreen ? 'Exit Fullscreen' : 'Fullscreen'">Fullscreen</span>
                </span>
            </button>
            <a href="{{ route('student.ebooks') }}" class="ebook-btn ebook-btn-muted" style="display: inline-flex; align-items: center; gap: 0.35rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                <span>Close Reader</span>
            </a>
        </div>
    </header>

    <!-- Spinner Loading State -->
    <div x-show="loading" class="ebook-loading flex flex-col items-center justify-center">
        <div class="ebook-spinner"></div>
        <div class="text-center">
            <h2 style="font-size: 1.2rem; font-weight: 900; color: #0f172a; margin-top: 1.25rem; margin-bottom: 0.25rem;">Preparing Interactive eBook...</h2>
            <p style="font-size: 0.875rem; font-weight: 700; color: #64748b;" x-text="loadingStatus">Downloading document and rendering pages...</p>
            
            <div style="width: 240px; height: 8px; background: #e2e8f0; border-radius: 9999px; margin-top: 1rem; margin-left: auto; margin-right: auto; overflow: hidden; position: relative;">
                <div style="height: 100%; background: #be185d; border-radius: 9999px; transition: width 0.3s ease-out;"
                     :style="`width: ${loadingPercent}%`"></div>
            </div>
        </div>
    </div>

    <!-- Flipbook Container -->
    <div x-show="!loading" x-ref="readerContainer" class="ebook-reader-stage" :class="isFullscreen ? 'fixed inset-0 z-50 rounded-none' : ''" @contextmenu="handleContextMenu($event)" @click="showContextMenu = false">
        
        <!-- Floating Fullscreen Toolbar -->
        <div class="floating-toolbar" x-show="isFullscreen" x-transition x-cloak>
            <button @click="zoomOut()" class="toolbar-btn" title="Zoom Out" :disabled="zoom <= 1.0">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-zoom-out"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
            </button>
            <span class="toolbar-text" x-text="Math.round(zoom * 100) + '%'"></span>
            <button @click="zoomIn()" class="toolbar-btn" title="Zoom In" :disabled="zoom >= 2.5">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-zoom-in"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
            </button>
            <button @click="resetZoom()" class="toolbar-btn" title="Reset Zoom" :disabled="zoom === 1.0">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-refresh-cw"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/></svg>
            </button>
            <div class="toolbar-separator"></div>
            <button @click="toggleFullscreen()" class="toolbar-btn toolbar-btn-highlight" :title="isFullscreen ? 'Exit Fullscreen' : 'Fullscreen'">
                <span x-show="!isFullscreen" class="inline-flex">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-maximize-2"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
                </span>
                <span x-show="isFullscreen" x-cloak class="inline-flex">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-minimize-2"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="14" y1="10" x2="21" y2="3"/><line x1="10" y1="14" x2="3" y2="21"/></svg>
                </span>
            </button>
        </div>

        <!-- Custom Right-Click Context Menu -->
        <div id="context-menu" 
             x-show="isFullscreen && showContextMenu" 
             x-transition 
             x-cloak
             @click.away="showContextMenu = false"
             style="position: absolute; background: rgba(15, 23, 42, 0.95); border: 1px solid rgba(255, 255, 255, 0.15); color: white; border-radius: 12px; padding: 0.5rem 0; width: 12rem; z-index: 50; font-size: 0.875rem; font-weight: 600; user-select: none;"
             :style="`left: ${contextMenuX}px; top: ${contextMenuY}px;`">
            <button @click="zoomIn(); showContextMenu = false;" style="width: 100%; text-align: left; padding: 0.5rem 1rem; background: transparent; border: none; color: white; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;" :disabled="zoom >= 2.5">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg> Zoom In
            </button>
            <button @click="zoomOut(); showContextMenu = false;" style="width: 100%; text-align: left; padding: 0.5rem 1rem; background: transparent; border: none; color: white; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;" :disabled="zoom <= 1.0">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg> Zoom Out
            </button>
            <button @click="resetZoom(); showContextMenu = false;" style="width: 100%; text-align: left; padding: 0.5rem 1rem; background: transparent; border: none; color: white; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;" :disabled="zoom === 1.0">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/></svg> Reset Zoom
            </button>
            <div style="border-top: 1px solid rgba(255,255,255,0.15); margin: 0.25rem 0;"></div>
            <button @click="toggleFullscreen(); showContextMenu = false;" style="width: 100%; text-align: left; padding: 0.5rem 1rem; background: transparent; border: none; color: white; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="14" y1="10" x2="21" y2="3"/><line x1="10" y1="14" x2="3" y2="21"/></svg> Exit Fullscreen
            </button>
        </div>

        <!-- Scrollable Zoom & Pan Container -->
        <div id="book-zoom-container" class="w-full flex-1 overflow-auto flex relative select-none"
             @mousedown="startPan($event)"
             @mousemove="pan($event)"
             @mouseup="endPan()"
             @mouseleave="endPan()"
             @touchstart="startPan($event)"
             @touchmove="pan($event)"
             @touchend="endPan()"
             @wheel="handleWheel($event)"
             :class="zoom > 1 ? 'is-zoomed cursor-grab active:cursor-grabbing' : 'items-center justify-center'"
             style="max-height: calc(100vh - 180px); min-height: 520px;">
            
            <div id="book-wrapper" class="flex items-center justify-center" 
                 :style="`width: ${bookWidth * zoom}px; height: ${bookHeight * zoom}px; box-sizing: content-box !important;`"
                 style="padding: 2rem;">
                <div id="book" class="opacity-0 mx-auto"
                     @click="handleBookClick($event)"
                     :style="`width: ${bookWidth}px; height: ${bookHeight}px; transform: ${getBookTransform()}; transform-origin: center center;`">
                    <!-- Pages injected dynamically -->
                </div>
            </div>
        </div>

        <!-- Bottom Control Panel -->
        <div class="ebook-reader-controls">
            <div class="ebook-reader-control-group">
                <button @click="goToPage(0)" :disabled="currentPage == 0" class="ebook-icon-btn disabled:opacity-40 disabled:cursor-not-allowed" style="background: transparent; color: #475569;" title="First Page">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevrons-left"><polyline points="11 17 6 12 11 7"/><polyline points="18 17 13 12 18 7"/></svg>
                </button>
                <button @click="prevPage()" :disabled="currentPage == 0" class="ebook-btn ebook-btn-primary disabled:opacity-40 disabled:cursor-not-allowed" style="min-height: 34px; padding: 6px 12px; display: inline-flex; align-items: center; gap: 0.25rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-left"><polyline points="15 18 9 12 15 6"/></svg>
                    <span>Prev</span>
                </button>
            </div>

            <div class="ebook-reader-page-label select-none flex items-center justify-center gap-1.5" style="font-weight: 800; font-size: 13px;">
                <span>Page</span>
                <input type="number" 
                       :value="currentPage + 1" 
                       @change="let val = parseInt($event.target.value, 10); if(val > 0 && val <= totalPages) { goToPage(val - 1); } else { $event.target.value = currentPage + 1; }"
                       style="width: 3.25rem; height: 1.85rem; text-align: center; border-radius: 8px; border: 1.5px solid #cbd5e1; background: white; font-weight: 900; font-size: 14px; color: #be185d; outline: none;"
                >
                <template x-if="orientation === 'landscape' && currentPage > 0 && currentPage + 1 < totalPages">
                    <span style="font-weight: 800; color: #1e293b;">
                        - <span x-text="currentPage + 2"></span>
                    </span>
                </template>
                <span style="color: #94a3b8; font-size: 11px;">/ <span x-text="totalPages"></span></span>
            </div>

            <div class="ebook-reader-control-group">
                <button @click="nextPage()" :disabled="currentPage == totalPages - 1" class="ebook-btn ebook-btn-primary disabled:opacity-40 disabled:cursor-not-allowed" style="min-height: 34px; padding: 6px 12px; display: inline-flex; align-items: center; gap: 0.25rem;">
                    <span>Next</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
                <button @click="goToPage(totalPages - 1)" :disabled="currentPage == totalPages - 1" class="ebook-icon-btn disabled:opacity-40 disabled:cursor-not-allowed" style="background: transparent; color: #475569;" title="Last Page">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevrons-right"><polyline points="13 17 18 12 13 7"/><polyline points="6 17 11 12 6 7"/></svg>
                </button>
            </div>
        </div>

    </div>
</div>

</x-student-layout>
