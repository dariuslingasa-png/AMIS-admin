        <div class="bg-white p-8 border border-slate-300 shadow-md rounded-2xl print:border-0 print:shadow-none print:p-0 print-container font-sans text-slate-800 leading-normal">
            
            <!-- 1. School Header (Perfect proportional balance) -->
            <div class="border-t border-black pt-2">
                    <div class="py-3" style="display: flex !important; align-items: center !important; justify-content: space-between !important; min-height: 96px !important;">
                        <!-- School name (left) exactly 24px -->
                        <div class="font-black uppercase text-black tracking-wider leading-none" style="font-family: Arial, sans-serif; font-weight: 900; padding-left: 14px; font-size: 24px !important;">
                            AL MUNAWWARA ISLAMIC SCHOOL
                        </div>
                        <!-- Logo (center) -->
                        <div class="flex-shrink-0">
                            <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo" style="height: 80px !important; width: auto !important;">
                        </div>
                        <!-- Arabic school name (right) exactly 24px -->
                        <div class="font-black text-right tracking-wider leading-none" style="font-family: 'Times New Roman', serif; font-weight: 900; direction: rtl; padding-right: 14px; font-size: 24px !important; color: #68ac5b !important;">
                            المدرسة المنورة الإسلامية
                        </div>
                    </div>
                </div>
                <div class="border-b border-black mt-2"></div>

                <!-- 2. Document Title Bar -->
                <div class="py-5 text-center leading-tight">
                    <h2 class="font-black uppercase text-black" style="font-family: Arial, sans-serif; font-size: 28px !important; letter-spacing: 0.05em;">SCHEDULE OF FEES & DISCOUNTS</h2>
                    <h3 class="font-bold text-black mt-1" style="font-size: 20px !important;">S.Y. {{ config('services.school.year') }}</h3>
                </div>

                <!-- 3. Schedule Table Matrix -->
                <div>
                    <table class="w-full text-left" style="font-size: 19.5px !important;">
                        <thead>
                            <tr class="bg-sage-medium text-black font-extrabold uppercase border-b border-black text-center" style="font-size: 19.5px !important; border-bottom: 2px solid var(--table-border) !important;">
                                <th class="px-4 py-3 text-left">Level</th>
                                <th class="px-4 py-3">Tuition</th>
                                <th class="px-4 py-3">Miscellaneous</th>
                                <th class="px-4 py-3">E-books & Other Fees</th>
                                <th class="px-4 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="font-semibold text-black">
                            
                            <!-- ================= KINDERGARTEN SECTION ================= -->
                            <tr>
                                <td colspan="5" class="px-4 py-2 bg-yellow-break text-center uppercase font-black" style="font-size: 19.5px !important;">
                                    KINDERGARTEN
                                </td>
                            </tr>
                            <tr class="bg-sage-light">
                                <td class="px-4 py-3 font-bold">Kindergarten 1</td>
                                <td class="px-4 py-3 text-center">28,500</td>
                                <td class="px-4 py-3 text-center">1,900</td>
                                <td class="px-4 py-3 text-center">5,400</td>
                                <td class="px-4 py-3 text-right font-black">35,800</td>
                            </tr>
                            <tr class="bg-sage-light">
                                <td class="px-4 py-3 font-bold">Kindergarten 2</td>
                                <td class="px-4 py-3 text-center">31,800</td>
                                <td class="px-4 py-3 text-center">1,900</td>
                                <td class="px-4 py-3 text-center">5,400</td>
                                <td class="px-4 py-3 text-right font-black">39,100</td>
                            </tr>

                            <!-- ================= ELEMENTARY SECTION ================= -->
                            <tr>
                                <td colspan="5" class="px-4 py-2 bg-yellow-break text-center uppercase font-black" style="font-size: 19.5px !important;">
                                    ELEMENTARY/GRADE SCHOOL
                                </td>
                            </tr>
                            @php
                                $elementary = [
                                    ['Grade 1', '35,800', '1,900', '5,900', '43,600'],
                                    ['Grade 2', '36,500', '1,900', '5,900', '44,300'],
                                    ['Grade 3', '37,100', '1,900', '5,900', '44,900'],
                                    ['Grade 4', '38,100', '1,900', '5,900', '44,190'], /* Matches template photo exactly */
                                    ['Grade 5', '38,700', '1,900', '5,900', '46,500'],
                                    ['Grade 6', '39,700', '1,900', '5,900', '47,500'],
                                ];
                            @endphp
                            @foreach($elementary as $elem)
                                <tr class="bg-sage-light">
                                    <td class="px-4 py-3 font-bold">{{ $elem[0] }}</td>
                                    <td class="px-4 py-3 text-center">{{ $elem[1] }}</td>
                                    <td class="px-4 py-3 text-center">{{ $elem[2] }}</td>
                                    <td class="px-4 py-3 text-center">{{ $elem[3] }}</td>
                                    <td class="px-4 py-3 text-right font-black">{{ $elem[4] }}</td>
                                </tr>
                            @endforeach

                            <!-- ================= JUNIOR HIGH SECTION ================= -->
                            <tr>
                                <td colspan="5" class="px-4 py-2 bg-yellow-break text-center uppercase font-black" style="font-size: 19.5px !important;">
                                    JUNIOR HIGH SCHOOL
                                </td>
                            </tr>
                            @php
                                $jhs = [
                                    ['Grade 7', '40,700', '1,900', '6,200', '48,800'],
                                    ['Grade 8', '41,100', '1,900', '6,200', '49,200'],
                                    ['Grade 9', '41,800', '1,900', '6,200', '49,900'],
                                    ['Grade 10', '42,400', '1,900', '6,200', '50,500'],
                                ];
                            @endphp
                            @foreach($jhs as $j)
                                <tr class="bg-sage-light">
                                    <td class="px-4 py-3 font-bold">{{ $j[0] }}</td>
                                    <td class="px-4 py-3 text-center">{{ $j[1] }}</td>
                                    <td class="px-4 py-3 text-center">{{ $j[2] }}</td>
                                    <td class="px-4 py-3 text-center">{{ $j[3] }}</td>
                                    <td class="px-4 py-3 text-right font-black">{{ $j[4] }}</td>
                                </tr>
                            @endforeach

                            <!-- ================= SENIOR HIGH SECTION ================= -->
                            <tr>
                                <td colspan="5" class="px-4 py-2 bg-yellow-break text-center uppercase font-black" style="font-size: 19.5px !important;">
                                    SENIOR HIGH SCHOOL
                                </td>
                            </tr>
                            @php
                                $shs = [
                                    ['Grade 11', '44,200', '1,900', '8,200', '54,300'],
                                    ['Grade 12', '45,200', '1,900', '8,200', '55,300'],
                                ];
                            @endphp
                            @foreach($shs as $s)
                                <tr class="bg-sage-light">
                                    <td class="px-4 py-3 font-bold">{{ $s[0] }}</td>
                                    <td class="px-4 py-3 text-center">{{ $s[1] }}</td>
                                    <td class="px-4 py-3 text-center">{{ $s[2] }}</td>
                                    <td class="px-4 py-3 text-center">{{ $s[3] }}</td>
                                    <td class="px-4 py-3 text-right font-black">{{ $s[4] }}</td>
                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>

                <!-- 4. Official Discount Scheme (Flyer-inspired with bulletproof CSS styling) -->
                <div class="mt-8 border-t-2 border-slate-300 pt-6">
                    <h2 class="discount-section-title">OFFICIAL DISCOUNT SCHEMES</h2>
                    
                    <div class="discount-grid">
                        <!-- Early Enrollment Discount -->
                        <div class="discount-card">
                            <div>
                                <div class="discount-card-header">
                                    Early Enrollment
                                </div>
                                <div class="space-y-1">
                                    <div class="discount-row">
                                        <span><span class="discount-check">✓</span> December</span>
                                        <span class="discount-value">15%</span>
                                    </div>
                                    <div class="discount-row">
                                        <span><span class="discount-check">✓</span> January</span>
                                        <span class="discount-value">10%</span>
                                    </div>
                                    <div class="discount-row">
                                        <span><span class="discount-check">✓</span> February</span>
                                        <span class="discount-value">5%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sibling Discount -->
                        <div class="discount-card">
                            <div>
                                <div class="discount-card-header">
                                    Sibling Discount
                                </div>
                                <div class="space-y-1">
                                    <div class="discount-row">
                                        <span><span class="discount-check">✓</span> 2<sup>nd</sup> Child</span>
                                        <span class="discount-value">10%</span>
                                    </div>
                                    <div class="discount-row">
                                        <span><span class="discount-check">✓</span> 3<sup>rd</sup> Child</span>
                                        <span class="discount-value">15%</span>
                                    </div>
                                    <div class="discount-row">
                                        <span><span class="discount-check">✓</span> 4<sup>th</sup> Child</span>
                                        <span class="discount-value">20%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Full Payment Discount -->
                        <div class="discount-card">
                            <div>
                                <div class="discount-card-header">
                                    Full Payment
                                </div>
                                <div class="text-center py-8">
                                    <div style="color: #065f46 !important; font-size: 64px !important; font-weight: 900 !important; font-family: Arial, sans-serif; letter-spacing: -0.02em; line-height: 1 !important;">
                                        5%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Separator & Shukran -->
                <div class="mt-12 border-t border-slate-350 pt-4 text-center">
                    <div class="w-11/12 mx-auto h-4 bg-[#FFFF00] mb-4"></div>
                    <p class="text-xs font-bold tracking-wider text-black uppercase">Shukran. JazakAllahu khayran</p>
                </div>

        </div>
