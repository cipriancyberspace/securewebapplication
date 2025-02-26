<?php
require_once 'connection.php';

session_start();

if(isset($_SESSION['user'])){
    header("location: welcome.php");
    exit();
}

$errorMsg = [];

if(isset($_POST['register_btn'])){

    require_once 'connection.php';

    if (!$db) {
        die("Database connection failed!");
    }

    // Sanitize inputs
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = strip_tags($_POST['password']);

    // Validation checks
    if(empty($name)){
        $errorMsg[0][] = 'Name is required';
    }

    if(empty($email)){
        $errorMsg[1][] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg[1][] = 'Invalid email format';
    }

    if(empty($password)){
        $errorMsg[2][] = 'Password is required';
    } elseif(strlen($password) < 6) {
        $errorMsg[2][] = 'Password must be at least 6 characters';
    }

    if(empty($errorMsg)){
        try {
            // Check if email already exists
            $select_stmt = $db->prepare("SELECT email FROM users WHERE email = :email");
            $select_stmt->execute([':email' => $email]);
            $row = $select_stmt->fetch(PDO::FETCH_ASSOC);

            if($row && $row['email'] === $email){
                $errorMsg[1][] = 'Email address already exists';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user
                $insert_stmt = $db->prepare("INSERT INTO users (name, email, password, created) 
                             VALUES (:name, :email, :password, NOW())");
				$insert_stmt->execute([
    				':name' => $name,
    				':email' => $email,
   					 ':password' => $hashed_password
				]);

                var_dump($insert_stmt->errorInfo()); // Debugging error info

                if($insert_stmt) {
                    header("location: index.php?registered=success");
                    exit();
                }
            }
        }
        catch(PDOException $e) {
            die("Database error: " . $e->getMessage());
        }
    }
}
?>


<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEAg3QhqLMpG8r+8fhAXLRk2vvoC2f3B09zVXn8CA5QIVfZOJ3BCsw2P0p/We" crossorigin="anonymous">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-U1DAWAznBHeqEIlVSCgzq+c9gqGAJn5c/t99JyeKa9xxaYpSvHU5awsuZVVFIhvj" crossorigin="anonymous"></script>
	<title>Register</title>
</head>

<body>
	<div class="container">
		
		<form action="register.php" method="post">
			<div class="mb-3">

			<?php
   				 if(isset($errorMsg[0]) && !empty($errorMsg[0])){
  				      foreach($errorMsg[0] as $nameErrors){
       				     echo "<p class='small text-danger'>".$nameErrors."</p>";
      			  }
    			}
			?>




				<label for="name" class="form-label">Name</label>
				<input type="text" name="name" class="form-control" placeholder="Jane Doe">
			</div>
			<div class="mb-3">

			<?php
   				 if(isset($errorMsg[1]) && !empty($errorMsg[1])){
  				      foreach($errorMsg[1] as $emailErrors){
       				     echo "<p class='small text-danger'>".$emailErrors."</p>";
      			  }
    			}
			?>



				<label for="email" class="form-label">Email address</label>
				<input type="email" name="email" class="form-control" placeholder="jane@doe.com">

			</div>
			<div class="mb-3">


			<?php
   				 if(isset($errorMsg[2]) && !empty($errorMsg[2])){
  				      foreach($errorMsg[2] as $passwordErrors){
       				     echo "<p class='small text-danger'>".$passwordErrors."</p>";
      			  }
    			}
			?>
				<label for="password" class="form-label">Password</label>
				<input type="password" name="password" class="form-control" placeholder="">
				
			</div>
			<button type="submit" name="register_btn" class="btn btn-primary">Register Account</button>
		</form>
		Already Have an Account? <a class="register" href="index.php">Login Instead</a>
	</div>
</body>

</html>