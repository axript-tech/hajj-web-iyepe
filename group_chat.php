<?php
// group_chat.php
require_once 'includes/auth_session.php';
require_once 'config/constants.php';

$user_id = $_SESSION['user_id'];
$batch_id = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;

if ($batch_id === 0) {
    // Fallback to active trip if no ID provided
    $sql = "SELECT trip_batch_id FROM bookings WHERE member_id = '$user_id' AND booking_status != 'completed' LIMIT 1";
    $trip = $conn->query($sql)->fetch_assoc();
    if($trip) $batch_id = $trip['trip_batch_id'];
    else { header("Location: dashboard.php"); exit(); }
}

// Verify Access & Get Trip Info
$sql = "SELECT tb.batch_name, p.name as pkg_name, tb.status as trip_status 
        FROM bookings b 
        JOIN trip_batches tb ON b.trip_batch_id = tb.id
        JOIN packages p ON b.package_id = p.id
        WHERE b.member_id = '$user_id' AND b.trip_batch_id = '$batch_id' LIMIT 1";
$trip = $conn->query($sql)->fetch_assoc();

if (!$trip) { header("Location: dashboard.php"); exit(); }
$is_archived = ($trip['trip_status'] === 'completed');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Group Chat | <?php echo $trip['batch_name']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>tailwind.config = { theme: { extend: { colors: { deepGreen: '#1B7D75', hajjGold: '#C8AA00' }, fontFamily: { sans: ['Quicksand'] } } } }</script>
    <style>
        body { background: #e5ddd5; } 
        .chat-bg { background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); opacity: 0.1; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .attach-menu { transition: all 0.2s ease-in-out; transform-origin: bottom left; }
        .attach-menu.hidden { transform: scale(0.9); opacity: 0; pointer-events: none; }
        .word-break { word-break: break-word; }
    </style>
</head>
<body class="font-sans h-screen flex flex-col relative max-w-4xl mx-auto shadow-2xl">

    <!-- Header -->
    <div class="bg-deepGreen text-white p-3 shadow-md flex items-center gap-3 z-20 relative">
        <a href="dashboard.php" class="p-2 hover:bg-white/10 rounded-full transition"><i class="fas fa-arrow-left"></i></a>
        <div class="flex-grow">
            <h1 class="font-bold text-lg leading-none flex items-center gap-2">
                <?php echo htmlspecialchars($trip['batch_name']); ?>
                <?php if($is_archived): ?><span class="text-[10px] bg-white/20 px-2 py-0.5 rounded uppercase">Archived</span><?php endif; ?>
            </h1>
            <p class="text-xs text-green-200"><?php echo htmlspecialchars($trip['pkg_name']); ?> Group</p>
        </div>
        <div class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center font-bold border border-white/20">
            <i class="fas fa-users"></i>
        </div>
    </div>

    <!-- Chat Area -->
    <div class="flex-grow relative bg-[#e5ddd5] overflow-hidden flex flex-col" onclick="closeAttachMenu()">
        <div class="absolute inset-0 chat-bg pointer-events-none"></div>
        <div id="chat-container" class="flex-grow overflow-y-auto p-4 space-y-3 z-0 no-scrollbar relative pb-20">
            <div class="text-center text-xs text-gray-500 my-4 bg-white/60 backdrop-blur-sm py-1.5 rounded-full mx-auto w-fit px-4 shadow-sm font-semibold">
                <i class="fas fa-lock mr-1"></i> Messages are monitored by Admin Support.
            </div>
        </div>
    </div>

    <?php if(!$is_archived): ?>
        <!-- Attachment Menu -->
        <div id="attach-menu" class="absolute bottom-20 left-4 bg-white rounded-2xl shadow-2xl p-4 flex gap-6 hidden attach-menu z-20 border border-gray-100">
            <button onclick="document.getElementById('doc-upload').click()" class="flex flex-col items-center gap-2 group">
                <div class="w-12 h-12 rounded-full bg-indigo-50 text-indigo-500 flex items-center justify-center text-xl group-hover:bg-indigo-500 group-hover:text-white transition shadow-sm border border-indigo-100"><i class="fas fa-file-alt"></i></div>
                <span class="text-[10px] font-bold text-gray-600 uppercase">Document</span>
            </button>
            <button onclick="document.getElementById('img-upload').click()" class="flex flex-col items-center gap-2 group">
                <div class="w-12 h-12 rounded-full bg-pink-50 text-pink-500 flex items-center justify-center text-xl group-hover:bg-pink-500 group-hover:text-white transition shadow-sm border border-pink-100"><i class="fas fa-image"></i></div>
                <span class="text-[10px] font-bold text-gray-600 uppercase">Gallery</span>
            </button>
            <button onclick="shareLocation()" class="flex flex-col items-center gap-2 group">
                <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center text-xl group-hover:bg-emerald-500 group-hover:text-white transition shadow-sm border border-emerald-100"><i class="fas fa-map-marker-alt"></i></div>
                <span class="text-[10px] font-bold text-gray-600 uppercase">Location</span>
            </button>
        </div>

        <input type="file" id="img-upload" accept="image/*" class="hidden" onchange="uploadFile(event, 'image')">
        <input type="file" id="doc-upload" accept=".pdf,.doc,.docx,.xls,.xlsx,.txt" class="hidden" onchange="uploadFile(event, 'document')">

        <!-- Input Area -->
        <div class="bg-gray-100 p-2 flex gap-2 items-center z-20 pb-6 md:pb-2" onclick="event.stopPropagation()">
            <button type="button" onclick="toggleAttachMenu(event)" class="p-3 text-gray-500 hover:text-deepGreen transition text-xl flex-shrink-0"><i class="fas fa-plus-circle"></i></button>
            <input type="text" id="msg-input" class="flex-grow bg-white rounded-full px-5 py-3.5 border border-gray-200 focus:ring-2 focus:ring-deepGreen outline-none shadow-sm text-sm" placeholder="Type a message...">
            <button type="button" onclick="sendMessage()" class="w-12 h-12 bg-deepGreen text-white rounded-full flex items-center justify-center shadow-md hover:bg-teal-800 transition flex-shrink-0">
                <i class="fas fa-paper-plane" id="send-icon"></i>
            </button>
        </div>
    <?php else: ?>
        <div class="bg-gray-200 p-4 text-center text-sm text-gray-500 font-bold z-20">
            <i class="fas fa-history mr-1"></i> This trip is completed. Chat is read-only.
        </div>
    <?php endif; ?>

    <script>
        const batchId = <?php echo $batch_id; ?>;
        const container = document.getElementById('chat-container');
        let lastMsgId = 0;
        let isUserScrolling = false;

        // Detect manual scrolling
        container.addEventListener('scroll', () => {
            isUserScrolling = container.scrollTop + container.clientHeight < container.scrollHeight - 50;
        });

        function toggleAttachMenu(e) { 
            if(e) e.stopPropagation();
            document.getElementById('attach-menu').classList.toggle('hidden'); 
        }
        function closeAttachMenu() { 
            const m = document.getElementById('attach-menu'); 
            if(m) m.classList.add('hidden'); 
        }

        document.body.addEventListener('click', () => {
            if (Notification.permission !== "granted" && Notification.permission !== "denied") Notification.requestPermission();
        }, { once: true });

        function fetchMessages() {
            const formData = new FormData();
            formData.append('action', 'fetch');
            formData.append('batch_id', batchId);
            formData.append('last_id', lastMsgId);

            fetch('chat_api.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if(data.error) return;
                if(data.messages && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        appendMessage(msg);
                        lastMsgId = msg.id;
                        if (!msg.is_me && document.hidden) sendPushNotification(msg);
                    });
                    if (!isUserScrolling) container.scrollTop = container.scrollHeight;
                }
            }).catch(e => console.log("Fetch err:", e));
        }

        function sendPushNotification(msg) {
            if (!("Notification" in window) || Notification.permission !== "granted") return;
            const notif = new Notification(msg.name, {
                body: msg.message.replace(/\[.*?\]/g, '📎 Attachment'),
                icon: 'https://abdullateefhajjumrah.com/wp-content/uploads/elementor/thumbs/727b0-abdullateef-hajj-and-umrah-logo-qtfd71hyunrre8osnz9m8vl0hf3deqv7d71ztqylmo.png',
                tag: 'chat-msg-' + batchId 
            });
            notif.onclick = function() { window.focus(); this.close(); };
        }

        function parseMessageContent(text) {
            let parsed = text;
            parsed = parsed.replace(/\[IMG\](.*?)\[\/IMG\]/g, (match, path) => `<a href="${path}" target="_blank" class="block mt-2"><img src="${path}" class="w-full max-w-[220px] rounded-lg shadow-sm border border-gray-200" loading="lazy"></a>`);
            parsed = parsed.replace(/\[FILE\](.*?)\|(.*?)\[\/FILE\]/g, (match, path, filename) => `
                <a href="${path}" download="${filename}" class="flex items-center gap-3 bg-white/80 p-3 rounded-lg mt-2 border border-gray-200 hover:bg-white transition text-gray-800 shadow-sm">
                    <div class="bg-indigo-100 text-indigo-600 p-2.5 rounded-lg"><i class="fas fa-file-download text-lg"></i></div>
                    <div class="overflow-hidden">
                        <p class="text-[11px] font-bold truncate w-32 md:w-48 leading-tight">${filename}</p>
                        <p class="text-[9px] text-gray-500 uppercase mt-0.5 font-bold">Download</p>
                    </div>
                </a>`);
            parsed = parsed.replace(/\[LOC\](.*?)\[\/LOC\]/g, (match, coords) => {
                const mapUrl = `https://www.google.com/maps/search/?api=1&query=${coords}`;
                return `
                <a href="${mapUrl}" target="_blank" class="block mt-2 rounded-lg overflow-hidden border border-gray-200 shadow-sm group bg-white">
                    <div class="p-3 flex items-center gap-3">
                        <div class="bg-emerald-100 text-emerald-600 w-10 h-10 rounded-full flex items-center justify-center"><i class="fas fa-map-marker-alt text-lg"></i></div>
                        <div><p class="text-xs font-bold text-gray-800">Shared Location</p><p class="text-[9px] text-gray-500 uppercase">Tap to open Maps</p></div>
                    </div>
                </a>`;
            });
            return parsed;
        }

        function appendMessage(msg) {
            const isMe = msg.is_me;
            const isAdmin = msg.is_admin == 1;
            const alignClass = isMe ? 'ml-auto bg-[#dcf8c6]' : 'mr-auto bg-white';
            const nameColor = isAdmin ? 'text-red-600' : (isMe ? 'text-green-800' : 'text-blue-600');
            const photoSrc = msg.photo ? msg.photo : 'https://ui-avatars.com/api/?name='+msg.name+'&background=random';
            const displayName = isMe ? 'You' : msg.name;
            const adminBadge = isAdmin ? '<i class="fas fa-shield-alt ml-1 text-[10px] bg-red-100 px-1 rounded"></i>' : '';
            const finalContent = parseMessageContent(msg.message);
            const avatarHtml = !isMe ? `<img src="${photoSrc}" class="w-8 h-8 rounded-full border border-gray-200 mt-1 flex-shrink-0 object-cover shadow-sm">` : '';

            const html = `
                <div class="flex gap-2 w-full max-w-[85%] md:max-w-[75%] ${isMe ? 'justify-end ml-auto' : ''}">
                    ${!isMe ? avatarHtml : ''}
                    <div class="${alignClass} p-2 px-3 rounded-2xl shadow-sm text-sm relative pb-5 min-w-[120px] ${isMe ? 'rounded-br-sm' : 'rounded-tl-sm'}">
                        <p class="text-[11px] font-bold ${nameColor} mb-1 ${isMe ? 'text-right' : ''}">${displayName} ${adminBadge}</p>
                        <div class="text-gray-800 leading-snug word-break">${finalContent}</div>
                        <span class="text-[9px] text-gray-400 absolute bottom-1 ${isMe ? 'left-3' : 'right-3'} font-medium">${msg.time}</span>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        function toggleLoading(isLoading) {
            const icon = document.getElementById('send-icon');
            if(icon) icon.className = isLoading ? 'fas fa-circle-notch fa-spin' : 'fas fa-paper-plane';
        }

        function uploadFile(event, type) {
            closeAttachMenu();
            const file = event.target.files[0];
            if (!file) return;
            toggleLoading(true);
            const fd = new FormData();
            fd.append('action', 'send_file');
            fd.append('batch_id', batchId);
            fd.append('attachment', file);
            fd.append('att_type', type);
            fetch('chat_api.php', { method: 'POST', body: fd }).then(r=>r.json()).then(res=>{
                toggleLoading(false);
                if(res.error) alert(res.error);
                fetchMessages();
            }).catch(()=>{ toggleLoading(false); alert('Upload failed.'); });
            event.target.value = '';
        }

        function shareLocation() {
            closeAttachMenu();
            if (!navigator.geolocation) { alert("Geolocation not supported."); return; }
            toggleLoading(true);
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const locStr = `[LOC]${pos.coords.latitude},${pos.coords.longitude}[/LOC]`;
                    const fd = new FormData();
                    fd.append('action', 'send'); fd.append('batch_id', batchId); fd.append('message', locStr);
                    fetch('chat_api.php', { method: 'POST', body: fd }).then(() => { toggleLoading(false); fetchMessages(); });
                },
                () => { toggleLoading(false); alert("Ensure location services are enabled."); }
            );
        }

        function sendMessage() {
            const input = document.getElementById('msg-input');
            const txt = input.value.trim();
            if(!txt) return;
            toggleLoading(true);
            const fd = new FormData();
            fd.append('action', 'send'); fd.append('batch_id', batchId); fd.append('message', txt);
            input.value = '';
            fetch('chat_api.php', { method: 'POST', body: fd }).then(()=>{ toggleLoading(false); fetchMessages(); });
        }

        const msgInput = document.getElementById('msg-input');
        if(msgInput) { msgInput.addEventListener('keypress', e => { if (e.key === 'Enter') sendMessage(); }); }

        setInterval(fetchMessages, 3000);
        fetchMessages();
    </script>
</body>
</html>