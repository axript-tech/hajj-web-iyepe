<?php
// admin/trip_chat.php
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'manager'])) { header("Location: ../index.php"); exit; }

// Fetch Batches
$batches = $conn->query("SELECT id, batch_name, status FROM trip_batches ORDER BY start_date DESC");
$selected_batch = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;
?>

<?php include '../includes/header.php'; ?>

<div class="h-[calc(100vh-140px)] flex flex-col max-w-6xl mx-auto">
    
    <!-- Header -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-4 flex flex-col md:flex-row justify-between items-center gap-4 z-30">
        <div>
            <h1 class="text-2xl font-bold text-deepGreen flex items-center gap-2"><i class="fas fa-shield-alt text-hajjGold"></i> Chat Moderator</h1>
            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mt-1">Monitor & Regulate Communications</p>
        </div>
        <form method="GET" class="w-full md:w-auto">
            <select name="batch_id" onchange="this.form.submit()" class="w-full md:w-64 p-3 border border-gray-300 rounded-lg font-bold text-gray-700 focus:ring-2 focus:ring-deepGreen outline-none shadow-sm">
                <option value="0">-- Select Trip Batch --</option>
                <?php if ($batches && $batches->num_rows > 0): ?>
                    <?php while($b = $batches->fetch_assoc()): ?>
                        <option value="<?php echo $b['id']; ?>" <?php echo ($selected_batch == $b['id'])?'selected':''; ?>>
                            <?php echo htmlspecialchars($b['batch_name']); ?> <?php echo ($b['status']=='completed')?'(Archived)':''; ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </form>
    </div>

    <?php if($selected_batch > 0): ?>
        <div class="flex-grow bg-[#e5ddd5] rounded-xl shadow-inner border border-gray-300 relative overflow-hidden flex flex-col" onclick="closeAttachMenu()">
            <div class="absolute inset-0 bg-[url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png')] opacity-10 pointer-events-none"></div>
            
            <!-- Chat Feed -->
            <div id="admin-chat-feed" class="flex-grow overflow-y-auto p-6 space-y-4 z-10 pb-24"></div>

            <!-- Attachment Menu (Hidden by default) -->
            <div id="attach-menu" class="absolute bottom-24 left-6 bg-white rounded-2xl shadow-2xl p-4 flex gap-6 hidden attach-menu z-20 border border-gray-100">
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

            <!-- Hidden File Inputs -->
            <input type="file" id="img-upload" accept="image/*" class="hidden" onchange="uploadFile(event, 'image')">
            <input type="file" id="doc-upload" accept=".pdf,.doc,.docx,.xls,.xlsx,.txt" class="hidden" onchange="uploadFile(event, 'document')">

            <!-- Admin Input -->
            <div class="bg-white p-3 z-20 flex gap-2 border-t shadow-[0_-5px_15px_rgba(0,0,0,0.05)] relative" onclick="event.stopPropagation()">
                <button type="button" onclick="toggleAttachMenu(event)" class="p-4 text-gray-500 hover:text-deepGreen transition text-xl flex-shrink-0 bg-gray-50 rounded-xl border border-gray-200"><i class="fas fa-plus-circle"></i></button>
                <input type="text" id="admin-msg" class="flex-grow p-4 border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:ring-2 focus:ring-deepGreen outline-none transition" placeholder="Post official announcement to this group...">
                <button type="button" onclick="sendAdminMsg()" id="send-btn" class="bg-deepGreen text-white px-8 rounded-xl font-bold hover:bg-teal-800 transition shadow-md flex items-center gap-2 flex-shrink-0">
                    Send <i id="send-icon" class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    <?php else: ?>
        <div class="flex-grow flex items-center justify-center bg-white rounded-xl border border-gray-200 shadow-sm text-gray-400">
            <div class="text-center">
                <i class="fas fa-comments fa-3x mb-3 opacity-20 text-deepGreen"></i>
                <p class="font-bold">Select a trip batch from the dropdown to monitor messages.</p>
            </div>
        </div>
    <?php endif; ?>

</div>

<style>
    .word-break { word-break: break-word; }
    /* Hide scrollbar */
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    /* Attachment Menu Animation */
    .attach-menu { transition: all 0.2s ease-in-out; transform-origin: bottom left; }
    .attach-menu.hidden { transform: scale(0.9); opacity: 0; pointer-events: none; }
</style>

<script>
    const batchId = <?php echo $selected_batch; ?>;
    const feed = document.getElementById('admin-chat-feed');
    const msgInput = document.getElementById('admin-msg');
    let lastId = 0;
    let isUserScrolling = false;

    // Detect if admin is scrolling up to prevent auto-scroll jump
    if(feed) {
        feed.addEventListener('scroll', () => {
            isUserScrolling = feed.scrollTop + feed.clientHeight < feed.scrollHeight - 50;
        });
    }

    // Attachment Menu UI Logic
    function toggleAttachMenu(e) { 
        if(e) e.stopPropagation();
        document.getElementById('attach-menu').classList.toggle('hidden'); 
    }
    function closeAttachMenu() { 
        const m = document.getElementById('attach-menu'); 
        if(m) m.classList.add('hidden'); 
    }

    // ENTER key listener
    if(msgInput) {
        msgInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault(); // Prevent accidental form submissions
                sendAdminMsg();
            }
        });
    }

    // Helper: Safely parse fetch responses in case server throws HTML errors
    async function safeFetchJSON(url, options) {
        const response = await fetch(url, options);
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch (err) {
            console.error("Server Response Error. Expected JSON, got:", text);
            return { error: "Server returned an invalid response." };
        }
    }

    // Helper: Parse tags for Admin view
    function parseMessageContentAdmin(text) {
        let parsed = text;
        parsed = parsed.replace(/\[IMG\](.*?)\[\/IMG\]/g, (match, path) => `<a href="../${path}" target="_blank" class="block mt-2"><img src="../${path}" class="w-full max-w-[200px] rounded border border-gray-300 shadow-sm hover:opacity-90"></a>`);
        parsed = parsed.replace(/\[FILE\](.*?)\|(.*?)\[\/FILE\]/g, (match, path, filename) => `
            <a href="../${path}" download="${filename}" class="flex items-center gap-3 bg-white p-2 rounded border border-gray-200 mt-2 hover:bg-gray-50 transition text-gray-800">
                <div class="bg-gray-100 p-2 rounded text-indigo-500"><i class="fas fa-file-download"></i></div>
                <div><p class="text-[11px] font-bold truncate w-32">${filename}</p><p class="text-[9px] text-gray-500 uppercase">Download File</p></div>
            </a>`);
        parsed = parsed.replace(/\[LOC\](.*?)\[\/LOC\]/g, (match, coords) => `
            <a href="https://www.google.com/maps/search/?api=1&query=${coords}" target="_blank" class="flex items-center gap-2 mt-2 bg-blue-50 text-blue-700 px-3 py-2 rounded border border-blue-100 hover:bg-blue-100 transition w-fit">
                <i class="fas fa-map-marker-alt"></i><span class="text-xs font-bold">View Location</span>
            </a>`);
        return parsed;
    }

    function fetchChat() {
        if(batchId === 0 || !feed) return;

        const fd = new FormData();
        fd.append('action', 'fetch');
        fd.append('batch_id', batchId);
        fd.append('last_id', lastId);

        safeFetchJSON('../chat_api.php', { method: 'POST', body: fd })
        .then(data => {
            if (data.error) {
                console.error("Fetch Error:", data.error);
                return;
            }
            if(data.messages && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    renderMsg(msg);
                    lastId = msg.id;
                });
                if(!isUserScrolling) feed.scrollTop = feed.scrollHeight;
            }
        }).catch(e => console.error("Network Error:", e));
    }

    function renderMsg(msg) {
        if (!feed) return;
        
        const isAdmin = msg.is_admin == 1;
        const style = isAdmin ? 'bg-yellow-50 border-l-4 border-hajjGold' : 'bg-white shadow-sm border border-gray-100';
        const nameDisplay = isAdmin ? 'Admin Announcement' : msg.name;
        const parsedContent = parseMessageContentAdmin(msg.message);
        
        const html = `
            <div class="flex items-start gap-3 w-full max-w-2xl ${isAdmin ? 'ml-auto' : ''}" id="msg-${msg.id}">
                <div class="${style} p-3 rounded-xl flex-grow text-sm relative group">
                    <div class="flex justify-between items-center mb-2">
                        <span class="font-bold ${isAdmin ? 'text-hajjGold' : 'text-deepGreen'} text-xs flex items-center gap-1">
                            ${isAdmin ? '<i class="fas fa-bullhorn"></i>' : ''} ${nameDisplay}
                        </span>
                        <div class="flex items-center gap-3">
                            <span class="text-[10px] text-gray-400 font-medium">${msg.time}</span>
                            <button onclick="deleteMsg(${msg.id})" class="text-gray-300 hover:text-red-500 transition" title="Delete Message">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div class="text-gray-800 leading-relaxed word-break">${parsedContent}</div>
                </div>
            </div>
        `;
        feed.insertAdjacentHTML('beforeend', html);
    }

    function toggleLoading(isLoading) {
        const icon = document.getElementById('send-icon');
        if (icon) icon.className = isLoading ? 'fas fa-circle-notch fa-spin' : 'fas fa-paper-plane';
    }

    // Action: File Upload
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

        safeFetchJSON('../chat_api.php', { method: 'POST', body: fd })
        .then(res => {
            toggleLoading(false);
            if(res.error) AppUI.toast(res.error, 'error');
            fetchChat(); 
        })
        .catch(err => {
            toggleLoading(false);
            AppUI.toast('Upload failed. Please check file size.', 'error');
        });

        // Reset input
        event.target.value = '';
    }

    // Action: Location Share
    function shareLocation() {
        closeAttachMenu();
        if (!navigator.geolocation) {
            AppUI.alert("Geolocation is not supported by your browser.", "error");
            return;
        }
        
        toggleLoading(true);
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const locStr = `[LOC]${pos.coords.latitude},${pos.coords.longitude}[/LOC]`;
                
                const fd = new FormData();
                fd.append('action', 'send');
                fd.append('batch_id', batchId);
                fd.append('message', locStr);

                safeFetchJSON('../chat_api.php', { method: 'POST', body: fd })
                .then(() => {
                    toggleLoading(false);
                    fetchChat();
                });
            },
            (error) => {
                toggleLoading(false);
                AppUI.toast("Unable to retrieve location. Ensure location services are enabled.", "warning");
            }
        );
    }

    // Action: Text Message
    function sendAdminMsg() {
        if (!msgInput) return;
        const txt = msgInput.value.trim();
        if(!txt) return;

        toggleLoading(true);

        const fd = new FormData();
        fd.append('action', 'send');
        fd.append('batch_id', batchId);
        fd.append('message', txt);

        safeFetchJSON('../chat_api.php', { method: 'POST', body: fd })
        .then(res => {
            toggleLoading(false);
            if (res.error) {
                AppUI.toast(res.error, 'error');
            } else {
                msgInput.value = '';
                fetchChat();
            }
        })
        .catch(err => {
            toggleLoading(false);
            console.error("Network Error:", err);
        });
    }

    // Action: Delete
    function deleteMsg(id) {
        AppUI.confirm("Are you sure you want to delete this message?<br>This will remove it from all pilgrim devices.", () => {
            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('msg_id', id);

            safeFetchJSON('../chat_api.php', { method: 'POST', body: fd })
            .then(res => {
                if (res.status === 'deleted') {
                    const el = document.getElementById('msg-'+id);
                    if (el) el.remove();
                    AppUI.toast("Message deleted.", "success");
                } else {
                    AppUI.toast(res.error || "Failed to delete.", "error");
                }
            })
            .catch(err => console.error("Network Error:", err));
        });
    }

    // Initialize Polling
    if(batchId > 0) {
        setInterval(fetchChat, 3000);
        fetchChat();
    }
</script>

<?php include '../includes/footer.php'; ?>