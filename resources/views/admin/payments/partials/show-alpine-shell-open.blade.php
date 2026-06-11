        <div x-data="{
             preview: false,
             src: '',
             label: '',
             pdf: false,
             zoom: 1,
             panning: false,
             panEl: null,
             panX: 0,
             panY: 0,
             panLeft: 0,
             panTop: 0,
             approveModal: false,
             rejectModal: false,
             showRejectForm: false,
             currentPayment: null,
             currentInvoice: '',
             predictedOr: '',
             orNumber: '',
             remarks: '',
             financeMethod: '',
             remittanceSource: '',
             financePaymentDate: '',
             financeReferenceNo: '',
             isSubmitting: false,
             familySubmitting: false,
             openPreview(url, title, isPdf) {
                 this.preview = true;
                 this.src = url;
                 this.label = title;
                 this.pdf = isPdf;
                 this.zoom = 1;
             },
             closePreview() {
                 this.preview = false;
                 this.zoom = 1;
                 this.stopPan();
                 this.currentPayment = null;
             },
             zoomIn() {
                 this.zoom = Math.min(3, Number((this.zoom + 0.1).toFixed(2)));
             },
             zoomOut() {
                 this.zoom = Math.max(0.1, Number((this.zoom - 0.1).toFixed(2)));
             },
             resetZoom() {
                 this.zoom = 1;
             },
             downloadBlob(blob, filename) {
                 const objectUrl = URL.createObjectURL(blob);
                 const link = document.createElement('a');
                 link.href = objectUrl;
                 link.download = filename;
                 document.body.appendChild(link);
                 link.click();
                 link.remove();
                 setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);
             },
             async downloadFile(url, filename) {
                 const response = await fetch(url, { credentials: 'same-origin' });
                 if (!response.ok) throw new Error('Download request failed.');
                 const blob = await response.blob();
                 this.downloadBlob(blob, filename);
             },
             loadImage(url) {
                 return new Promise((resolve, reject) => {
                     const img = new Image();
                     img.crossOrigin = 'Anonymous';
                     img.onload = () => resolve(img);
                     img.onerror = reject;
                     img.src = url;
                 });
             },
             startPan(event) {
                 if (this.pdf) return;
                 const point = event.touches ? event.touches[0] : event;
                 this.panning = true;
                 this.panEl = event.currentTarget;
                 this.panX = point.pageX;
                 this.panY = point.pageY;
                 this.panLeft = this.panEl.scrollLeft;
                 this.panTop = this.panEl.scrollTop;
                 this.panEl.classList.add('cursor-grabbing');
             },
             movePan(event) {
                 if (!this.panning || !this.panEl) return;
                 event.preventDefault();
                 const point = event.touches ? event.touches[0] : event;
                 this.panEl.scrollLeft = this.panLeft - (point.pageX - this.panX);
                 this.panEl.scrollTop = this.panTop - (point.pageY - this.panY);
             },
             stopPan() {
                 if (this.panEl) this.panEl.classList.remove('cursor-grabbing');
                 this.panning = false;
                 this.panEl = null;
             },
             async downloadPdf() {
                 if (!this.src) return;
                 const url = this.src;
                 const filename = (this.label || 'document').replace(/[^a-zA-Z0-9]/g, '_') + '.pdf';
                 const btn = document.getElementById('download-pdf-btn');
                 const originalText = btn ? btn.innerHTML : '';

                 if (btn) {
                     btn.disabled = true;
                     btn.innerHTML = '<i data-lucide=\'loader-2\' class=\'h-3.5 w-3.5 animate-spin\'></i> Downloading...';
                     if (window.lucide) window.lucide.createIcons();
                 }

                 if (this.pdf) {
                     try {
                         await this.downloadFile(url, filename);
                     } catch (e) {
                         console.error(e);
                         const link = document.createElement('a');
                         link.href = url;
                         link.download = filename;
                         link.rel = 'noopener noreferrer';
                         document.body.appendChild(link);
                         link.click();
                         link.remove();
                     } finally {
                         if (btn) {
                             btn.disabled = false;
                             btn.innerHTML = originalText;
                             if (window.lucide) window.lucide.createIcons();
                         }
                     }
                     return;
                 }
                 try {
                     const { jsPDF } = window.jspdf;
                     const img = await this.loadImage(url);
                     const pdf = new jsPDF({
                         orientation: img.width > img.height ? 'landscape' : 'portrait',
                         unit: 'px',
                         format: [img.width, img.height]
                     });
                     pdf.addImage(img, 'JPEG', 0, 0, img.width, img.height);
                     pdf.save(filename);
                 } catch (e) {
                     console.error(e);
                     await this.downloadFile(url, this.label || 'payment-proof');
                 } finally {
                     if (btn) {
                         btn.disabled = false;
                         btn.innerHTML = originalText;
                         if (window.lucide) window.lucide.createIcons();
                     }
                 }
             }
         }"
         x-effect="document.body.classList.toggle('overflow-hidden', preview)"
         @keydown.escape.window="closePreview()"
         @mouseup.window="stopPan()"
         @touchend.window="stopPan()"
         @open-preview-event.window="openPreview($event.detail.url, $event.detail.title, $event.detail.isPdf)"
         class="space-y-6">
