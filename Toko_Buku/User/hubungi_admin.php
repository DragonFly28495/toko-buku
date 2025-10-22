<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Form hubungi admin dengan fitur chat
include __DIR__ . '/../config.php';

// PERBAIKI: Gunakan pengecekan session_status()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah pengguna sudah login
if (!isset($_SESSION['id_pengguna']) || !isset($_SESSION['username'])) {
    header('Location: ../login-user.php');
    exit;
}


$user_id = $_SESSION['id_pengguna']; // gunakan id_pengguna dari session
$errors = [];
$success = '';

// Fungsi untuk mendapatkan atau membuat percakapan
function getOrCreateConversation($conn, $user_id)
{
    // Cek apakah sudah ada percakapan untuk pengguna ini
    $stmt = $conn->prepare('SELECT id_percakapan FROM percakapan WHERE id_pengguna = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['id_percakapan'];
    } else {
        // Buat percakapan baru
        $stmt = $conn->prepare('INSERT INTO percakapan (id_pengguna, subjek) VALUES (?, "Pertanyaan Pengguna")');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        return $stmt->insert_id;
    }
}

// Fungsi untuk mendapatkan pesan - VERSI DIPERBAIKI
function getMessages($conn, $id_percakapan, $user_id)
{
    $messages = [];

    // Debug: cek parameter
    error_log("getMessages called with id_percakapan: $id_percakapan, user_id: $user_id");

    $stmt = $conn->prepare('
        SELECT p.*, 
               CASE 
                   WHEN p.is_from_admin = 1 THEN "Admin"
                   ELSE dp.username 
               END as username,
               CASE 
                   WHEN p.is_from_admin = 1 THEN "admin" 
                   ELSE "user" 
               END as role
        FROM pesan p 
        LEFT JOIN daftar_pengguna dp ON p.id_pengirim = dp.id_pengguna AND p.is_from_admin = 0
        WHERE p.id_percakapan = ? 
        ORDER BY p.dibuat_pada ASC
    ');

    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return [];
    }

    $stmt->bind_param('i', $id_percakapan);

    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return [];
    }

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }

    $stmt->close();
    return $messages;
}

// Fungsi untuk mengirim pesan - VERSI DIPERBAIKI
function sendMessage($conn, $id_percakapan, $id_pengirim, $teks_pesan)
{
    error_log("sendMessage called: id_percakapan=$id_percakapan, id_pengirim=$id_pengirim, teks_pesan=$teks_pesan");

    // Untuk user, set is_from_admin = 0
    $stmt = $conn->prepare('INSERT INTO pesan (id_percakapan, id_pengirim, teks_pesan, is_from_admin) VALUES (?, ?, ?, 0)');

    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param('iis', $id_percakapan, $id_pengirim, $teks_pesan);

    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return false;
    }

    $insert_id = $stmt->insert_id;
    $stmt->close();

    // Update waktu pesan terakhir di percakapan
    $update_stmt = $conn->prepare('UPDATE percakapan SET pesan_terakhir_pada = NOW() WHERE id_percakapan = ?');
    if ($update_stmt) {
        $update_stmt->bind_param('i', $id_percakapan);
        $update_stmt->execute();
        $update_stmt->close();
    }

    return $insert_id;
}

// Fungsi untuk menandai pesan sebagai dibaca - VERSI DIPERBAIKI
function markMessagesAsRead($conn, $id_percakapan, $user_id)
{
    $stmt = $conn->prepare('
        UPDATE pesan SET sudah_dibaca = 1 
        WHERE id_percakapan = ? AND is_from_admin = 1 AND sudah_dibaca = 0
    ');
    $stmt->bind_param('i', $id_percakapan);
    $stmt->execute();
    $stmt->close();
}

// Proses AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'get_messages':
            $id_percakapan = $_POST['id_percakapan'];
            $messages = getMessages($conn, $id_percakapan, $user_id);
            markMessagesAsRead($conn, $id_percakapan, $user_id);
            echo json_encode($messages);
            exit;

        case 'send_message':
            $id_percakapan = $_POST['id_percakapan'];
            $teks_pesan = trim($_POST['teks_pesan']);

            error_log("AJAX send_message: id_percakapan=$id_percakapan, teks_pesan=$teks_pesan");

            if (empty($teks_pesan)) {
                echo json_encode(['error' => 'Pesan tidak boleh kosong']);
                exit;
            }

            $id_pesan = sendMessage($conn, $id_percakapan, $user_id, $teks_pesan);

            if (!$id_pesan) {
                error_log("Failed to send message");
                echo json_encode(['error' => 'Gagal mengirim pesan']);
                exit;
            }

            // Ambil data pesan yang baru dikirim - VERSI DIPERBAIKI
            $stmt = $conn->prepare('
                SELECT p.*, dp.username, "user" as role
                FROM pesan p 
                JOIN daftar_pengguna dp ON p.id_pengirim = dp.id_pengguna 
                WHERE p.id_pesan = ?
            ');

            if ($stmt) {
                $stmt->bind_param('i', $id_pesan);
                $stmt->execute();
                $result = $stmt->get_result();
                $new_message = $result->fetch_assoc();
                $stmt->close();

                echo json_encode($new_message);
            } else {
                echo json_encode(['error' => 'Gagal mengambil data pesan']);
            }
            exit;
    }
}

// Dapatkan atau buat percakapan untuk pengguna ini
$id_percakapan = getOrCreateConversation($conn, $user_id);
$messages = getMessages($conn, $id_percakapan, $user_id);
markMessagesAsRead($conn, $id_percakapan, $user_id);

// Ambil username pengguna
$stmt = $conn->prepare('SELECT username FROM daftar_pengguna WHERE id_pengguna = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$username = $user_data['username'];
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hubungi Admin - Toko BUKU</title>
    <link rel="stylesheet" href="style.css">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reset dan gaya dasar */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            color: #333;
            line-height: 1.6;
        }

        /* Main Container */
        .main-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            flex: 1;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .page-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #6c757d;
            font-size: 1.1rem;
        }

        /* Chat Container */
        .chat-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            max-width: 800px;
            margin: 0 auto;
            height: 600px;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }

        .chat-header h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.4rem;
        }

        .chat-header small {
            opacity: 0.9;
        }

        .chat-messages {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            background: #f8f9fa;
        }

        .message {
            max-width: 70%;
            padding: 0.75rem 1rem;
            border-radius: 18px;
            position: relative;
            word-wrap: break-word;
        }

        .message.user {
            align-self: flex-end;
            background: #3498db;
            color: white;
            border-bottom-right-radius: 6px;
        }

        .message.admin {
            align-self: flex-start;
            background: white;
            color: #333;
            border: 1px solid #e0e0e0;
            border-bottom-left-radius: 6px;
        }

        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.25rem;
            text-align: right;
        }

        .message.admin .message-time {
            text-align: left;
        }

        .no-messages {
            text-align: center;
            color: #6c757d;
            padding: 2rem;
            font-style: italic;
        }

        .chat-input {
            padding: 1.5rem;
            border-top: 1px solid #e9ecef;
            background: white;
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }

        .chat-input textarea {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-radius: 24px;
            resize: none;
            font-family: inherit;
            font-size: 1rem;
            line-height: 1.5;
            max-height: 120px;
            outline: none;
            transition: border-color 0.3s;
        }

        .chat-input textarea:focus {
            border-color: #3498db;
        }

        .chat-input button {
            padding: 0.75rem 1.5rem;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 24px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
            white-space: nowrap;
        }

        .chat-input button:hover:not(:disabled) {
            background: #2980b9;
        }

        .chat-input button:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .chat-container {
                height: 500px;
                margin: 0 1rem;
            }
        }

        @media (max-width: 768px) {
            .message {
                max-width: 85%;
            }

            .page-title {
                font-size: 1.8rem;
            }

            .chat-header {
                padding: 1rem;
            }

            .chat-messages {
                padding: 1rem;
            }

            .chat-input {
                padding: 1rem;
                flex-direction: column;
                gap: 0.75rem;
            }

            .chat-input button {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .main-container {
                padding: 0 0.5rem;
            }

            .page-title {
                font-size: 1.6rem;
            }

            .chat-container {
                height: 400px;
                border-radius: 8px;
            }

            .message {
                max-width: 90%;
                padding: 0.5rem 0.75rem;
            }
        }
    </style>
</head>

<body>
    <?php require "navbar.php"; ?>

    <div class="main-container">
        <div class="page-header">
            <h1 class="page-title">Hubungi Admin</h1>
            <p class="page-subtitle">Chat langsung dengan admin untuk pertanyaan dan bantuan</p>
        </div>

        <div class="chat-container">
            <div class="chat-header">
                <h3>Chat dengan Admin</h3>
                <small>Anda: <?php echo htmlspecialchars($username); ?></small>
            </div>

            <div class="chat-messages" id="chatMessages">
                <?php if (empty($messages)): ?>
                    <div class="no-messages">
                        Belum ada pesan. Mulai percakapan dengan admin!
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message <?php echo $message['role'] === 'user' ? 'user' : 'admin'; ?>">
                            <div><?php echo htmlspecialchars($message['teks_pesan']); ?></div>
                            <div class="message-time">
                                <?php echo date('H:i', strtotime($message['dibuat_pada'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="chat-input">
                <textarea id="messageInput" placeholder="Ketik pesan Anda di sini..." rows="1"></textarea>
                <button id="sendButton" onclick="sendMessage()">Kirim</button>
            </div>
        </div>
    </div>
        <?php require "footer.php"; ?>

    <script>
        const id_percakapan = <?php echo $id_percakapan; ?>;
        let isSending = false;

        // Fungsi untuk mengirim pesan
        function sendMessage() {
            if (isSending) return;

            const messageInput = document.getElementById('messageInput');
            const teks_pesan = messageInput.value.trim();

            if (teks_pesan === '') return;

            isSending = true;
            document.getElementById('sendButton').disabled = true;

            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('id_percakapan', id_percakapan);
            formData.append('teks_pesan', teks_pesan);

            fetch('hubungi_admin.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    console.log('Response:', data); // Debug

                    if (data.error) {
                        alert('Error: ' + data.error);
                        return;
                    }

                    // Tambahkan pesan ke chat
                    addMessageToChat(data);

                    // Reset input
                    messageInput.value = '';
                    messageInput.style.height = 'auto';

                    isSending = false;
                    document.getElementById('sendButton').disabled = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat mengirim pesan: ' + error.message);
                    isSending = false;
                    document.getElementById('sendButton').disabled = false;
                });
        }

        // Fungsi untuk menambahkan pesan ke chat
        function addMessageToChat(messageData) {
            const chatMessages = document.getElementById('chatMessages');
            const noMessages = chatMessages.querySelector('.no-messages');

            if (noMessages) {
                noMessages.remove();
            }

            const messageDiv = document.createElement('div');
            messageDiv.className = 'message user';

            const messageContent = document.createElement('div');
            messageContent.textContent = messageData.teks_pesan;

            const messageTime = document.createElement('div');
            messageTime.className = 'message-time';
            messageTime.textContent = new Date().toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit'
            });

            messageDiv.appendChild(messageContent);
            messageDiv.appendChild(messageTime);
            chatMessages.appendChild(messageDiv);

            // Scroll ke bawah
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Fungsi untuk memuat pesan - VERSI DIPERBAIKI
        function loadMessages() {
            const formData = new FormData();
            formData.append('action', 'get_messages');
            formData.append('id_percakapan', id_percakapan);

            fetch('hubungi_admin.php', {
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
                    console.log('Loaded messages:', messages); // Debug

                    const chatMessages = document.getElementById('chatMessages');
                    const currentMessages = chatMessages.querySelectorAll('.message');
                    const currentMessageCount = currentMessages.length;

                    // Hanya update jika ada pesan baru
                    if (messages.length !== currentMessageCount) {
                        chatMessages.innerHTML = '';

                        if (messages.length === 0) {
                            const noMessages = document.createElement('div');
                            noMessages.className = 'no-messages';
                            noMessages.textContent = 'Belum ada pesan. Mulai percakapan dengan admin!';
                            chatMessages.appendChild(noMessages);
                        } else {
                            messages.forEach(message => {
                                const messageDiv = document.createElement('div');
                                messageDiv.className = `message ${message.role === 'user' ? 'user' : 'admin'}`;

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
            const chatMessages = document.getElementById('chatMessages');

            // Auto-resize textarea
            messageInput.addEventListener('input', function () {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });

            // Kirim pesan dengan Enter (tanpa Shift)
            messageInput.addEventListener('keypress', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });

            // Scroll ke bawah saat halaman dimuat
            chatMessages.scrollTop = chatMessages.scrollHeight;

            // Auto-refresh pesan setiap 3 detik
            setInterval(loadMessages, 3000);
        });
    </script>
</body>

</html>