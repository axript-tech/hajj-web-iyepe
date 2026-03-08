<!-- includes/footer.php -->
    </main> <!-- End of main container -->

    <footer class="bg-gray-900 text-gray-400 py-10 mt-auto border-t-4 border-hajjGold print:hidden">
        <div class="container mx-auto px-6 grid md:grid-cols-3 gap-8 text-sm">
            
            <!-- Brand -->
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 bg-hajjGold rounded-full flex items-center justify-center font-bold text-white">A</div>
                    <span class="font-bold text-white text-lg">Abdullateef</span>
                </div>
                <p class="opacity-80 leading-relaxed">
                    Facilitating spiritual journeys with comfort, safety, and transparency. Your trusted partner for Hajj and Umrah services in Nigeria.
                </p>
            </div>

            <!-- Links -->
            <div>
                <h4 class="font-bold text-white mb-4 uppercase tracking-wide text-xs">Quick Access</h4>
                <ul class="space-y-2">
                    <li><a href="#" class="hover:text-hajjGold transition">Packages</a></li>
                    <li><a href="#" class="hover:text-hajjGold transition">Hajj Guides (Mutawwif)</a></li>
                    <li><a href="#" class="hover:text-hajjGold transition">Privacy Policy</a></li>
                    <li><a href="../admin/dashboard.php" class="hover:text-deepGreen transition font-bold">Admin Portal</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div>
                <h4 class="font-bold text-white mb-4 uppercase tracking-wide text-xs">Contact Support</h4>
                <p class="mb-2"><i class="fas fa-map-marker-alt mr-2 text-deepGreen"></i> Lagos, Nigeria</p>
                <p class="mb-2"><a href="tel:<?php echo defined('COMPANY_PHONE') ? COMPANY_PHONE : '+234 800 IYEPE HAJJ'; ?>" class="hover:text-white"><i class="fas fa-phone mr-2 text-deepGreen"></i> <?php echo defined('COMPANY_PHONE') ? COMPANY_PHONE : '+234 800 IYEPE HAJJ'; ?></a></p>
                <p><a href="mailto:<?php echo defined('COMPANY_EMAIL') ? COMPANY_EMAIL : 'salam@abdullateef.ng'; ?>" class="hover:text-white"><i class="fas fa-envelope mr-2 text-deepGreen"></i> <?php echo defined('COMPANY_EMAIL') ? COMPANY_EMAIL : 'salam@abdullateef.ng'; ?></a></p>
            </div>
        </div>
        
        <div class="container mx-auto px-6 mt-10 pt-6 border-t border-gray-800 flex flex-col md:flex-row justify-between items-center text-xs">
            <p>&copy; <?php echo date('Y'); ?> Abdullateef Integrated Hajj & Umrah Ltd. All rights reserved.</p>
            <p class="mt-2 md:mt-0 text-gray-600">System Version: <span class="font-mono"><?php echo defined('APP_VERSION') ? APP_VERSION : '1.0'; ?></span></p>
        </div>
    </footer>

    <!-- Inject Global App UI Script -->
    <?php $js_path = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../assets/js/main.js' : 'assets/js/main.js'; ?>
    <script src="<?php echo $js_path; ?>"></script>
</body>
</html>