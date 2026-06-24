{{-- Interactive Floating Chatbot Widget --}}
<div x-data="{ 
    isOpen: false, 
    userInput: '',
    messages: [],
    isLoading: false,
    escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
    },
    formatMessage(value) {
        return this.escapeHtml(value).replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    },
    async sendMessage() {
        const text = this.userInput.trim();
        if (!text) return;
        
        // Push user message
        this.messages.push({ sender: 'user', text: text });
        this.userInput = '';
        this.isLoading = true;
        
        this.$nextTick(() => {
            const el = document.getElementById('chat-messages-container');
            if (el) el.scrollTop = el.scrollHeight;
        });

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const response = await fetch('/api/chatbot', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    messages: this.messages.slice(-10)
                })
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();
            const reply = data.reply || 'Sorry, I encountered an issue. Please try again.';
            
            this.messages.push({ sender: 'bot', text: reply });
        } catch (error) {
            console.error('Chatbot error:', error);
            this.messages.push({ sender: 'bot', text: 'Sorry, I am offline at the moment. Please try again later or click **Submit a Request** above.' });
        } finally {
            this.isLoading = false;
            this.$nextTick(() => {
                const el = document.getElementById('chat-messages-container');
                if (el) el.scrollTop = el.scrollHeight;
            });
        }
    },
    resetChat() {
        this.messages = [];
        this.userInput = '';
        this.isLoading = false;
    }
}" class="fixed bottom-6 right-6 z-50 flex flex-col items-end print:hidden">

    <!-- Chat Panel -->
    <div x-show="isOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-10 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-10 scale-95"
         class="absolute bottom-16 right-0 w-[calc(100vw-2rem)] sm:w-96 h-[480px] max-h-[75vh] bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-2xl flex flex-col overflow-hidden"
         style="display: none;">
         
         <!-- Panel Header -->
         <div class="px-4 py-3 bg-gradient-to-r from-emerald-700 to-teal-900 text-white flex items-center justify-between shadow-sm shrink-0">
             <div class="flex items-center gap-2">
                 <div class="w-8 h-8 rounded-full bg-emerald-500/20 border border-emerald-400/30 flex items-center justify-center">
                     <!-- Lucide bot icon representation -->
                     <svg class="w-4 h-4 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                         <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                     </svg>
                 </div>
                 <div>
                     <strong class="block text-sm">AMIS Assistant</strong>
                     <span class="text-[10px] text-emerald-300 font-bold uppercase tracking-wider flex items-center gap-1">
                         <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Online
                     </span>
                 </div>
             </div>
             <div class="flex items-center gap-2">
                 <button type="button" @click="resetChat()" class="text-emerald-100 hover:text-white transition p-1 cursor-pointer" title="Reset Conversation">
                     <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                         <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H18" />
                     </svg>
                 </button>
                 <button type="button" @click="isOpen = false" class="text-emerald-100 hover:text-white text-xl leading-none p-1 cursor-pointer">&times;</button>
             </div>
         </div>

         <!-- Messages Container -->
         <div id="chat-messages-container" class="flex-1 p-4 overflow-y-auto flex flex-col justify-start gap-3 bg-slate-50 dark:bg-gray-900 scrollbar-thin">
             <!-- Background Placeholder when empty -->
             <template x-if="messages.length === 0">
                 <div class="flex-1 flex flex-col items-center justify-center p-6 text-center select-none h-full min-h-[300px]">
                     <p class="text-sm sm:text-base font-bold text-gray-400 dark:text-gray-500 leading-relaxed max-w-[240px]">
                         Ask me anything - I am here to help! Alhamdulillah
                     </p>
                 </div>
             </template>

             <!-- Conversation bubbles -->
             <template x-for="(msg, index) in messages" :key="index">
                 <div :class="msg.sender === 'user' ? 'justify-end' : 'justify-start'" class="flex">
                     <div :class="msg.sender === 'user' ? 'bg-emerald-600 text-white rounded-br-none' : 'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-150 border border-gray-150 dark:border-gray-700 rounded-bl-none'" 
                          class="max-w-[85%] rounded-2xl px-4 py-2.5 text-xs sm:text-sm font-bold shadow-xs leading-relaxed">
                          <span class="whitespace-pre-line" x-html="formatMessage(msg.text)"></span>
                     </div>
                 </div>
             </template>

             <!-- Typing Indicator -->
             <template x-if="isLoading">
                 <div class="flex justify-start">
                     <div class="bg-white dark:bg-gray-800 text-gray-400 dark:text-gray-500 border border-gray-150 dark:border-gray-700 rounded-2xl rounded-bl-none px-4 py-2 flex items-center gap-1 shadow-xs">
                         <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-bounce" style="animation-delay: 0ms"></span>
                         <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-bounce" style="animation-delay: 150ms"></span>
                         <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-bounce" style="animation-delay: 300ms"></span>
                     </div>
                 </div>
             </template>
         </div>



        <!-- Input Area -->
        <form @submit.prevent="sendMessage()" class="p-3 bg-white dark:bg-gray-800 border-t border-gray-150 dark:border-gray-700 flex flex-col gap-1 shrink-0">
            <div class="flex items-center gap-2 w-full">
                <input type="text" 
                       x-model="userInput" 
                       maxlength="500"
                       :disabled="isLoading"
                       placeholder="Type a message..." 
                       class="flex-1 h-10 px-3.5 rounded-full border border-gray-250 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-xs sm:text-sm font-semibold text-gray-850 dark:text-gray-100 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 dark:focus:ring-emerald-950/30 transition placeholder:text-gray-400 disabled:opacity-60 disabled:cursor-not-allowed">
                <button type="submit" 
                        :disabled="!userInput.trim() || isLoading"
                        class="flex items-center justify-center w-10 h-10 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white disabled:bg-gray-100 disabled:text-gray-400 dark:disabled:bg-gray-800 dark:disabled:text-gray-600 disabled:cursor-not-allowed transition cursor-pointer shrink-0">
                    <svg class="w-4.5 h-4.5 transform rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </button>
            </div>
            <div class="flex justify-end px-2">
                <span class="text-[10px] font-bold text-gray-400 dark:text-gray-550" x-text="userInput.length + ' / 500'"></span>
            </div>
        </form>
    </div>

    <!-- Floating Action Button & Need Help Pill -->
    <div class="flex items-center gap-2 cursor-pointer" @click="isOpen = !isOpen">
         <span x-show="!isOpen" 
               x-transition:enter="transition ease-out duration-300"
               x-transition:enter-start="opacity-0 translate-x-4"
               x-transition:enter-end="opacity-100 translate-x-0"
               class="bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-250 border border-gray-250 dark:border-gray-700 px-3.5 py-1.5 rounded-full text-xs font-extrabold shadow-md whitespace-nowrap animate-bounce select-none">
             AMIS-ian, Need help??
         </span>
         
         <button class="flex items-center justify-center w-14 h-14 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white shadow-xl hover:scale-105 active:scale-95 transition-all duration-200 cursor-pointer"
                 style="box-shadow: 0 8px 30px rgba(5, 150, 105, 0.35);">
             <!-- Close icon if open, else chat bubble icon -->
             <template x-if="isOpen">
                 <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                     <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                 </svg>
             </template>
             <template x-if="!isOpen">
                 <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                     <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                 </svg>
             </template>
         </button>
    </div>

</div>
