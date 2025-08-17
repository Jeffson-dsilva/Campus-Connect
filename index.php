<?php
require_once __DIR__ . '/app/core/config.php';
$target = BASE_URL . 'app/login.php';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Campus Connect — Loading…</title>
  <meta http-equiv="refresh" content="3;url=<?php echo htmlspecialchars($target, ENT_QUOTES); ?>">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
  <style>
    :root { --bg1:#e0f2fe; --bg2:#c7d2fe; --text:#1e3a8a; --primary:#2563eb; }
    *{ box-sizing:border-box; }
    html,body{ height:100%; margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial, sans-serif; }
    body{ background: linear-gradient(135deg, var(--bg1), var(--bg2)); display:flex; align-items:center; justify-content:center; }
    .card{ width:min(560px, 92%); background:#ffffff; border-radius:16px; box-shadow: 0 15px 40px rgba(2,6,23,0.15); padding:32px 28px; text-align:center; }
    .logo{ width:64px; height:64px; object-fit:contain; margin:0 auto 12px; display:block; }
    .title{ margin:6px 0 2px; font-weight:800; letter-spacing:.2px; color:var(--text); font-size:22px; }
    .subtitle{ margin:0; color:#475569; font-size:13px; }
    .progress{ position:relative; height:10px; background:#e5e7eb; border-radius:999px; overflow:hidden; margin:22px auto 0; width:86%; }
    .bar{ position:absolute; inset:0; width:0%; background: linear-gradient(90deg, #60a5fa, #2563eb); animation: load 2.4s ease-in-out forwards; border-radius:inherit; }
    @keyframes load{ to{ width:100%; } }
    .spinner{ margin:18px auto 0; width:28px; height:28px; border:3px solid #bfdbfe; border-top-color:#2563eb; border-radius:50%; animation:spin 1s linear infinite; }
    @keyframes spin{ to{ transform:rotate(360deg);} }
    .hint{ margin-top:12px; color:#64748b; font-size:12px; }
    .fade-in{ animation:fade .6s ease both; }
    @keyframes fade{ from{opacity:0; transform:translateY(4px);} to{opacity:1; transform:none;} }
    .sr-only{ position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }
  </style>
  <script>
    // JS redirect after 3 seconds (fallback in addition to meta refresh)
    window.addEventListener('DOMContentLoaded', function(){
      setTimeout(function(){
        window.location.replace('<?php echo htmlspecialchars($target, ENT_QUOTES); ?>');
      }, 3000);
      // Animate progress if JS enabled
      const bar = document.querySelector('.bar');
      if (bar) { bar.style.animationDuration = '3s'; }
    });
  </script>
</head>
<body>
  <main class="card fade-in" role="main" aria-label="Loading Campus Connect">
    <img class="logo" src="<?php echo BASE_URL; ?>images/logo.png" alt="College Logo">
    <h1 class="title">Campus Connect</h1>
    <p class="subtitle">Preparing your experience…</p>
    <div class="progress" aria-hidden="true"><div class="bar"></div></div>
    <div class="spinner" aria-hidden="true"></div>
    <p class="hint">You will be redirected to the sign-in page shortly.</p>
    <p class="sr-only">Redirecting to sign-in in 3 seconds</p>
  </main>
</body>
</html>

