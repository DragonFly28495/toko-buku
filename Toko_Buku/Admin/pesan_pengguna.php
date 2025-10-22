<?php
// pesan_pengguna.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include config
$config_path = __DIR__ . '/../config.php';
if (!file_exists($config_path)) {
    die("File config tidak ditemukan: " . $config_path);
}
include $config_path;

// Validasi session
if (!isset($_SESSION['admin_id'], $_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Validasi koneksi database
if (!isset($pdo)) {
    die("Koneksi database tidak tersedia");
}

$admin_id = $_SESSION['admin_id'];

// Fungsi: Ambil daftar percakapan - VERSI DENGAN is_from_admin
function getConversations($pdo, $admin_id)
{
    $sql = "SELECT p.*, dp.username,
            (SELECT teks_pesan FROM pesan WHERE id_percakapan = p.id_percakapan ORDER BY dibuat_pada DESC LIMIT 1) AS pesan_terakhir,
            (SELECT dibuat_pada FROM pesan WHERE id_percakapan = p.id_percakapan ORDER BY dibuat_pada DESC LIMIT 1) AS waktu_pesan_terakhir,
            (SELECT COUNT(*) FROM pesan WHERE id_percakapan = p.id_percakapan AND sudah_dibaca = 0 AND is_from_admin = 0) AS jumlah_belum_dibaca
            FROM percakapan p
            JOIN daftar_pengguna dp ON p.id_pengguna = dp.id_pengguna
            ORDER BY waktu_pesan_terakhir DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// Fungsi: Ambil pesan dalam percakapan - VERSI DENGAN is_from_admin
function getMessages($pdo, $id_percakapan, $admin_id)
{
    $sql = "SELECT ps.*, 
            CASE 
                WHEN ps.is_from_admin = 1 THEN 'Admin'
                ELSE dp.username 
            END as username,
            CASE 
                WHEN ps.is_from_admin = 1 THEN 'admin' 
                ELSE 'user' 
            END as role
            FROM pesan ps
            LEFT JOIN daftar_pengguna dp ON ps.id_pengirim = dp.id_pengguna AND ps.is_from_admin = 0
            WHERE ps.id_percakapan = :id_percakapan
            ORDER BY ps.dibuat_pada ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_percakapan' => $id_percakapan]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// Fungsi: Kirim pesan - VERSI FIX FOREIGN KEY
// Fungsi: Kirim pesan - PASTIKAN RETURN ID VALID
function sendMessage($pdo, $id_percakapan, $admin_id, $teks_pesan)
{
    try {
        $sql = "INSERT INTO pesan (id_percakapan, id_pengirim, teks_pesan, is_from_admin) 
                VALUES (:id_percakapan, NULL, :teks_pesan, 1)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id_percakapan' => $id_percakapan,
            'teks_pesan' => $teks_pesan
        ]);

        $lastInsertId = $pdo->lastInsertId();

        // Pastikan ID valid
        if (!$lastInsertId || $lastInsertId == 0) {
            throw new Exception("Gagal mendapatkan ID pesan yang baru");
        }

        // Update waktu terakhir percakapan
        $update_sql = "UPDATE percakapan SET pesan_terakhir_pada = NOW() WHERE id_percakapan = :id_percakapan";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute(['id_percakapan' => $id_percakapan]);

        return $lastInsertId;

    } catch (PDOException $e) {
        error_log("Database error in sendMessage: " . $e->getMessage());
        throw new Exception("Gagal menyimpan pesan ke database");
    }
}

// Fungsi: Tandai pesan sebagai dibaca - VERSI DENGAN is_from_admin
function markMessagesAsRead($pdo, $id_percakapan, $admin_id)
{
    $sql = "UPDATE pesan SET sudah_dibaca = 1
            WHERE id_percakapan = :id_percakapan
            AND is_from_admin = 0
            AND sudah_dibaca = 0";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_percakapan' => $id_percakapan]);
}

// Proses AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        switch ($_POST['action']) {
            case 'get_messages':
    $id_percakapan = $_POST['id_percakapan'];
    $messages = getMessages($pdo, $id_percakapan, $admin_id);
    // PASTIKAN BARIS INI ADA:
    markMessagesAsRead($pdo, $id_percakapan, $admin_id);
    echo json_encode($messages);
    exit;
            // Di bagian AJAX handler, tambahkan logging lebih detail
            case 'send_message':
                try {
                    $id_percakapan = $_POST['id_percakapan'];
                    $teks_pesan = $_POST['teks_pesan'];

                    // Validasi input
                    if (empty($teks_pesan)) {
                        throw new Exception("Pesan tidak boleh kosong");
                    }

                    $id_pesan = sendMessage($pdo, $id_percakapan, $admin_id, $teks_pesan);

                    // Kembalikan data sederhana - tidak perlu query tambahan
                    $new_message = [
                        'id_pesan' => $id_pesan,
                        'teks_pesan' => $teks_pesan,
                        'username' => 'Admin',
                        'role' => 'admin',
                        'dibuat_pada' => date('Y-m-d H:i:s')
                    ];

                    echo json_encode($new_message);

                } catch (Exception $e) {
                    error_log("Send message error: " . $e->getMessage());
                    http_response_code(500);
                    echo json_encode(['error' => $e->getMessage()]);
                }
                exit;

            default:
                throw new Exception("Aksi tidak valid");
        }
    } catch (Exception $e) {
        error_log("Error in pesan_pengguna.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

// Tampilan awal
$percakapan_list = getConversations($pdo, $admin_id);
$selected_percakapan = null;
$messages = [];

// Di bagian ini, PASTIKAN markMessagesAsRead dipanggil:
if (isset($_GET['id_percakapan'])) {
    $selected_id_percakapan = $_GET['id_percakapan'];
    $messages = getMessages($pdo, $selected_id_percakapan, $admin_id);
    // PASTIKAN BARIS INI ADA DAN TIDAK DI-COMMENT:
    markMessagesAsRead($pdo, $selected_id_percakapan, $admin_id);

    foreach ($percakapan_list as $percakapan) {
        if ($percakapan['id_percakapan'] == $selected_id_percakapan) {
            $selected_percakapan = $percakapan;
            break;
        }
    }
}
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pesan Pengguna - Admin Toko BUKU</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style-admin.css">
    <link rel="stylesheet" href="../CSS/style_pesan_pengguna-admin.css">
</head>

<body>
    <div style="display: flex; min-height: 100vh;">
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <h1 class="page-title">Pesan Pengguna</h1>

            <div class="messages-container">
                <!-- Daftar Percakapan -->
                <div class="conversations-list">
                    <?php foreach ($percakapan_list as $percakapan): ?>
                        <div class="conversation-item <?php echo ($selected_percakapan && $selected_percakapan['id_percakapan'] == $percakapan['id_percakapan']) ? 'active' : ''; ?>"
                            onclick="selectConversation(<?php echo $percakapan['id_percakapan']; ?>)">
                            <div class="conversation-header">
                                <span class="user-name"><?php echo htmlspecialchars($percakapan['username']); ?></span>
                                <span class="message-time">
                                    <?php
                                    if ($percakapan['waktu_pesan_terakhir']) {
                                        echo date('H:i', strtotime($percakapan['waktu_pesan_terakhir']));
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="message-preview">
                                <?php echo htmlspecialchars($percakapan['pesan_terakhir'] ?? 'Tidak ada pesan'); ?>
                            </div>
                            <?php if ($percakapan['jumlah_belum_dibaca'] > 0): ?>
                                <span class="unread-badge"><?php echo $percakapan['jumlah_belum_dibaca']; ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($percakapan_list)): ?>
                        <div class="conversation-item">
                            <div class="message-preview">Belum ada pesan dari pengguna</div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Area Chat -->
                <div class="chat-container">
                    <?php if ($selected_percakapan): ?>
    <div class="chat-header">
        <h4><?php echo htmlspecialchars($selected_percakapan['username']); ?></h4>
    </div>

                        <div class="chat-messages" id="chatMessages">
                            <?php foreach ($messages as $message): ?>
                                <div class="message <?php echo $message['role'] == 'admin' ? 'admin' : 'user'; ?>">
                                    <div><?php echo htmlspecialchars($message['teks_pesan']); ?></div>
                                    <div class="message-time">
                                        <?php echo date('H:i', strtotime($message['dibuat_pada'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="chat-input">
                            <textarea id="messageInput" placeholder="Ketik pesan balasan..." rows="1"></textarea>
                            <button
                                onclick="sendMessage(<?php echo $selected_percakapan['id_percakapan']; ?>)">Kirim</button>
                        </div>
                    <?php else: ?>
                        <div class="no-conversation">
                            Pilih percakapan untuk melihat pesan
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
    <!-- Footer -->
    <footer class="footer">
        &copy; <?= date('Y') ?> Toko BUKU. All rights reserved.
    </footer>
    <script>
        // Fungsi untuk toggle dropdown
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            dropdown.style.display = dropdown.style.display === 'flex' ? 'none' : 'flex';
        }

        // Fungsi untuk memilih percakapan
        function selectConversation(id_percakapan) {
            window.location.href = 'pesan_pengguna.php?id_percakapan=' + id_percakapan;
        }

        // Variabel global
        let isSending = false;
        const selectedIdPercakapan = <?php echo $selected_percakapan['id_percakapan'] ?? 'null'; ?>;

        // Fungsi untuk mengirim pesan
        function sendMessage(id_percakapan) {
            if (isSending || !id_percakapan) return;

            const messageInput = document.getElementById('messageInput');
            const teks_pesan = messageInput.value.trim();

            if (teks_pesan === '') return;

            isSending = true;
            const sendButton = document.querySelector('.chat-input button');
            const originalText = sendButton.textContent;
            sendButton.textContent = 'Mengirim...';
            sendButton.disabled = true;

            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('id_percakapan', id_percakapan);
            formData.append('teks_pesan', teks_pesan);

            fetch('pesan_pengguna.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error(`HTTP ${response.status}: ${text}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        alert('Error: ' + data.error);
                        return;
                    }

                    // Tambahkan pesan ke chat
                    addMessageToChat(data, 'admin');

                    // Reset input
                    messageInput.value = '';
                    messageInput.style.height = 'auto';

                    // Scroll ke bawah
                    const chatMessages = document.getElementById('chatMessages');
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                })
                .catch(error => {
                    console.error('Error details:', error);
                    alert('Terjadi kesalahan saat mengirim pesan: ' + error.message);
                })
                .finally(() => {
                    // Reset button
                    isSending = false;
                    sendButton.textContent = originalText;
                    sendButton.disabled = false;
                });
        }

        // Fungsi untuk menambahkan pesan ke chat
        function addMessageToChat(messageData, role) {
            const chatMessages = document.getElementById('chatMessages');

            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${role}`;

            const messageContent = document.createElement('div');
            messageContent.textContent = messageData.teks_pesan;

            const messageTime = document.createElement('div');
            messageTime.className = 'message-time';

            // Gunakan waktu dari server jika tersedia, atau waktu sekarang
            if (messageData.dibuat_pada) {
                const messageDate = new Date(messageData.dibuat_pada);
                messageTime.textContent = messageDate.toLocaleTimeString('id-ID', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } else {
                messageTime.textContent = new Date().toLocaleTimeString('id-ID', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }

            messageDiv.appendChild(messageContent);
            messageDiv.appendChild(messageTime);
            chatMessages.appendChild(messageDiv);
        }

        // Fungsi untuk memuat pesan
        function loadMessages() {
            if (!selectedIdPercakapan) return;

            const formData = new FormData();
            formData.append('action', 'get_messages');
            formData.append('id_percakapan', selectedIdPercakapan);

            fetch('pesan_pengguna.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(messages => {
                    const chatMessages = document.getElementById('chatMessages');
                    if (!chatMessages) return;

                    const currentMessages = chatMessages.querySelectorAll('.message');
                    const currentMessageCount = currentMessages.length;

                    // Hanya update jika ada pesan baru
                    if (messages.length !== currentMessageCount) {
                        chatMessages.innerHTML = '';

                        if (messages.length === 0) {
                            const noMessages = document.createElement('div');
                            noMessages.className = 'no-conversation';
                            noMessages.textContent = 'Belum ada pesan';
                            chatMessages.appendChild(noMessages);
                        } else {
                            messages.forEach(message => {
                                const messageDiv = document.createElement('div');
                                messageDiv.className = `message ${message.role}`;

                                const messageContent = document.createElement('div');
                                messageContent.textContent = message.teks_pesan;

                                const messageTime = document.createElement('div');
                                messageTime.className = 'message-time';

                                // Format waktu dengan benar
                                const messageDate = new Date(message.dibuat_pada);
                                messageTime.textContent = messageDate.toLocaleTimeString('id-ID', {
                                    hour: '2-digit',
                                    minute: '2-digit'
                                });

                                messageDiv.appendChild(messageContent);
                                messageDiv.appendChild(messageTime);
                                chatMessages.appendChild(messageDiv);
                            });
                        }

                        // Scroll ke bawah
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }
                })
                .catch(error => {
                    console.error('Error loading messages:', error);
                    // Jangan tampilkan alert untuk error auto-refresh
                });
        }

        // Event listener saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function () {
            const messageInput = document.getElementById('messageInput');

            if (messageInput) {
                // Auto-resize textarea
                messageInput.addEventListener('input', function () {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });

                // Kirim pesan dengan Enter (tanpa Shift)
                messageInput.addEventListener('keypress', function (e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        if (selectedIdPercakapan) {
                            sendMessage(selectedIdPercakapan);
                        }
                    }
                });

                // Scroll ke bawah saat halaman dimuat
                const chatMessages = document.getElementById('chatMessages');
                if (chatMessages) {
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }

                // Auto-refresh pesan setiap 3 detik jika ada percakapan yang dipilih
                if (selectedIdPercakapan) {
                    setInterval(loadMessages, 3000);
                }
            }
        });

        // Auto-refresh daftar percakapan setiap 30 detik
        setInterval(() => {
            // Di sini bisa ditambahkan kode untuk memperbarui daftar percakapan
            // jika diperlukan, dengan reload halaman atau AJAX
            if (!selectedIdPercakapan) {
                location.reload(); // Refresh halaman jika tidak ada percakapan yang dipilih
            }
        }, 30000);
    // === SAMPE SINI ===
    </script>
</body>

</html>