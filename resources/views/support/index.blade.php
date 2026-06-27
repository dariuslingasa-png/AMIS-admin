<x-guest-layout title="AMIS Support Center">
    {{-- Header --}}
    <header class="bg-white border-b border-gray-100 dark:bg-gray-800 dark:border-gray-700">
        <div class="mx-auto px-4 sm:px-6 lg:px-8" style="max-width: 1280px; width: 100%;">
            <div class="flex h-16 items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/AMIS_Logo.png') }}" class="h-10 w-10 object-contain" alt="AMIS Logo">
                    <div>
                        <span class="text-lg font-black text-gray-900 dark:text-white tracking-tight">AMIS Support Center</span>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-4 sm:gap-6">
                    <a href="{{ route('support.index') }}" class="text-xs sm:text-sm font-bold text-gray-600 hover:text-emerald-600 dark:text-gray-300 dark:hover:text-emerald-400">Home</a>
                    <a href="#faq" class="text-xs sm:text-sm font-bold text-gray-650 hover:text-emerald-600 dark:text-gray-300 dark:hover:text-emerald-400">FAQ</a>
                    <a href="{{ route('support.create') }}" class="text-xs sm:text-sm font-bold text-gray-655 hover:text-emerald-600 dark:text-gray-300 dark:hover:text-emerald-400">Submit Ticket</a>
                </div>
            </div>
        </div>
    </header>


    {{-- Hero Section --}}
    <section class="relative bg-gradient-to-br from-emerald-50 via-emerald-100/50 to-sky-50 dark:from-gray-900 dark:to-gray-800 py-16 sm:py-20 border-b border-gray-100 dark:border-gray-700">
        <div class="mx-auto px-4 text-center" style="max-width: 896px; width: 100%;">
            <span class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">Welcome to our support desk</span>
            <h1 class="mt-4 text-4xl sm:text-5xl font-black text-gray-900 dark:text-white tracking-tight leading-none">
                How can we help you today?
            </h1>
            <p class="mt-6 text-lg font-medium text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                Need help with your student account, forgot your password, or have an enrollment inquiry? Submit a ticket or browse our resources below.
            </p>
        </div>
    </section>

    {{-- Inquiry Counter / Status Bar --}}
    <div class="border-b border-gray-100 bg-white dark:border-gray-800 dark:bg-gray-850">
        <div class="mx-auto px-4 py-5" style="max-width: 1152px; width: 100%;">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3 text-center sm:text-left divide-y divide-gray-100 sm:divide-y-0 sm:divide-x dark:divide-gray-800">
                <div class="flex items-center justify-center sm:justify-start gap-4 py-2 sm:py-0 sm:px-6 first:pl-0">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400 shadow-sm">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div>
                        <div class="text-sm font-extrabold text-gray-900 dark:text-white tracking-tight">1,500+ Enrollment Submissions</div>
                        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 mt-0.5">Processed successfully</div>
                    </div>
                </div>
                <div class="flex items-center justify-center sm:justify-start gap-4 py-4 sm:py-0 sm:px-6">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600 dark:bg-sky-950/30 dark:text-sky-400 shadow-sm">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    </div>
                    <div>
                        <div class="text-sm font-extrabold text-gray-900 dark:text-white tracking-tight">Official Support Channel</div>
                        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 mt-0.5">inquiries@amis.edu.ph</div>
                    </div>
                </div>
                <div class="flex items-center justify-center sm:justify-start gap-4 py-2 sm:py-0 sm:px-6 last:pr-0">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/30 dark:text-amber-400 shadow-sm">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div>
                        <div class="text-sm font-extrabold text-gray-900 dark:text-white tracking-tight">Response Time: 24–48 Hours</div>
                        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 mt-0.5">Average ticket resolution</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Core Options Grid --}}
    <section class="mx-auto px-4 py-12 sm:py-16" style="max-width: 1152px; width: 100%;">
        {{-- Important Notice Banner --}}
        <div class="mb-8 p-4 rounded-xl bg-amber-50 border border-amber-100 dark:bg-amber-950/15 dark:border-amber-900/30 flex items-start gap-3 shadow-sm">
            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <p class="text-sm font-semibold text-amber-800 dark:text-amber-300 leading-relaxed">
                <strong>Important Notice:</strong> Before submitting a ticket, please check the <a href="#faq" class="text-emerald-600 dark:text-emerald-400 hover:underline font-bold">FAQ section below</a>. Most common concerns such as password resets and account access can be resolved immediately.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            
            {{-- Ticket --}}
            <div class="flex flex-col h-full p-8 rounded-2xl bg-white border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition duration-150">
                <div class="flex-1">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 mb-6">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    </div>
                    <h3 class="text-xl font-extrabold text-gray-900 dark:text-white">Submit a Ticket</h3>
                    <p class="mt-3 text-sm font-medium text-gray-500 dark:text-gray-400 leading-relaxed">
                        Forgot your password, need your credentials resent, or have portal issues? Fill out our support request form.
                    </p>
                </div>
                <div class="mt-8">
                    <a href="{{ route('support.create') }}" class="w-full student-primary-btn justify-center" style="display: flex;">
                        Submit a Request
                    </a>
                </div>
            </div>

            {{-- 24/7 Chatbot AI --}}
            <div class="flex flex-col h-full p-8 rounded-2xl bg-white border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition duration-150">
                <div class="flex-1">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400 mb-6">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
                    </div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-xl font-extrabold text-gray-900 dark:text-white">24/7 Chatbot AI</h3>
                        <span class="text-[10px] font-bold text-purple-600 bg-purple-50 px-2 py-0.5 rounded dark:bg-purple-950/30 dark:text-purple-400">SOON</span>
                    </div>
                    <p class="mt-3 text-sm font-medium text-gray-500 dark:text-gray-400 leading-relaxed">
                        Need instant help? Get answers to FAQs, logins, password reset instructions, and setup guides 24/7 from our AI chatbot.
                    </p>
                </div>
                <div class="mt-8">
                    <button class="w-full student-light-btn justify-center cursor-not-allowed opacity-50" disabled>
                        AI Chatbot (Coming Soon)
                    </button>
                </div>
            </div>

            {{-- Socials / IT support --}}
            <div class="flex flex-col h-full p-8 rounded-2xl bg-white border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition duration-150">
                <div class="flex-1">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 mb-6">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" /></svg>
                    </div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-xl font-extrabold text-gray-900 dark:text-white">Direct Support</h3>
                        <span class="text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded dark:bg-amber-950/30 dark:text-amber-400">9AM - 3PM</span>
                    </div>
                    <p class="mt-3 text-sm font-medium text-gray-500 dark:text-gray-400 leading-relaxed">
                        Get in touch directly with our administration and IT departments for immediate assistance and inquiries.
                    </p>
                </div>
                <div class="mt-8 flex flex-col gap-2">
                    <a href="mailto:inquiries@amis.edu.ph" class="w-full student-light-btn justify-start gap-3" style="padding: 10px 14px;">
                        <span class="text-base shrink-0">📧</span>
                        <span class="text-xs font-bold truncate">inquiries@amis.edu.ph</span>
                    </a>
                    <a href="https://m.me/almunawwaraislamicschool" target="_blank" class="w-full student-light-btn justify-start gap-3" style="padding: 10px 14px;">
                        <span class="text-base shrink-0">🤖</span>
                        <span class="text-xs font-bold truncate">AMIS - IT Chatbot</span>
                    </a>
                    <a href="https://www.facebook.com/almunawwaraislamicschool" target="_blank" class="w-full student-light-btn justify-start gap-3" style="padding: 10px 14px;">
                        <span class="text-base shrink-0">📱</span>
                        <span class="text-xs font-bold truncate">AMIS - Information Technology</span>
                    </a>
                </div>
            </div>

        </div>
    </section>

    {{-- FAQ Section --}}
    <section id="faq" class="bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700 py-16 sm:py-20">
        <div class="mx-auto px-4" style="max-width: 896px; width: 100%;">
            <div class="text-center mb-12">
                <span class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">Frequently Asked Questions</span>
                <h2 class="mt-3 text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">Got questions? We've got answers.</h2>
            </div>

            <div class="space-y-4" x-data="{ active: null }">
                
                {{-- FAQ 1 --}}
                <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
                    <button class="w-full flex items-center justify-between p-5 text-left font-bold text-gray-900 dark:text-white focus:outline-none"
                            @click="active = active === 1 ? null : 1">
                        <span>I forgot my password.</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" :class="active === 1 ? 'transform rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="p-5 border-t border-gray-100 dark:border-gray-700 text-sm font-semibold text-gray-600 dark:text-gray-300 leading-relaxed"
                         x-show="active === 1" x-cloak x-transition>
                        You can easily reset your password by submitting a support ticket here on the Support Center. Click on <a href="{{ route('support.create') }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">Submit Ticket</a>, choose <strong>Forgot Password</strong> as the concern type, fill in your details, and our IT team will reset it and send the new credentials to you.
                    </div>
                </div>

                {{-- FAQ 2 --}}
                <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
                    <button class="w-full flex items-center justify-between p-5 text-left font-bold text-gray-900 dark:text-white focus:outline-none"
                            @click="active = active === 2 ? null : 2">
                        <span>How do I access my Microsoft account?</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" :class="active === 2 ? 'transform rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="p-5 border-t border-gray-100 dark:border-gray-700 text-sm font-semibold text-gray-600 dark:text-gray-300 leading-relaxed"
                         x-show="active === 2" x-cloak x-transition>
                        Go to <a href="https://login.microsoftonline.com" target="_blank" class="text-emerald-600 dark:text-emerald-400 hover:underline">login.microsoftonline.com</a> and sign in using your official `@amis.edu.ph` email account and the temporary password provided during enrollment. If you need a password reset or cannot log in, submit a support request with the concern type <strong>Microsoft Account Issue</strong>.
                    </div>
                </div>

                {{-- FAQ 3 --}}
                <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
                    <button class="w-full flex items-center justify-between p-5 text-left font-bold text-gray-900 dark:text-white focus:outline-none"
                            @click="active = active === 3 ? null : 3">
                        <span>How do I join Microsoft Teams?</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" :class="active === 3 ? 'transform rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="p-5 border-t border-gray-100 dark:border-gray-700 text-sm font-semibold text-gray-600 dark:text-gray-300 leading-relaxed"
                         x-show="active === 3" x-cloak x-transition>
                        Download the Microsoft Teams app on your computer or mobile device, or access it via <a href="https://teams.microsoft.com" target="_blank" class="text-emerald-600 dark:text-emerald-400 hover:underline">teams.microsoft.com</a>. Sign in with your school Microsoft account (`@amis.edu.ph`). You will automatically see your assigned classes and channels.
                    </div>
                </div>

                {{-- FAQ 4 --}}
                <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
                    <button class="w-full flex items-center justify-between p-5 text-left font-bold text-gray-900 dark:text-white focus:outline-none"
                            @click="active = active === 4 ? null : 4">
                        <span>I did not receive my credentials.</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" :class="active === 4 ? 'transform rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="p-5 border-t border-gray-100 dark:border-gray-700 text-sm font-semibold text-gray-600 dark:text-gray-300 leading-relaxed"
                         x-show="active === 4" x-cloak x-transition>
                        If you are newly enrolled and have not received your `@amis.edu.ph` Microsoft account credentials, please submit a ticket using the <strong>Resend Credentials</strong> concern type. Provide your full student name, grade level, and AMIS ID (if available) so we can locate your record.
                    </div>
                </div>

                {{-- FAQ 5 --}}
                <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
                    <button class="w-full flex items-center justify-between p-5 text-left font-bold text-gray-900 dark:text-white focus:outline-none"
                            @click="active = active === 5 ? null : 5">
                        <span>How do I check my enrollment status?</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" :class="active === 5 ? 'transform rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="p-5 border-t border-gray-100 dark:border-gray-700 text-sm font-semibold text-gray-600 dark:text-gray-300 leading-relaxed"
                         x-show="active === 5" x-cloak x-transition>
                        You can check your status on the official enrollment portal or submit a support ticket under the <strong>Enrollment Concern</strong> category. Our admissions staff will verify your enrollment documents and reply with your current status.
                    </div>
                </div>

                {{-- FAQ 6 --}}
                <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
                    <button class="w-full flex items-center justify-between p-5 text-left font-bold text-gray-900 dark:text-white focus:outline-none"
                            @click="active = active === 6 ? null : 6">
                        <span>How do I submit payment proof?</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" :class="active === 6 ? 'transform rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="p-5 border-t border-gray-100 dark:border-gray-700 text-sm font-semibold text-gray-600 dark:text-gray-300 leading-relaxed"
                         x-show="active === 6" x-cloak x-transition>
                        While the payment portal is coming soon, you can upload proof of payment screenshots or receipts by submitting a support ticket under the <strong>Payment Concern</strong> category. Make sure to attach a clear screenshot or image of the transaction slip.
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="bg-white py-12 border-t border-gray-100 dark:bg-gray-850 dark:border-gray-800">
        <div class="mx-auto px-4 text-center" style="max-width: 1280px; width: 100%;">
            <div class="flex flex-col items-center gap-2">
                <span class="text-base font-extrabold text-gray-900 dark:text-white">AMIS Support Center</span>
                <div class="flex flex-wrap items-center justify-center gap-4 text-xs font-semibold text-gray-500 dark:text-gray-400 mt-1">
                    <a href="https://support.amis.edu.ph" class="hover:text-emerald-600 dark:hover:text-emerald-400">support.amis.edu.ph</a>
                    <span class="text-gray-300 dark:text-gray-700">|</span>
                    <a href="mailto:inquiries@amis.edu.ph" class="hover:text-emerald-600 dark:hover:text-emerald-400">inquiries@amis.edu.ph</a>
                </div>
                <p class="text-xs font-semibold text-gray-400 dark:text-gray-550 mt-4">
                    &copy; 2026 Al Munawwara Islamic School. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
</x-guest-layout>
