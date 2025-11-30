<?php
// contact.php - fixed version
// Place in C:\xampp\htdocs\techpub\contact.php
session_start();
require_once __DIR__ . '/config.php';

// Load PHPMailer (composer preferred)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
} else {
    require __DIR__ . '/PHPMailer/src/Exception.php';
    require __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require __DIR__ . '/PHPMailer/src/SMTP.php';
}
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// helpers
function clean($s) { return htmlspecialchars(trim($s), ENT_QUOTES, 'UTF-8'); }
function abort_with_error($msg) {
    // simple output for development; adjust for production
    echo "<div style='background:#fee;padding:12px;border:1px solid #faa;margin:10px 0;'>Error: " . htmlspecialchars($msg) . "</div>";
    return;
}

$errors = [];
$success = '';

// file upload settings
$allowedMime = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];
$maxSize = 5 * 1024 * 1024; // 5MB
$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Invalid form submission.";
    } else {

        // gather inputs
        $name    = isset($_POST['name']) ? clean($_POST['name']) : '';
        $company = isset($_POST['company']) ? clean($_POST['company']) : '';
        $email   = isset($_POST['email']) ? clean($_POST['email']) : '';
        $phone   = isset($_POST['phone']) ? clean($_POST['phone']) : '';
        $service = isset($_POST['service']) ? clean($_POST['service']) : '';
        $brief   = isset($_POST['brief']) ? clean($_POST['brief']) : '';

        // server-side validation
        if (strlen($name) < 2) $errors[] = "Please enter your name.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email address.";
        if (empty($service)) $errors[] = "Please select a service.";
        if (strlen($brief) < 10) $errors[] = "Please add a short project brief.";

        // handle optional file
        $savedFilename = null;
        if (!empty($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $f = $_FILES['file'];
            if ($f['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "File upload error code: " . $f['error'];
            } else {
                if ($f['size'] > $maxSize) {
                    $errors[] = "Attachment exceeds 5 MB limit.";
                } else {
                    $mime = mime_content_type($f['tmp_name']);
                    if (!in_array($mime, $allowedMime, true)) {
                        $errors[] = "Only PDF or Word documents are allowed.";
                    } else {
                        $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
                        $safe = bin2hex(random_bytes(8)) . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', substr($f['name'], 0, 120));
                        $dest = $uploadDir . '/' . $safe;
                        if (!move_uploaded_file($f['tmp_name'], $dest)) {
                            $errors[] = "Unable to save uploaded file.";
                        } else {
                            $savedFilename = basename($dest);
                        }
                    }
                }
            }
        }

        // If validations passed, save to DB
        if (empty($errors)) {
            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS);
            if ($mysqli->connect_errno) {
                $errors[] = "DB connect failed: " . $mysqli->connect_error;
            } else {
                // Ensure database exists. If select_db fails, create DB then select.
                if (!$mysqli->select_db(DB_NAME)) {
                    $createSQL = "CREATE DATABASE IF NOT EXISTS `" . $mysqli->real_escape_string(DB_NAME) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
                    if (!$mysqli->query($createSQL)) {
                        $errors[] = "Failed to create DB: " . $mysqli->error;
                    } else {
                        if (!$mysqli->select_db(DB_NAME)) {
                            $errors[] = "Failed to select DB after creation: " . $mysqli->error;
                        }
                    }
                }

                if (empty($errors)) {
                    $createTable = "CREATE TABLE IF NOT EXISTS contacts (
                        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(255) NOT NULL,
                        company VARCHAR(255) DEFAULT NULL,
                        email VARCHAR(255) NOT NULL,
                        phone VARCHAR(50) DEFAULT NULL,
                        service VARCHAR(255) DEFAULT NULL,
                        brief TEXT NOT NULL,
                        attachment VARCHAR(512) DEFAULT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                    if (!$mysqli->query($createTable)) {
                        $errors[] = "Failed to create table: " . $mysqli->error;
                    } else {
                        $stmt = $mysqli->prepare("INSERT INTO contacts (name, company, email, phone, service, brief, attachment) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        if ($stmt) {
                            $attachDb = $savedFilename ?: null;
                            $stmt->bind_param('sssssss', $name, $company, $email, $phone, $service, $brief, $attachDb);
                            if (!$stmt->execute()) {
                                $errors[] = "Failed to save message: " . $stmt->error;
                            }
                            $stmt->close();
                        } else {
                            $errors[] = "DB prepare failed: " . $mysqli->error;
                        }
                    }
                }
                $mysqli->close();
            }
        }
// after $stmt->execute() and success
$lastId = $mysqli->insert_id ?? $stmt->insert_id ?? null;
if ($lastId) {
    // set source if not provided
    $src = 'Website';
    $u = $mysqli->prepare("UPDATE contacts SET status='New', source=? WHERE id=?");
    $u->bind_param('si', $src, $lastId); $u->execute(); $u->close();

    // add lead note
    $n = $mysqli->prepare("INSERT INTO lead_notes (lead_id, author, note) VALUES (?, ?, ?)");
    $initNote = "Lead received via website form";
    $adminName = 'system';
    $n->bind_param('iss', $lastId, $adminName, $initNote); $n->execute(); $n->close();
}
        // Send email via PHPMailer (only if no errors)
        if (empty($errors)) {
            $htmlBody = "<h3>New request: " . htmlspecialchars($service) . "</h3>"
                      . "<p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>"
                      . "<p><strong>Company:</strong> " . htmlspecialchars($company) . "</p>"
                      . "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>"
                      . "<p><strong>Phone:</strong> " . htmlspecialchars($phone) . "</p>"
                      . "<p><strong>Brief:</strong><br/>" . nl2br(htmlspecialchars($brief)) . "</p>";
            $plainBody = "New request\nName: {$name}\nCompany: {$company}\nEmail: {$email}\nPhone: {$phone}\nService: {$service}\nBrief:\n{$brief}";

            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USER;
                $mail->Password = SMTP_PASS;
                $mail->SMTPSecure = SMTP_SECURE;
                $mail->Port = SMTP_PORT;

                $mail->setFrom(SMTP_USER, 'ASISA Website');
                $mail->addAddress(RECIPIENT_EMAIL, RECIPIENT_NAME);
                $mail->addReplyTo($email, $name);
                if ($savedFilename) {
                    $mail->addAttachment($uploadDir . '/' . $savedFilename, $savedFilename);
                }

                $mail->isHTML(true);
                $mail->Subject = "Proposal request: " . ($service ?: 'General');
                $mail->Body = $htmlBody;
                $mail->AltBody = $plainBody;
                $mail->send();

                $success = "Thanks " . htmlspecialchars($name) . " — your request has been submitted.";
                // regenerate CSRF token and clear POST data
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                // clear local vars (so form shows empty on reload)
                $name = $company = $email = $phone = $service = $brief = '';
            } catch (Exception $e) {
                $errors[] = "Saved to DB but mail failed: " . $mail->ErrorInfo;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Contact — ASISA</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="assets/css/main.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body>
  <div id="header-topbar-include"></div>
  <div id="header-mobile-include"></div>
  <script src="assets/js/include.js"></script>
  <script src="assets/js/includes.js"></script>

  <main class="contact-section contact-wrapper" id="contact">
    <h2 style="font-size:28px;margin-bottom:12px;color:#0b1320;">Talk to a Team</h2>
    <p style="color:#555;margin-bottom:20px;max-width:900px;">Share your project brief and we'll propose a delivery team and an estimate for PM coverage.</p>

    <?php if (!empty($errors)): ?>
      <div class="error" style="display:block;margin-bottom:12px;">
        <ul><?php foreach ($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?></ul>
      </div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="pm-success" style="display:block;margin-bottom:12px;"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="contact-grid">
      <div>
        <div class="pm-form" aria-labelledby="contact-form-heading">
          <h3 id="contact-form-heading" style="margin:0 0 10px;color:#0b1320;">Request a Proposal</h3>
          <form id="pmContactForm" novalidate method="post" action="" enctype="multipart/form-data">
            <div class="row" style="margin-bottom:12px;">
              <div class="col">
                <label class="form-label" for="name">Full name</label>
                <input id="name" name="name" class="form-control" type="text" required value="<?php echo isset($name)?htmlspecialchars($name):''; ?>" />
                <div class="error" id="err-name" style="display:none">Please enter your name.</div>
              </div>
              <div class="col">
                <label class="form-label" for="company">Company</label>
                <input id="company" name="company" class="form-control" type="text" value="<?php echo isset($company)?htmlspecialchars($company):''; ?>" />
              </div>
            </div>

            <div class="row" style="margin-bottom:12px;">
              <div class="col">
                <label class="form-label" for="email">Email</label>
                <input id="email" name="email" class="form-control" type="email" required value="<?php echo isset($email)?htmlspecialchars($email):''; ?>" />
                <div class="error" id="err-email" style="display:none">Please enter a valid email.</div>
              </div>
              <div class="col">
                <label class="form-label" for="phone">Phone</label>
                <input id="phone" name="phone" class="form-control" type="tel" value="<?php echo isset($phone)?htmlspecialchars($phone):''; ?>" />
              </div>
            </div>

            <div style="margin-bottom:12px;">
              <label class="form-label" for="service">Service of interest</label>
              <select id="service" name="service" class="form-control" required>
                <option value="">Select a service</option>
                <?php
                  $options = [
                    "End-to-End Project Management",
                    "Programme & Schedule Management (4D)",
                    "Cost & Commercial Control (5D)",
                    "Coordination & Stakeholder Management"
                  ];
                  foreach ($options as $opt) {
                      $sel = (isset($service) && $service === $opt) ? 'selected' : '';
                      echo '<option value="'.htmlspecialchars($opt).'" '.$sel.'>'.htmlspecialchars($opt).'</option>';
                  }
                ?>
              </select>
              <div class="error" id="err-service" style="display:none">Please select a service.</div>
            </div>

            <div style="margin-bottom:12px;">
              <label class="form-label" for="brief">Project brief (short)</label>
              <textarea id="brief" name="brief" class="form-control" rows="4" required><?php echo isset($brief)?htmlspecialchars($brief):''; ?></textarea>
              <div class="error" id="err-brief" style="display:none">Please add a short project brief.</div>
            </div>

            <div style="display:flex;gap:12px;align-items:center;margin-bottom:12px;">
              <div style="flex:1;">
                <label class="form-label" for="file">Attach a brief (optional)</label>
                <input id="file" name="file" class="form-control" type="file" accept=".pdf,.doc,.docx" />
                <div class="small-note">Max 5MB. We accept PDF or Word files.</div>
              </div>
              <div style="min-width:140px;">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn-send" id="sendBtn">Send Request</button>
              </div>
            </div>

            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div style="font-size:13px;color:#666;">
              By submitting you agree that ASISA may contact you about this request.
            </div>
          </form>
        </div>
      </div>
                 <!-- RIGHT: Contact Cards + Map -->
      <aside>
        <div style="display:flex;flex-direction:column;gap:12px;">
          <div class="contact-card" role="group" aria-label="Contact options">
            <i class="fa-solid fa-envelope"></i>
            <div>
              <div style="font-weight:700;color:#0b1320;">Email</div>
              <div><a href="mailto:dipak.zagade8@gmail.com">dipak.zagade8@gmail.com</a></div>
            </div>
          </div>

          <div class="contact-card">
            <i class="fa-solid fa-phone"></i>
            <div>
              <div style="font-weight:700;color:#0b1320;">Phone</div>
              <div><a href="tel:+919623640101">+91 962 364 0101</a></div>
            </div>
          </div>

          <div class="contact-card">
            <i class="fa-solid fa-map-marker-alt"></i>
            <div>
              <div style="font-weight:700;color:#0b1320;">Office</div>
              <div>Albany, NY · Remote delivery across US/UK/Australia</div>
            </div>
          </div>

          <div class="map-box" aria-hidden="false" title="Office location (placeholder)">
            <!-- Replace the below <img> with an iframe to your Google Map or a static map image -->
            <img src="assets/img/project_management-services/office_map_placeholder.webp" alt="Office map placeholder" style="width:100%;height:100%;object-fit:cover;display:block;">
          </div>
        </div>
      </aside>
      <aside>
        <!-- FAQ Accordion -->
        <div style="margin-top:18px;">
          <h4 style="margin-bottom:8px;color:#0b1320;">Quick FAQs</h4>
          <div id="faq">
            <button class="doc-hero__cta" style="background:#fff;border:1px solid #e6edf3;color:#0b1320;padding:10px 12px;border-radius:6px;margin-bottom:8px;text-align:left;width:100%;" aria-expanded="false" aria-controls="faq1" data-target="#faq1">
              What happens after I submit?
            </button>
            <div id="faq1" hidden style="padding:12px;border-left:3px solid #ff7a1a;margin-bottom:8px;background:#fff;">
              We review your brief within 1–2 business days and propose a tailored PM scope and estimated coverage cost. We may request a short call for clarifications.
            </div>

            <button class="doc-hero__cta" style="background:#fff;border:1px solid #e6edf3;color:#0b1320;padding:10px 12px;border-radius:6px;margin-bottom:8px;text-align:left;width:100%;" aria-expanded="false" aria-controls="faq2" data-target="#faq2">
              Do you work with remote project teams?
            </button>
            <div id="faq2" hidden style="padding:12px;border-left:3px solid #ff7a1a;margin-bottom:8px;background:#fff;">
              Yes — our delivery model supports dedicated, hybrid and on-demand teams integrated with your CDE and reporting systems.
            </div>
          </div>
        </div>
      </div>
      </aside>
    </div>
  </main>

  <script>
    (function(){
      const form = document.getElementById('pmContactForm');
      const sendBtn = document.getElementById('sendBtn');

      function showError(id, show) {
        const el = document.getElementById(id);
        if(!el) return;
        el.style.display = show ? 'block' : 'none';
      }

      function validate() {
        let ok = true;
        const name = form.name.value.trim();
        const email = form.email.value.trim();
        const service = form.service.value.trim();
        const brief = form.brief.value.trim();

        if(!name) { showError('err-name', true); ok = false; } else showError('err-name', false);
        if(!email || !/^\S+@\S+\.\S+$/.test(email)) { showError('err-email', true); ok = false; } else showError('err-email', false);
        if(!service) { showError('err-service', true); ok = false; } else showError('err-service', false);
        if(!brief) { showError('err-brief', true); ok = false; } else showError('err-brief', false);

        return ok;
      }

      form.addEventListener('submit', function(e){
        if(!validate()) {
          e.preventDefault();
        } else {
          sendBtn.disabled = true;
          sendBtn.textContent = 'Sending...';
        }
      });
    })();
  </script>

  <div id="footer-include"></div>
  <script src="assets/js/footer.js"></script>
  <script src="assets/js/nav.js"></script>
</body>
</html>
