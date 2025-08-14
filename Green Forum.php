<?php
$host = 'localhost'; 
$db   = 'u894972567_green_forum'; 
$user = 'u894972567_green_forum'; 
$pass = 'Green_forum1!'; 
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE             => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES    => false,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Create 'posts' table if it doesn't exist
    // This will run once to set up the tables on your Hostinger database
    $pdo->exec("CREATE TABLE IF NOT EXISTS posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        author VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Create 'replies' table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS replies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT,
        author VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
    )");

} catch (\PDOException $e) {
    // In a live environment, log the error and show a generic message to the user
    error_log("Database connection failed: " . $e->getMessage()); // Log error to server logs
    die("An error occurred while connecting to the database. Please try again later."); // User-friendly message
}

// 2. Handle New Post Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'new_post') {
    // Using FILTER_UNSAFE_RAW to store the raw string, PDO will handle escaping for the DB.
    // For PHP 8.1+, direct $_POST access is generally preferred for plain text inputs:
    // $author = $_POST['forum-name'] ?? '';
    // $subject = $_POST['forum-subject'] ?? '';
    // $message = $_POST['forum-message'] ?? '';
    $author = filter_input(INPUT_POST, 'forum-name', FILTER_UNSAFE_RAW);
    $subject = filter_input(INPUT_POST, 'forum-subject', FILTER_UNSAFE_RAW);
    $message = filter_input(INPUT_POST, 'forum-message', FILTER_UNSAFE_RAW);

    // Trim whitespace from inputs
    $author = trim((string)$author);
    $subject = trim((string)$subject);
    $message = trim((string)$message);

    if ($author && $subject && $message) {
        try {
            $stmt = $pdo->prepare("INSERT INTO posts (author, subject, message) VALUES (:author, :subject, :message)");
            // Data is stored as raw text in the database
            $stmt->execute([':author' => $author, ':subject' => $subject, ':message' => $message]);
            // Redirect to prevent form resubmission on refresh
            header("Location: Green%20Forum.php"); // Redirect to the new page name
            exit();
        } catch (PDOException $e) {
            error_log("Error posting new post: " . $e->getMessage());
            echo "Error posting: " . $e->getMessage(); // For debugging, remove for live
        }
    }
}

// 3. Handle New Reply Submission (via AJAX, so this is a separate endpoint logic)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'new_reply') {
    $post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    // Using FILTER_UNSAFE_RAW here as well.
    $author = filter_input(INPUT_POST, 'reply-name', FILTER_UNSAFE_RAW);
    $message = filter_input(INPUT_POST, 'reply-message', FILTER_UNSAFE_RAW);

    // Trim whitespace from inputs
    $author = trim((string)$author);
    $message = trim((string)$message);

    if ($post_id && $author && $message) {
        try {
            $stmt = $pdo->prepare("INSERT INTO replies (post_id, author, message) VALUES (:post_id, :author, :message)");
            // Data is stored as raw text in the database
            $stmt->execute([':post_id' => $post_id, ':author' => $author, ':message' => $message]);

            // When returning data for AJAX, we still want to htmlspecialchars() it for safe display in JavaScript
            echo json_encode([
                'success' => true,
                'author' => htmlspecialchars($author), // Keep htmlspecialchars here for display
                'message' => htmlspecialchars($message), // Keep htmlspecialchars here for display
                'created_at' => date('F j, Y') // Or format as needed
            ]);
            exit();
        } catch (PDOException $e) {
            error_log("Error posting new reply: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid reply data.']);
        exit();
    }
}

// 4. Fetch All Posts and their Replies
$posts = [];
try {
    $stmt_posts = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC");
    $all_posts = $stmt_posts->fetchAll(PDO::FETCH_ASSOC);

    foreach ($all_posts as $post) {
        $stmt_replies = $pdo->prepare("SELECT * FROM replies WHERE post_id = :post_id ORDER BY created_at ASC");
        $stmt_replies->execute([':post_id' => $post['id']]);
        $post['replies'] = $stmt_replies->fetchAll(PDO::FETCH_ASSOC);
        $posts[] = $post;
    }
} catch (PDOException $e) {
    error_log("Error fetching posts: " . $e->getMessage());
    echo "Error fetching posts: " . $e->getMessage(); // For debugging, remove for live
}
?>

<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>FoundationfortheFuture</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.min.css">
    <link rel="icon" href="/assets/img/Asset%205.png" type="image/png">
    <style>
        /* Custom styles for the forum elements */
        .forum-post {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .forum-post .post-title {
            font-weight: bold;
            color: #333;
        }

        .forum-post .post-meta {
            font-size: 0.9em;
            color: #777;
            margin-bottom: 10px;
        }

        .forum-post .post-content {
            margin-bottom: 10px;
        }

        .forum-post .reply-section {
            margin-top: 15px;
            border-top: 1px dashed #eee;
            padding-top: 10px;
        }

        .forum-post .reply-form {
            margin-top: 10px;
        }

        .forum-post .reply-item {
            background-color: #f9f9f9;
            border-left: 3px solid #badd7f; /* Adjusted to new theme color */
            padding: 10px;
            margin-top: 8px;
            border-radius: 4px;
        }

        .forum-post .reply-item .reply-meta {
            font-size: 0.85em;
            color: #888;
            margin-bottom: 5px;
        }

        .section-separator {
            height: 2px;
            background: linear-gradient(to right, #f7efda, #badd7f, #f7efda); /* Adjusted to new theme colors */
            border: none;
            margin-top: 60px;
            margin-bottom: 60px;
            opacity: 0.7;
        }
    </style>
</head>

<body style="background: #f7efda;">
    <div id="header"></div>

    <div data-bss-parallax-bg="true"
        style="height: 600px;background: url(&quot;assets/img/photo-1533038023143-de7a62a9d779.jpg?h=3d1bed42a2e6fb709c04635b9209151f&quot;) center / cover;">
        <div class="container h-100">
            <div class="row h-100">
                <div
                    class="col-md-6 text-center text-md-start d-flex d-sm-flex d-md-flex justify-content-center align-items-center justify-content-md-start align-items-md-center justify-content-xl-center">
                    <div>
                        <h2 class="text-capitalize fw-light"
                            style="color: #f7efda; font-family: Marcellus, serif; font-size: clamp(35px, 10vw, 77px); font-weight: bold;">
                            Green Forum
                        </h2>
                        <p
                            style="color: #f7efda;font-size: 17px;text-shadow: 0px 0px 20px #000000;margin-top: 0px;">
                            We’re here to help and answer any question you might have. We look forward to hearing from
                            you!
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <hr class="section-separator">

    <section id="forum-section" class="py-5">
        <div class="container">
            <h2 class="text-center mb-4" style="font-family: Marcellus, serif; color: #badd7f;">Join the Discussion</h2>

            <div class="card mb-4" style="border: none; background-color: #f7efda;">
                <div class="card-body">
                    <h4 class="card-title">Create a New Post</h4>
                    <form id="new-post-form" method="POST" action="Green%20Forum.php">
                        <input type="hidden" name="action" value="new_post">
                        <div class="mb-3">
                            <label for="forum-name" class="form-label">Your Name</label>
                            <input type="text" class="form-control" id="forum-name" name="forum-name" placeholder="Enter your name" required>
                        </div>
                        <div class="mb-3">
                            <label for="forum-subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="forum-subject" name="forum-subject" placeholder="What is your post about?" required>
                        </div>
                        <div class="mb-3">
                            <label for="forum-message" class="form-label">Message</label>
                            <textarea class="form-control" id="forum-message" name="forum-message" rows="4" placeholder="Write your message here..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" style="background-color: #badd7f; border-color: #badd7f;">Post Topic</button>
                    </form>
                </div>
            </div>

            <h3 class="mb-3" style="font-family: Marcellus, serif;">Recent Topics</h3>

            <div id="forum-posts-container">
                <?php if (empty($posts)): ?>
                    <p class="text-center">No posts yet. Be the first to start a discussion!</p>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                      <div class="card mb-3 forum-post">
    <div class="card-body">
        <h5 class="card-title post-title"><?= htmlspecialchars($post['subject']) ?></h5>
        <h6 class="card-subtitle mb-2 text-muted post-meta">
            by <?= htmlspecialchars($post['author']) ?> - <?= date('F j, Y', strtotime($post['created_at'])) ?>
        </h6>
        <p class="card-text post-content"><?= nl2br(htmlspecialchars($post['message'])) ?></p>

        <!-- FLEX CONTAINER FOR ALIGNED BUTTONS -->
        <div class="d-flex gap-3 align-items-center flex-wrap">
            <a href="#" 
               class="card-link reply-button" 
               style="color: #badd7f;" 
               data-bs-toggle="collapse" 
               data-bs-target="#replies-post-<?= $post['id'] ?>">
               Reply
            </a>
            <a href="#" 
               class="card-link view-replies-button" 
               style="color: #badd7f;" 
               data-bs-toggle="collapse" 
               data-bs-target="#replies-post-<?= $post['id'] ?>">
               View <?= count($post['replies']) ?> Repl<?= count($post['replies']) === 1 ? 'y' : 'ies' ?>
            </a>
        </div>

        <div class="collapse reply-section" id="replies-post-<?= $post['id'] ?>">
            <h6>Replies:</h6>
            <?php if (empty($post['replies'])): ?>
                <p class="text-muted">No replies yet.</p>
            <?php else: ?>
                <?php foreach ($post['replies'] as $reply): ?>
                    <div class="reply-item">
                        <p class="reply-meta">
                            by <?= htmlspecialchars($reply['author']) ?> - <?= date('F j, Y', strtotime($reply['created_at'])) ?>
                        </p>
                        <p><?= nl2br(htmlspecialchars($reply['message'])) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <form class="reply-form" data-post-id="<?= $post['id'] ?>">
                <div class="mb-2">
                    <input type="text" class="form-control form-control-sm reply-name" placeholder="Your Name" required>
                </div>
                <div class="mb-2">
                    <textarea class="form-control form-control-sm reply-message" rows="2" placeholder="Write your reply here..." required></textarea>
                </div>
                <button type="submit" class="btn btn-secondary btn-sm reply-submit-button" style="background-color: #badd7f; border-color: #badd7f;">
                    Post Reply
                </button>
            </form>
        </div>
    </div>
</div>

                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            </div>
    </section>

    <div id="footer"></div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.min.js?h=e21329cc057f51c00970539ac9834d22"></script>
    <script src="/assets/js/partials.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Function to format the date - not strictly used for new replies as PHP handles it initially
            function getFormattedDate(dateString) {
                const date = new Date(dateString);
                const options = { year: 'numeric', month: 'long', day: 'numeric' };
                return date.toLocaleDateString('en-US', options);
            }

            // Function to add a reply to a specific post (handles AJAX response)
            async function addReply(replyForm, postId, name, message) {
                const formData = new FormData();
                formData.append('action', 'new_reply');
                formData.append('post_id', postId);
                formData.append('reply-name', name);
                formData.append('reply-message', message);

                try {
                    // --- IMPORTANT: Update the fetch URL to your new PHP file name ---
                    const response = await fetch('Green%20Forum.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (result.success) {
                        // The message received from PHP is already HTML-escaped,
                        // so we can insert it directly. Replace newlines for <br> tags.
                        const replyHtml = `
                            <div class="reply-item">
                                <p class="reply-meta">by ${result.author} - ${result.created_at}</p>
                                <p>${result.message.replace(/\n/g, '<br>')}</p>
                            </div>
                        `;
                        const replyItemElement = document.createElement('div');
                        replyItemElement.innerHTML = replyHtml.trim();

                        const repliesContainer = replyForm.closest('.reply-section');
                        // Insert the new reply before the reply form itself
                        // Ensure the new element is appended to the correct container within the reply-section
                        const existingRepliesContainer = repliesContainer.querySelector('h6 + p.text-muted') || repliesContainer.querySelector('h6');
                        if (existingRepliesContainer && existingRepliesContainer.tagName === 'P') { // If "No replies yet" is present
                            existingRepliesContainer.remove(); // Remove the "No replies yet" message
                        }
                        repliesContainer.insertBefore(replyItemElement.firstChild, replyForm);


                        // Update the "View X Replies" link count
                        const cardBody = replyForm.closest('.card-body');
                        const viewRepliesLink = cardBody.querySelector('.view-replies-button');

                        if (viewRepliesLink) {
                            let currentRepliesText = viewRepliesLink.textContent;
                            let numReplies = 0;
                            const match = currentRepliesText.match(/\d+/);
                            if (match) {
                                numReplies = parseInt(match[0]);
                            }
                            numReplies++;
                            viewRepliesLink.textContent = `View ${numReplies} Repl${numReplies === 1 ? 'y' : 'ies'}`;
                        }

                        // Clear reply form fields
                        replyForm.querySelector('.reply-name').value = '';
                        replyForm.querySelector('.reply-message').value = '';

                    } else {
                        alert('Error posting reply: ' + result.error);
                    }
                } catch (error) {
                    console.error('Network or server error:', error);
                    alert('An error occurred while posting your reply. Please try again.');
                }
            }

            // Attach event listener to a single reply form
            function attachReplyFormListener(form) {
                form.addEventListener('submit', function (event) {
                    event.preventDefault(); // Prevent default form submission and page reload

                    const postId = this.dataset.postId; // Get post ID from data attribute
                    const replyNameInput = form.querySelector('.reply-name');
                    const replyMessageInput = form.querySelector('.reply-message');

                    const name = replyNameInput.value;
                    const message = replyMessageInput.value;

                    if (name && message) {
                        addReply(form, postId, name, message);
                    } else {
                        alert('Please enter your name and reply message.');
                    }
                });
            }

            // Attach event listeners to all existing reply forms initially (rendered by PHP)
            document.querySelectorAll('.reply-form').forEach(form => {
                attachReplyFormListener(form);
            });
        });
    </script>
    <script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text)
            .then(() => alert('Copied to clipboard!'))
            .catch(err => console.error('Failed to copy: ', err));
    }
    </script>
</body>
</html>