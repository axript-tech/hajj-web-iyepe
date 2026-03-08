<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposal: Digital Transformation Ecosystem</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        deepGreen: '#1B7D75',
                        hajjGold: '#C8AA00',
                        lightGold: '#FFF9C4',
                        offWhite: '#F9FAFB',
                    },
                    fontFamily: {
                        sans: ['Quicksand', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        @media print {
            .no-print { display: none; }
            body { background: white; }
            .shadow-2xl { box-shadow: none; }
            .container { max-width: 100%; margin: 0; padding: 0; }
        }
    </style>
</head>
<body class="bg-gray-100 py-10 font-sans text-gray-800">

    <!-- Print Button -->
    <div class="fixed bottom-8 right-8 no-print">
        <button onclick="window.print()" class="bg-deepGreen text-white px-6 py-3 rounded-full shadow-lg hover:bg-teal-800 transition font-bold flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2-4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Print Proposal
        </button>
    </div>

    <div class="max-w-4xl mx-auto bg-white shadow-2xl overflow-hidden container">
        
        <!-- COVER PAGE -->
        <div class="bg-deepGreen text-white p-12 md:p-20 relative overflow-hidden min-h-[600px] flex flex-col justify-between">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10" style="background-image: url('https://www.transparenttextures.com/patterns/arabesque.png');"></div>
            <div class="absolute -right-20 -top-20 w-96 h-96 bg-hajjGold rounded-full opacity-20 blur-3xl"></div>

            <div class="relative z-10">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-16 h-16 bg-white rounded-lg flex items-center justify-center text-deepGreen font-bold text-3xl shadow-lg">A</div>
                    <div class="h-12 w-px bg-white/30"></div>
                    <div>
                        <h2 class="text-xl font-bold uppercase tracking-widest">Abdullateef</h2>
                        <p class="text-xs text-hajjGold uppercase tracking-widest">Integrated Hajj & Umrah Ltd.</p>
                    </div>
                </div>

                <h1 class="text-5xl md:text-6xl font-bold leading-tight mb-6">
                    Digital Transformation <br>
                    <span class="text-hajjGold">Ecosystem Proposal</span>
                </h1>
                <p class="text-xl text-green-100 max-w-xl leading-relaxed">
                    A comprehensive, secured, and automated portal designed to elevate pilgrim safety, streamline operations, and ensure financial integrity.
                </p>
            </div>

            <div class="relative z-10 grid grid-cols-2 gap-8 border-t border-white/20 pt-8 mt-12">
                <div>
                    <p class="text-xs text-green-300 uppercase font-bold mb-1">Prepared For</p>
                    <p class="text-lg font-bold">The Management</p>
                    <p class="text-sm opacity-80">Abdullateef Integrated Hajj & Umrah Ltd.</p>
                </div>
                <div>
                    <p class="text-xs text-green-300 uppercase font-bold mb-1">Prepared By</p>
                    <p class="text-lg font-bold">Dr. Adam Zubair</p>
                    <p class="text-sm opacity-80">Lead Technical Consultant</p>
                </div>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="p-12 md:p-16 space-y-12">

            <!-- 1. Executive Summary -->
            <section>
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-hajjGold font-bold text-lg">01</span>
                    <h2 class="text-2xl font-bold text-deepGreen uppercase tracking-wide">Executive Summary</h2>
                </div>
                <p class="text-gray-600 leading-relaxed">
                    The pilgrimage industry demands a blend of spiritual sensitivity and rigorous operational precision. Current manual processes pose risks in medical clearance, payment tracking, and room allocation. 
                    <br><br>
                    We propose the deployment of the <strong>Abdullateef Hajj Portal</strong>—a bespoke, enterprise-grade web application. This system introduces a "Gated Logic" workflow that ensures no pilgrim is booked without a confirmed medical profile and financial commitment, effectively eliminating operational bottlenecks and financial leakage.
                </p>
            </section>

            <!-- 2. The Solution Ecosystem -->
            <section>
                <div class="flex items-center gap-3 mb-6">
                    <span class="text-hajjGold font-bold text-lg">02</span>
                    <h2 class="text-2xl font-bold text-deepGreen uppercase tracking-wide">The Solution Ecosystem</h2>
                </div>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="bg-offWhite p-6 rounded-lg border-l-4 border-deepGreen">
                        <h3 class="font-bold text-deepGreen mb-2">1. Pilgrim Self-Service</h3>
                        <ul class="text-sm text-gray-600 space-y-2 list-disc list-inside">
                            <li>Secure Account Creation with Passport Upload.</li>
                            <li><strong>Safety Gate:</strong> Mandatory Medical Profile (Genotype/Mobility) before booking.</li>
                            <li><strong>Financial Gate:</strong> Automated ₦20k Commitment Fee via Paystack.</li>
                            <li>Live Dashboard with Payment History & Digital ID.</li>
                        </ul>
                    </div>
                    <div class="bg-offWhite p-6 rounded-lg border-l-4 border-hajjGold">
                        <h3 class="font-bold text-deepGreen mb-2">2. Admin Command Center</h3>
                        <ul class="text-sm text-gray-600 space-y-2 list-disc list-inside">
                            <li><strong>Trip Lifecycle:</strong> Schedule batches (e.g., Aug 2026) and manage departure status.</li>
                            <li><strong>Medical Manifest:</strong> Live filtering of high-risk pilgrims (Wheelchair/SS Genotype).</li>
                            <li><strong>Room Manager:</strong> Drag-and-drop interface for Makkah/Madinah allocations.</li>
                            <li><strong>Global Ledger:</strong> Real-time revenue tracking across all cohorts.</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- 3. Investment Breakdown -->
            <section class="break-before-page">
                <div class="flex items-center gap-3 mb-6">
                    <span class="text-hajjGold font-bold text-lg">03</span>
                    <h2 class="text-2xl font-bold text-deepGreen uppercase tracking-wide">Investment Justification</h2>
                </div>
                
                <p class="text-gray-600 mb-6">
                    The total investment for the design, development, deployment, and security infrastructure of this custom ecosystem is <strong>₦4,000,000</strong>. This cost is broken down into five critical technical modules ensuring scalability and security.
                </p>

                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-deepGreen text-white text-sm uppercase">
                            <tr>
                                <th class="p-4">Module / Phase</th>
                                <th class="p-4">Key Deliverables & Complexity</th>
                                <th class="p-4 text-right">Valuation (₦)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <tr class="bg-gray-50">
                                <td class="p-4 font-bold text-deepGreen">1. Core Architecture & Security</td>
                                <td class="p-4 text-gray-600">
                                    Database design, Authentication system, Passport encryption, Server setup, and "Gated Logic" development.
                                </td>
                                <td class="p-4 text-right font-mono">850,000</td>
                            </tr>
                            <tr>
                                <td class="p-4 font-bold text-deepGreen">2. Pilgrim Experience Engine</td>
                                <td class="p-4 text-gray-600">
                                    User Dashboard, Medical Profiling logic, Digital ID Card generation, and Mobile-Responsive UI.
                                </td>
                                <td class="p-4 text-right font-mono">750,000</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="p-4 font-bold text-deepGreen">3. Financial Logic & Ledger</td>
                                <td class="p-4 text-gray-600">
                                    Paystack integration, Automated Receipt Generation, Installment Wallet system, and Admin Financial Reporting.
                                </td>
                                <td class="p-4 text-right font-mono">900,000</td>
                            </tr>
                            <tr>
                                <td class="p-4 font-bold text-deepGreen">4. Operations Command Center</td>
                                <td class="p-4 text-gray-600">
                                    <strong>High Complexity:</strong> Drag-and-Drop Room Manager, Trip Batching algorithm, Automated Manifests (PDF), and Communications Hub.
                                </td>
                                <td class="p-4 text-right font-mono">1,100,000</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="p-4 font-bold text-deepGreen">5. Deployment & Training</td>
                                <td class="p-4 text-gray-600">
                                    Server deployment, SSL Installation, Staff training sessions, and 3-month priority support.
                                </td>
                                <td class="p-4 text-right font-mono">400,000</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-800 text-white">
                            <tr>
                                <td colspan="2" class="p-4 text-right uppercase font-bold tracking-wider">Total Investment</td>
                                <td class="p-4 text-right font-bold text-xl text-hajjGold">₦4,000,000</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>

            <!-- 4. Timeline -->
            <section>
                <div class="flex items-center gap-3 mb-6">
                    <span class="text-hajjGold font-bold text-lg">04</span>
                    <h2 class="text-2xl font-bold text-deepGreen uppercase tracking-wide">Project Timeline</h2>
                </div>
                <div class="flex flex-col md:flex-row gap-4 justify-between text-center">
                    <div class="flex-1 bg-gray-50 p-4 rounded border border-gray-200">
                        <span class="block text-deepGreen font-bold text-lg mb-1">Weeks 1-2</span>
                        <span class="text-sm text-gray-500">Core Setup & Database</span>
                    </div>
                    <div class="flex-1 bg-gray-50 p-4 rounded border border-gray-200">
                        <span class="block text-deepGreen font-bold text-lg mb-1">Weeks 3-4</span>
                        <span class="text-sm text-gray-500">Pilgrim UI & Payments</span>
                    </div>
                    <div class="flex-1 bg-gray-50 p-4 rounded border border-gray-200">
                        <span class="block text-deepGreen font-bold text-lg mb-1">Weeks 5-6</span>
                        <span class="text-sm text-gray-500">Admin Modules & Rooming</span>
                    </div>
                    <div class="flex-1 bg-hajjGold text-white p-4 rounded shadow-lg">
                        <span class="block font-bold text-lg mb-1">Week 7</span>
                        <span class="text-sm opacity-90">Testing & Launch</span>
                    </div>
                </div>
            </section>

            <!-- Footer -->
            <div class="border-t border-gray-200 pt-8 mt-12">
                <div class="flex justify-between items-end">
                    <div>
                        <p class="font-bold text-deepGreen text-lg">Dr. Adam Zubair</p>
                        <p class="text-sm text-gray-500">Lead Technical Consultant</p>
                        <p class="text-sm text-gray-500 mt-2"><?php echo date('F d, Y'); ?></p>
                    </div>
                    <div class="text-right">
                        <p class="italic text-gray-400 text-sm mb-2">Valid for 30 days from issuance.</p>
                        <div class="h-px w-32 bg-gray-300 ml-auto mb-2"></div>
                        <p class="text-xs uppercase font-bold text-gray-400">Authorized Signature</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

</body>
</html>