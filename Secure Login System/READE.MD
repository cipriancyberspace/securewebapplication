1.Secure Login System

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

![Sqlmap](https://raw.githubusercontent.com/cipriancyberspace/securewebapplication/50a9b50e8ed06c048d4327917a5f41702d507b5f/Secure%20Login%20System/sqlmap.png)

Sqlmap is an open source penetration testing tool that automates the process of detecting and exploiting SQL injection flaws and taking over of database servers. 
It comes with a powerful detection engine, many niche features for the ultimate penetration tester and a broad range of switches lasting from database fingerprinting, 
over data fetching from the database, to accessing the underlying file system and executing commands on the operating system via out-of-band connections.

![Sqlmap](https://github.com/cipriancyberspace/securewebapplication/blob/ad4ab30ec684ebaaf70cecabc7cb48c7569ec70b/Secure%20Login%20System/images/sqlmap_1.png)

sqlmap -u "http://localhost/secureloginsystem/vulnerable_login_form.php" --data="username=test&password=test" --dbs --batch

<pre> <code> ```sqlmap -u "http://localhost/secureloginsystem/vulnerable_login_form.php" --data="username=test&password=test" --dbs --batch ``` </code> </pre>
                                                                                           
