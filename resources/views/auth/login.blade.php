<x-guest-layout title="Admin Login">
    <div class="min-h-screen bg-slate-950 lg:grid lg:grid-cols-[1.05fr_.95fr]">
        <section class="relative hidden overflow-hidden bg-emerald-950 p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="absolute -right-32 -top-32 h-96 w-96 rounded-full bg-emerald-400/10"></div>
            <div class="absolute -bottom-40 -left-24 h-[30rem] w-[30rem] rounded-full bg-teal-300/10"></div>
            <div class="relative flex items-center gap-4">
                <img src="{{ asset('images/AMIS_Logo.svg') }}" class="h-14 w-14 rounded-2xl bg-white p-2" alt="AMIS Logo">
                <div><p class="text-xs font-bold uppercase tracking-[.2em] text-emerald-300">AMIS Secure Access</p><h1 class="mt-1 text-xl font-extrabold">Admin Portal</h1></div>
            </div>
            <div class="relative max-w-xl">
                <span class="inline-flex rounded-full border border-emerald-300/20 bg-emerald-300/10 px-3 py-1 text-xs font-bold uppercase tracking-wider text-emerald-200">Protected workspace</span>
                <h2 class="mt-5 text-4xl font-black leading-tight xl:text-5xl">School operations.<br>One secure workspace.</h2>
                <p class="mt-5 max-w-lg text-base leading-7 text-emerald-100/70">Access enrollment, student records, academics, Finance verification, family accounts, receipts, and reports.</p>
            </div>
            <div class="relative grid grid-cols-3 gap-3 text-xs">
                <div class="rounded-xl border border-white/10 bg-white/5 p-4"><strong class="block text-sm text-white">Email OTP</strong><span class="mt-1 block text-emerald-100/60">One-time 4-digit code</span></div>
                <div class="rounded-xl border border-white/10 bg-white/5 p-4"><strong class="block text-sm text-white">Admin only</strong><span class="mt-1 block text-emerald-100/60">Role verified before send</span></div>
                <div class="rounded-xl border border-white/10 bg-white/5 p-4"><strong class="block text-sm text-white">Audited</strong><span class="mt-1 block text-emerald-100/60">Security events recorded</span></div>
            </div>
        </section>

        <section class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-8 sm:px-8">
            <div class="w-full max-w-md" x-data="{
                mode: 'otp', step: 'email', email: '{{ old('email') }}', otp: ['', '', '', ''], loading: false, error: '', success: '',
                async request(path, body) {
                    const response = await fetch(path, { method: 'POST', headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content}, body: JSON.stringify(body) });
                    const data = await response.json();
                    return {ok: response.ok, data};
                },
                async sendOtp() {
                    if (!this.email || !this.email.includes('@')) { this.error = 'Enter a valid Admin email address.'; return; }
                    this.loading = true; this.error = ''; this.success = '';
                    try {
                        const result = await this.request('{{ route('admin.login.otp.send') }}', {email:this.email});
                        if (!result.ok) { this.error = result.data.message || Object.values(result.data.errors || {})[0]?.[0] || 'Could not send the code.'; return; }
                        this.step = 'code'; this.success = result.data.message; this.otp = ['', '', '', ''];
                        this.$nextTick(() => this.$refs.otp0.focus());
                    } catch (e) { this.error = 'Network error. Please try again.'; } finally { this.loading = false; }
                },
                inputOtp(event, index) {
                    this.otp[index] = event.target.value.replace(/\D/g,'').slice(-1);
                    if (this.otp[index] && index < 3) this.$refs['otp'+(index+1)].focus();
                    if (this.otp.join('').length === 4) this.verifyOtp();
                },
                keyOtp(event, index) { if (event.key === 'Backspace' && !this.otp[index] && index > 0) this.$refs['otp'+(index-1)].focus(); },
                pasteOtp(event) { const digits = event.clipboardData.getData('text').replace(/\D/g,'').slice(0,4).split(''); if (!digits.length) return; event.preventDefault(); this.otp = [digits[0]||'',digits[1]||'',digits[2]||'',digits[3]||'']; if (digits.length===4) this.verifyOtp(); },
                async verifyOtp() {
                    if (this.otp.join('').length !== 4 || this.loading) return;
                    this.loading = true; this.error = ''; this.success = '';
                    try {
                        const result = await this.request('{{ route('admin.login.otp.verify') }}', {email:this.email,code:this.otp.join('')});
                        if (!result.ok) { this.error = result.data.message || 'Invalid verification code.'; this.otp=['','','','']; this.$nextTick(()=>this.$refs.otp0.focus()); return; }
                        window.location.href = result.data.redirectUrl;
                    } catch (e) { this.error = 'Network error. Please try again.'; } finally { this.loading = false; }
                }
            }">
                <div class="mb-7 flex items-center gap-3 lg:hidden"><img src="{{ asset('images/AMIS_Logo.svg') }}" class="h-11 w-11" alt="AMIS Logo"><div><p class="text-xs font-bold uppercase tracking-wider text-emerald-700">AMIS Secure Access</p><h1 class="font-extrabold text-slate-900">Admin Portal</h1></div></div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/60 sm:p-8">
                    <div><p class="text-xs font-bold uppercase tracking-[.18em] text-emerald-700">Authorized personnel only</p><h2 class="mt-2 text-2xl font-black text-slate-900">Sign in to AMIS Admin</h2><p class="mt-2 text-sm leading-6 text-slate-500">Use a one-time email code or your Admin password.</p></div>

                    <div class="mt-6 grid grid-cols-2 rounded-xl bg-slate-100 p-1 text-sm font-bold">
                        <button type="button" @click="mode='otp';step='email';error='';success=''" :class="mode==='otp' ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-500'" class="rounded-lg px-3 py-2.5">Email code</button>
                        <button type="button" @click="mode='password';error='';success=''" :class="mode==='password' ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-500'" class="rounded-lg px-3 py-2.5">Password</button>
                    </div>

                    @if ($errors->any())<div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm font-semibold text-rose-800">{{ $errors->first() }}</div>@endif
                    <div x-show="error" x-cloak x-text="error" class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm font-semibold text-rose-800"></div>
                    <div x-show="success" x-cloak x-text="success" class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm font-semibold text-emerald-800"></div>

                    <div x-show="mode==='otp'" x-cloak class="mt-5">
                        <div x-show="step==='email'">
                            <label for="otp-email" class="text-sm font-bold text-slate-700">Admin email address</label>
                            <input id="otp-email" x-model.trim="email" @keydown.enter.prevent="sendOtp()" type="email" autocomplete="email" placeholder="name@school.edu.ph" class="mt-2 w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <button type="button" @click="sendOtp()" :disabled="loading" class="mt-4 w-full rounded-xl bg-emerald-700 px-5 py-3.5 text-sm font-extrabold text-white hover:bg-emerald-800 disabled:opacity-50"><span x-show="!loading">Send verification code</span><span x-show="loading">Sending securely…</span></button>
                        </div>
                        <div x-show="step==='code'" x-cloak>
                            <p class="text-sm text-slate-600">Enter the 4-digit code sent to <strong class="break-all text-slate-900" x-text="email"></strong>.</p>
                            <div class="mt-5 grid grid-cols-4 gap-3" @paste="pasteOtp($event)">@for($index=0;$index<4;$index++)<input type="text" inputmode="numeric" maxlength="1" x-model="otp[{{ $index }}]" x-ref="otp{{ $index }}" @input="inputOtp($event,{{ $index }})" @keydown="keyOtp($event,{{ $index }})" class="h-14 rounded-xl border-slate-300 text-center text-2xl font-black text-slate-900 focus:border-emerald-600 focus:ring-emerald-600">@endfor</div>
                            <button type="button" @click="verifyOtp()" :disabled="loading || otp.join('').length!==4" class="mt-5 w-full rounded-xl bg-emerald-700 px-5 py-3.5 text-sm font-extrabold text-white disabled:opacity-50"><span x-show="!loading">Verify and sign in</span><span x-show="loading">Verifying…</span></button>
                            <div class="mt-4 flex items-center justify-between text-xs font-bold"><button type="button" @click="step='email';otp=['','','',''];error='';success=''" class="text-slate-500">← Change email</button><button type="button" @click="sendOtp()" :disabled="loading" class="text-emerald-700">Resend code</button></div>
                        </div>
                    </div>

                    <form x-show="mode==='password'" x-cloak method="POST" action="{{ route('admin.login.store') }}" class="mt-5 space-y-4">
                        @csrf
                        <label class="block text-sm font-bold text-slate-700">Email<input name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="mt-2 w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm focus:border-emerald-600 focus:ring-emerald-600"></label>
                        <label class="block text-sm font-bold text-slate-700">Password<input name="password" type="password" required autocomplete="current-password" class="mt-2 w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm focus:border-emerald-600 focus:ring-emerald-600"></label>
                        <button class="w-full rounded-xl bg-slate-900 px-5 py-3.5 text-sm font-extrabold text-white hover:bg-slate-800">Sign in with password</button>
                    </form>

                    <div class="my-5 flex items-center gap-3"><div class="h-px flex-1 bg-slate-200"></div><span class="text-[11px] font-bold uppercase text-slate-400">or</span><div class="h-px flex-1 bg-slate-200"></div></div>
                    <a href="{{ route('admin.microsoft.redirect') }}" class="flex w-full items-center justify-center gap-2.5 rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50"><svg class="h-4 w-4" viewBox="0 0 23 23"><rect width="11" height="11" fill="#F25022"/><rect x="12" width="11" height="11" fill="#7FBA00"/><rect y="12" width="11" height="11" fill="#00A4EF"/><rect x="12" y="12" width="11" height="11" fill="#FFB900"/></svg>Sign in with Microsoft</a>
                </div>
                <p class="mt-5 text-center text-xs text-slate-400">OTP requests and sign-in attempts are rate-limited and audited.</p>
            </div>
        </section>
    </div>
</x-guest-layout>
