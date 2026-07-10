<!-- Image Preview Modal Overlay -->
<div x-show="previewPhoto" 
     x-cloak 
     x-init="$watch('previewPhoto', v => v && $nextTick(() => window.lucide && window.lucide.createIcons()))"
     @keydown.escape.window="previewPhoto = null"
     style="position: fixed !important; inset: 0 !important; z-index: 9999 !important; display: flex !important; align-items: center !important; justify-content: center !important; padding: 1.5rem !important; background: rgba(15, 23, 42, 0.75) !important; backdrop-filter: blur(4px) !important;"
     x-transition:enter="transition ease-out duration-250"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
     
     <div @click.outside="previewPhoto = null"
          style="position: relative !important; background: white !important; border-radius: 24px !important; padding: 1.25rem !important; max-width: 420px !important; width: 100% !important; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important; display: flex !important; flex-direction: column !important; gap: 1rem !important; margin: auto !important;"
          x-transition:enter="transition ease-out duration-300 transform"
          x-transition:enter-start="opacity-0 scale-95"
          x-transition:enter-end="opacity-100 scale-100"
          x-transition:leave="transition ease-in duration-200 transform"
          x-transition:leave-start="opacity-100 scale-100"
          x-transition:leave-end="opacity-0 scale-95">
          
          <!-- Modal Header: Title + Close Button -->
          <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; border-bottom: 1.5px solid #f1f5f9; padding-bottom: 0.75rem; margin-bottom: 0.25rem;">
              <h4 style="font-size: 1.05rem; font-weight: 900; color: #0f172a; margin: 0;">Teacher Profile</h4>
              <button @click="previewPhoto = null" 
                      style="border: none; background: #f1f5f9; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b; transition: all 0.15s;"
                      onmouseover="this.style.background='#e2e8f0'; this.style.color='#0f172a'"
                      onmouseout="this.style.background='#f1f5f9'; this.style.color='#64748b'">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
              </button>
          </div>

          <!-- Image -->
          <div style="width: 100%; border-radius: 18px; overflow: hidden; background: #ecfdf5; border: 2px solid #a7f3d0; display: flex; align-items: center; justify-content: center;">
              <img :src="previewPhoto?.url" :alt="previewPhoto?.name" style="width: 100%; height: auto; max-height: 60vh; object-fit: contain; display: block;">
          </div>

          <!-- Title / Name -->
          <div style="text-align: center; margin-bottom: 0.25rem;">
              <h3 style="font-size: 1.25rem; font-weight: 900; color: #0f172a; margin: 0;" x-text="previewPhoto?.name"></h3>
              <p style="font-size: 0.8rem; font-weight: 850; color: #059669; margin: 0.25rem 0 0; text-transform: uppercase; letter-spacing: 0.05em;" x-text="previewPhoto?.role"></p>
          </div>

          <!-- Subject & Schedule Details -->
          <template x-if="previewPhoto?.subject">
              <div style="padding: 0.85rem 1rem; background: #f0fdfa; border: 1.5px solid #ccfbf1; border-radius: 16px; width: 100%; box-sizing: border-box; text-align: left; display: flex; flex-direction: column; gap: 0.45rem;">
                  <div style="display: flex; align-items: center; gap: 0.5rem;">
                      <i data-lucide="book-open" style="width: 14px; height: 14px; color: #0f766e;"></i>
                      <span style="font-size: 10px; font-weight: 800; text-transform: uppercase; color: #0d9488; letter-spacing: 0.05em;">Subject Details</span>
                  </div>
                  <div style="font-size: 14px; font-weight: 800; color: #0f172a;" x-text="previewPhoto.subject"></div>
                  <div style="margin-top: 0.15rem; display: flex; align-items: center; gap: 0.75rem; border-top: 1px dashed #ccfbf1; padding-top: 0.45rem;">
                      <div style="display: flex; align-items: center; gap: 0.3rem;">
                          <i data-lucide="calendar" style="width: 12px; height: 12px; color: #0f766e;"></i>
                          <span style="font-size: 12px; font-weight: 700; color: #0f766e;" x-text="previewPhoto.day"></span>
                      </div>
                      <div style="display: flex; align-items: center; gap: 0.3rem;">
                          <i data-lucide="clock" style="width: 12px; height: 12px; color: #0f766e;"></i>
                          <span style="font-size: 12px; font-weight: 700; color: #0f766e;" x-text="previewPhoto.time"></span>
                      </div>
                  </div>
              </div>
          </template>
     </div>
</div>
