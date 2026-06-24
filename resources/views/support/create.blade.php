<x-guest-layout title="Submit a Support Request | AMIS Support">
    <style>
        /* Automatically force uppercase on user typed input values (except email) */
        input[type="text"], textarea {
            text-transform: uppercase;
        }
        /* Keep placeholders readable, normal case, and high-contrast */
        input[type="text"]::placeholder, textarea::placeholder, input[type="email"]::placeholder {
            text-transform: none !important;
            color: #4b5563 !important; /* Tailwind gray-600 for high-contrast readability */
            opacity: 1 !important;
        }
        
        /* Make form labels highly readable, mixed-case, and high-contrast */
        .student-form label span {
            text-transform: none !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            color: #374151 !important; /* Tailwind gray-800 for high contrast on light bg */
            letter-spacing: normal !important;
            margin-bottom: 4px !important;
        }

        /* Define form input/select borders more clearly for better visibility */
        .student-form input, .student-form select, .student-form textarea {
            border: 1px solid #d1d5db !important; /* Tailwind gray-300 */
        }

        /* Dark mode overrides for high-contrast accessibility */
        .dark input[type="text"]::placeholder, .dark textarea::placeholder, .dark input[type="email"]::placeholder {
            color: #9ca3af !important; /* Tailwind gray-400 */
        }
        .dark .student-form label span {
            color: #e5e7eb !important; /* Tailwind gray-200 */
        }
        .dark .student-form input, .dark .student-form select, .dark .student-form textarea {
            border: 1px solid #4b5563 !important;
        }
    </style>
    <section class="flex min-h-screen items-center justify-center px-4 py-12 student-login-body">
        <div class="w-full max-w-3xl rounded-2xl border border-gray-200 bg-white p-8 shadow-lg dark:border-gray-700 dark:bg-gray-800 sm:p-10">
            
            {{-- Header banner --}}
            <div class="mb-8 flex items-center justify-between border-b border-gray-100 pb-6 dark:border-gray-700 flex-wrap gap-4">
                <div class="flex items-center gap-4">
                    <img src="{{ asset('images/AMIS_Logo.png') }}" class="h-12 w-12 object-contain" alt="AMIS Logo">
                    <div>
                        <h1 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">AMIS Support Portal</h1>
                        <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Submit a Support Request</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3 sm:gap-4">
                    <a href="{{ route('support.index') }}" class="inline-flex items-center gap-1.5 text-xs font-extrabold text-gray-600 hover:text-emerald-600 dark:text-gray-300 dark:hover:text-emerald-400 border border-gray-200 dark:border-gray-600 bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-750 px-3.5 py-2 rounded-xl transition duration-150 shadow-xs">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <line x1="19" y1="12" x2="5" y2="12"></line>
                            <polyline points="12 19 5 12 12 5"></polyline>
                        </svg>
                        Back
                    </a>
                </div>
            </div>

            {{-- Important Notice Banner --}}
            <div class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-100 dark:bg-amber-950/15 dark:border-amber-900/30 flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <p class="text-sm font-semibold text-amber-800 dark:text-amber-300 leading-relaxed">
                    <strong>Important Notice:</strong> Before submitting a ticket, please check the <a href="{{ route('support.index') }}#faq" class="text-emerald-600 dark:text-emerald-400 hover:underline">FAQ section</a>. Most common concerns such as password resets and account access can be resolved immediately.
                </p>
            </div>

            {{-- Form or Success Content --}}
            @if (session('success'))
                <div class="text-center py-4">
                    <div class="mx-auto flex items-center justify-center w-16 h-16 rounded-full bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 mb-6 shadow-sm border border-emerald-100 dark:border-emerald-900/30">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    
                    <h2 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight flex items-center justify-center gap-2 flex-wrap">
                        <span>✅</span> Support Request Submitted Successfully
                    </h2>
                    
                    <p class="mt-4 text-sm sm:text-base font-bold text-gray-700 dark:text-gray-255">
                        Thank you for contacting the AMIS Support Center.
                    </p>
                    <p class="mt-1.5 text-sm font-semibold text-gray-500 dark:text-gray-400 leading-relaxed">
                        Your support request has been received and is now being reviewed by our team.
                    </p>

                    <div class="mt-8 p-6 rounded-2xl bg-gray-50 border border-gray-200 dark:bg-gray-800/40 dark:border-gray-700 max-w-lg mx-auto text-left shadow-xs">
                        <div class="mb-5">
                            <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider block">Reference Number:</span>
                            <span class="text-base sm:text-lg font-black text-emerald-700 dark:text-emerald-400 font-mono select-all bg-emerald-50 dark:bg-emerald-950/20 px-3.5 py-1.5 rounded-lg border border-emerald-100 dark:border-emerald-900/30 inline-block mt-2">
                                {{ session('reference_number') }}
                            </span>
                        </div>

                        <div class="mb-5 border-t border-gray-150 dark:border-gray-700/80 pt-4">
                            <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider block flex items-center gap-1">
                                <span>⚡</span> Average Response Time:
                            </span>
                            <span class="text-sm font-extrabold text-gray-900 dark:text-white block mt-1.5">
                                Within 30 Minutes During Office Hours
                            </span>
                        </div>

                        <div class="mb-5 border-t border-gray-150 dark:border-gray-700/80 pt-4">
                            <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider block">Office Hours:</span>
                            <span class="text-sm font-bold text-gray-800 dark:text-gray-250 block mt-1.5 leading-relaxed">
                                Monday–Friday<br>
                                <span class="text-emerald-700 dark:text-emerald-400">7:30 AM – 5:00 PM</span>
                            </span>
                        </div>

                        <div class="border-t border-gray-150 dark:border-gray-700/80 pt-4">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 leading-relaxed">
                                You will receive updates through the email address you provided.
                            </p>
                        </div>
                    </div>

                    <div class="mt-8 text-sm text-gray-600 dark:text-gray-300 leading-relaxed border-t border-gray-100 dark:border-gray-700 pt-6 max-w-lg mx-auto">
                        <p class="font-extrabold text-gray-900 dark:text-white mb-3">For urgent concerns, you may also contact:</p>
                        <div class="flex flex-col items-center gap-2">
                            <a href="mailto:inquiries@amis.edu.ph" class="inline-flex items-center gap-2 text-emerald-600 hover:text-emerald-750 dark:text-emerald-400 font-bold hover:underline">
                                <span>📧</span> inquiries@amis.edu.ph
                            </a>
                            <span class="inline-flex items-center gap-2 text-gray-800 dark:text-gray-200 font-bold">
                                <span>📱</span> AMIS – Information Technology
                            </span>
                        </div>
                    </div>

                    <div class="mt-8 text-center">
                        <p class="text-sm font-extrabold text-emerald-700 dark:text-emerald-400 italic">
                            Jazakumullahu Khayran.
                        </p>
                        <p class="text-xs font-bold text-gray-400 dark:text-gray-550 mt-1 uppercase tracking-wider">
                            AMIS Support Center
                        </p>
                    </div>

                    <div class="mt-8 border-t border-gray-100 dark:border-gray-700 pt-6">
                        <a href="{{ route('support.index') }}" class="student-primary-btn justify-center inline-flex px-10 py-2.5">
                            Back to Home
                        </a>
                    </div>
                </div>
            @else
                @if ($errors->any())
                    <div class="student-error mb-6">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="flex-shrink-0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Form --}}
                <form method="POST" action="{{ route('support.store') }}" enctype="multipart/form-data" class="student-form gap-6">
                    @csrf

                    {{-- SECTION 1: Your Information --}}
                    <div>
                        <h2 class="mb-4 text-sm font-extrabold text-emerald-700 border-b border-gray-200 pb-2 dark:border-gray-700 dark:text-emerald-400">Your Information</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label>
                                <span>Full Name <span class="text-red-500">*</span></span>
                                <input type="text" name="full_name" value="{{ old('full_name') }}" placeholder="Enter your full name" required>
                            </label>
                            <label>
                                <span>Email Address <span class="text-red-500">*</span></span>
                                <input type="email" name="email" value="{{ old('email') }}" placeholder="Enter your email address" required>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 font-normal normal-case flex items-start gap-1.5 leading-normal">
                                    <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Note: After you fill up and submit the form, we will send a confirmation message with your ticket details to this email.</span>
                                </p>
                            </label>
                            <label>
                                <span>Contact Number</span>
                                <input type="text" name="contact_number" value="{{ old('contact_number') }}" placeholder="Enter your contact number (optional)">
                            </label>
                            <label>
                                <span>Facebook URL or WhatsApp Number</span>
                                <input type="text" name="fb_or_whatsapp" value="{{ old('fb_or_whatsapp') }}" placeholder="Enter FB profile link or WhatsApp number (optional)">
                            </label>
                        </div>
                    </div>

                    {{-- SECTION 2: Student Information --}}
                    <div class="mt-2">
                        <h2 class="mb-4 text-sm font-extrabold text-emerald-700 border-b border-gray-200 pb-2 dark:border-gray-700 dark:text-emerald-400">Student Information (If applicable)</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <label class="md:col-span-2">
                                <span>Student Full Name</span>
                                <input type="text" name="student_full_name" value="{{ old('student_full_name') }}" placeholder="Enter student's full name">
                            </label>
                            <label>
                                <span>Grade Level</span>
                                <select name="grade_level">
                                    <option value="">Select Grade Level</option>
                                    @foreach($gradeLevels as $level)
                                        <option value="{{ $level }}" {{ old('grade_level') === $level ? 'selected' : '' }}>{{ $level }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="md:col-span-3">
                                <span>AMIS ID (Optional)</span>
                                <input type="text" name="amis_id" value="{{ old('amis_id') }}" placeholder="Enter student's AMIS ID e.g. 260000">
                            </label>
                        </div>
                    </div>

                    {{-- SECTION 3: Concern Details --}}
                    <div class="mt-2">
                        <h2 class="mb-4 text-sm font-extrabold text-emerald-700 border-b border-gray-200 pb-2 dark:border-gray-700 dark:text-emerald-400">Concern Details</h2>
                        <div class="grid grid-cols-1 gap-4">
                            <label>
                                <span>Concern Type <span class="text-red-500">*</span></span>
                                <select name="concern_type" required>
                                    <option value="">Select Concern Type</option>
                                    @foreach($concernTypes as $type)
                                        <option value="{{ $type }}" {{ old('concern_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>
                                <span>Subject <span class="text-red-500">*</span></span>
                                <input type="text" name="subject" value="{{ old('subject') }}" placeholder="Brief summary of your concern" required>
                            </label>
                            <label>
                                <span>Description of Concern <span class="text-red-500">*</span></span>
                                <textarea name="description" placeholder="Please describe your concern in detail so we can assist you better..." required>{{ old('description') }}</textarea>
                            </label>
                            <label>
                                <span>Upload Screenshot (Optional)</span>
                                <div class="mt-1 flex items-center justify-center rounded-lg border-2 border-dashed border-gray-300 px-6 py-5 dark:border-gray-600">
                                    <div class="space-y-1 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex text-sm text-gray-600 dark:text-gray-400">
                                            <label for="file-upload" class="relative cursor-pointer rounded-md bg-white font-medium text-emerald-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-emerald-500 focus-within:ring-offset-2 hover:text-emerald-500 dark:bg-gray-800">
                                                <span>Upload a file</span>
                                                <input id="file-upload" name="screenshot" type="file" class="sr-only" accept="image/*">
                                            </label>
                                            <p class="pl-1">or drag and drop</p>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">PNG, JPG, JPEG up to 5MB</p>
                                        <div id="file-chosen" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 mt-2"></div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Action buttons --}}
                    <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-3 border-t border-gray-100 pt-6 dark:border-gray-700">
                        <a href="{{ route('support.index') }}" class="student-outline-btn sm:px-8">Cancel</a>
                        <button type="submit" class="student-primary-btn sm:px-8">Submit Request</button>
                    </div>
                </form>
            @endif
        </div>
    </section>

    <script>
        document.getElementById('file-upload').addEventListener('change', function(){
            const fileName = this.files[0] ? this.files[0].name : '';
            const sizeInMB = this.files[0] ? (this.files[0].size / (1024 * 1024)).toFixed(2) : 0;
            const displayDiv = document.getElementById('file-chosen');
            if (fileName) {
                displayDiv.textContent = `Selected file: ${fileName} (${sizeInMB} MB)`;
            } else {
                displayDiv.textContent = '';
            }
        });

        // Auto uppercase inputs dynamically as they type (except email and fb_or_whatsapp URL)
        document.querySelectorAll('input[type="text"]:not([name="fb_or_whatsapp"]), textarea').forEach(input => {
            input.addEventListener('input', function() {
                const start = this.selectionStart;
                const end = this.selectionEnd;
                this.value = this.value.toUpperCase();
                this.setSelectionRange(start, end);
            });
        });
    </script>

</x-guest-layout>
