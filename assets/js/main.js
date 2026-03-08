/**
 * assets/js/main.js
 * Main Frontend Logic for Abdullateef Hajj Portal
 */

document.addEventListener('DOMContentLoaded', () => {
    // Auto-Dismiss Static Backend Alerts
    const alerts = document.querySelectorAll('.bg-green-100, .bg-red-100, .bg-yellow-100');
    if (alerts.length > 0) {
        setTimeout(() => {
            alerts.forEach(el => {
                el.style.transition = "opacity 0.5s ease";
                el.style.opacity = '0';
                setTimeout(() => el.style.display = 'none', 500);
            });
        }, 5000);
    }
});

/**
 * Global App UI System
 * Replaces native alert(), confirm(), and prompt()
 */
window.AppUI = {
    toast: function(message, type = 'info') {
        const colors = {
            success: 'bg-green-500 border-green-600',
            error: 'bg-red-500 border-red-600',
            info: 'bg-[#1B7D75] border-teal-800',
            warning: 'bg-[#C8AA00] border-yellow-600'
        };
        const icon = type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-exclamation-circle' : (type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle'));
        
        const toastId = 'toast-' + Date.now();
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `fixed top-24 right-5 ${colors[type] || colors.info} text-white px-6 py-4 rounded-xl shadow-2xl z-[9999] transform translate-x-full transition-transform duration-300 flex items-center gap-3 border-l-4`;
        toast.innerHTML = `<i class="fas ${icon} text-lg"></i> <span class="font-bold text-sm leading-tight">${message}</span>`;
        
        document.body.appendChild(toast);
        
        setTimeout(() => toast.classList.remove('translate-x-full'), 50);
        
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    },

    confirm: function(message, onConfirm) {
        this.createModal(message, 'confirm', onConfirm);
    },

    prompt: function(message, onConfirm) {
        this.createModal(message, 'prompt', onConfirm);
    },

    alert: function(message, type = 'error') {
        this.createModal(message, 'alert', null, type);
    },

    createModal: function(message, mode, onConfirm, type = 'info') {
        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4 opacity-0 transition-opacity duration-300 font-sans';
        
        const modal = document.createElement('div');
        modal.className = 'bg-white rounded-3xl shadow-2xl max-w-sm w-full p-6 transform scale-95 transition-transform duration-300';
        
        let iconHtml = '<div class="w-14 h-14 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center text-2xl mb-4 mx-auto shadow-inner border border-blue-100"><i class="fas fa-question"></i></div>';
        if (mode === 'alert') {
            if (type === 'error') iconHtml = '<div class="w-14 h-14 rounded-full bg-red-50 text-red-500 flex items-center justify-center text-2xl mb-4 mx-auto shadow-inner border border-red-100"><i class="fas fa-exclamation-triangle"></i></div>';
            if (type === 'success') iconHtml = '<div class="w-14 h-14 rounded-full bg-green-50 text-green-500 flex items-center justify-center text-2xl mb-4 mx-auto shadow-inner border border-green-100"><i class="fas fa-check"></i></div>';
            if (type === 'info') iconHtml = '<div class="w-14 h-14 rounded-full bg-teal-50 text-[#1B7D75] flex items-center justify-center text-2xl mb-4 mx-auto shadow-inner border border-teal-100"><i class="fas fa-info-circle"></i></div>';
        }

        let inputHtml = '';
        if (mode === 'prompt') {
            inputHtml = `<input type="text" id="appui-prompt-input" class="w-full mt-5 p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#1B7D75] focus:border-[#1B7D75] outline-none transition font-bold text-gray-800 text-center" placeholder="Type here..." autocomplete="off">`;
        }

        let buttonsHtml = '';
        if (mode === 'alert') {
            buttonsHtml = `<button class="w-full bg-gray-800 text-white font-bold py-3 rounded-xl mt-6 hover:bg-black transition shadow-md" id="appui-ok">Acknowledge</button>`;
        } else {
            buttonsHtml = `
                <div class="flex gap-3 mt-6">
                    <button class="flex-1 bg-gray-100 text-gray-600 font-bold py-3 rounded-xl hover:bg-gray-200 transition border border-gray-200" id="appui-cancel">Cancel</button>
                    <button class="flex-1 bg-[#1B7D75] text-white font-bold py-3 rounded-xl hover:bg-teal-800 transition shadow-md" id="appui-confirm">Proceed</button>
                </div>
            `;
        }

        modal.innerHTML = `
            <div class="text-center">
                ${iconHtml}
                <h3 class="text-lg font-bold text-gray-800 leading-snug">${message}</h3>
                ${inputHtml}
            </div>
            ${buttonsHtml}
        `;
        
        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        setTimeout(() => {
            overlay.classList.remove('opacity-0');
            modal.classList.remove('scale-95');
        }, 10);

        const close = () => {
            overlay.classList.add('opacity-0');
            modal.classList.add('scale-95');
            setTimeout(() => overlay.remove(), 300);
        };

        if (mode === 'alert') {
            modal.querySelector('#appui-ok').onclick = close;
        } else {
            modal.querySelector('#appui-cancel').onclick = close;
            modal.querySelector('#appui-confirm').onclick = () => {
                let val = true;
                if (mode === 'prompt') {
                    val = modal.querySelector('#appui-prompt-input').value.trim();
                    if (!val) {
                        modal.querySelector('#appui-prompt-input').classList.add('ring-2', 'ring-red-500');
                        return; 
                    }
                }
                close();
                if (onConfirm) onConfirm(val);
            };
            
            if (mode === 'prompt') {
                const input = modal.querySelector('#appui-prompt-input');
                setTimeout(() => input.focus(), 100);
                input.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') modal.querySelector('#appui-confirm').click();
                });
            }
        }
    }
};

/**
 * Live Paystack Integration
 */
function payWithPaystack(email, amount, callbackUrl) {
    // NOTE: Replace with your LIVE / TEST Paystack Public Key
    const publicKey = 'pk_test_00b7531c09eed64cf7af3e4cc42efc753f45dd6d'; 
    
    // Dynamically load the Paystack inline script if it hasn't been loaded yet
    if (typeof PaystackPop === 'undefined') {
        AppUI.toast("Initializing secure gateway...", "info");
        const script = document.createElement('script');
        script.src = 'https://js.paystack.co/v1/inline.js';
        script.onload = () => triggerPaystackPopup(publicKey, email, amount, callbackUrl);
        document.body.appendChild(script);
    } else {
        triggerPaystackPopup(publicKey, email, amount, callbackUrl);
    }
}

function triggerPaystackPopup(publicKey, email, amount, callbackUrl) {
    const handler = PaystackPop.setup({
        key: publicKey,
        email: email,
        amount: amount * 100, // Paystack expects the amount in Kobo
        currency: 'NGN',
        ref: 'REF-' + Math.floor((Math.random() * 1000000000) + 1) + '-' + Date.now(),
        callback: function(response) {
            AppUI.toast('Payment authorized! Verifying...', 'success');
            // Safely append reference to the callback URL
            const connector = callbackUrl.includes('?') ? '&' : '?';
            window.location.href = callbackUrl + connector + 'ref=' + response.reference;
        },
        onClose: function() {
            AppUI.toast('Transaction window closed.', 'warning');
        }
    });
    handler.openIframe();
}