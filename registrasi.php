<?php
require "../db.php";

if(isset($_POST['register'])){
    $name = $_POST['name'];
    $email = $_POST['email'];
    $nim_nip = $_POST['nim_nip'];
    $password = $_POST['password'];

    // simpan langsung plaintext
    $sql = "INSERT INTO users (name,email,nim_nip,password,role)
            VALUES ('$name','$email','$nim_nip','$password','none')";
    mysqli_query($conn,$sql);

    echo "<script>alert('Registrasi berhasil! silakan login');location.href='login.php';</script>";
}

?>

<form method="POST">
    <h2>Registrasi</h2>
    <input type="text" name="name" placeholder="Nama" required><br>
    <input type="text" name="email" placeholder="Email" required><br>
    <input type="text" name="nim_nip" placeholder="NIM / NIP" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit" name="register">Daftar</button>
</form>