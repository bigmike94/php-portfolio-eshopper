<?php
class User{
    private $pdo;
    function __construct(){
        $this->pdo = DB::getConnection();
    }
    public function register($name, $email, $phone, $address, $password){
        $stmt = $this->pdo->prepare('INSERT INTO `users` (name, email, phone, address, password) VALUES (:name, :email, :phone, :address, :password)');
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
        $stmt->bindParam(':address', $address, PDO::PARAM_STR);
        $stmt->bindParam(':password', $password, PDO::PARAM_STR);
        $stmt->execute();
        return $this->pdo->lastInsertId();
    }
    public function edit($id, $updates){
        $query_string="";
        $query_values_arr=array();
        foreach($updates as $update_name => $update_value){
            $query_string.="{$update_name}=:{$update_name},";
            if ($update_name==="password") $query_values_arr[":{$update_name}"] = md5($update_value);
            else $query_values_arr[":{$update_name}"] = $update_value;
        }
        $query_values_arr[":id"] = intval($id);
        $query_string = substr($query_string, 0, strlen($query_string)-1);
        $stmt = $this->pdo->prepare("UPDATE `users` SET {$query_string} WHERE id = :id");
        $stmt->execute($query_values_arr);
        if($stmt->rowCount()>0) return true;
    }
    public function checkUserData($param, $password){
        $hashed_password = md5($password);
        $stmt = $this->pdo->prepare("SELECT * FROM `users` WHERE (email=:param OR id=:param) AND password=:password");
        $stmt->bindParam(':param', $param, PDO::PARAM_STR);
        $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($userData) return $userData;
        return false;
    }
    public function auth($lang, $user_id, $user_name){
        session_start();
        $_SESSION['user']['id'] = $user_id;
        $_SESSION['user']['name'] = explode(" ", $user_name)[0];
        $_SESSION['user']['lang'] = $lang;
    }
    public function checkLogged(){
        session_start();
        if (isset($_SESSION['user'])) return true;
        return false;
    }
    public function checkName($name){
        $pattern = "/^([a-zA-Z]{2,})\s([a-zA-Z]{2,})$/";
        if(preg_match($pattern, $name)) return true;
        return false;
    }
    public function checkPhone($phone){
        $pattern = "/^(5\d{2})-(\d{2})-(\d{2})-(\d{2})$/";
        if(preg_match($pattern, $phone)) return true;
        return false;
    }
    public function checkAddress($address){
        $pattern = "/^[a-zA-z]{3,}(\s)?,(\s)?[a-zA-z]{3,}\s#(\d){1,}(\s)?(\w)?$/";
        if(preg_match($pattern, $address)) return true;
        return false;
    }
    public function checkPassword($password){
        $pattern = "/^(?=.*\d)(?=.*[A-Z]).{8,}$/";
        if(preg_match($pattern, $password)) return true;
        return false;
    }
    public function checkEmail($email){
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) return true;
        return false;
    }
    public function checkDataExist($email, $phone){     
        $stmt = $this->pdo->prepare("SELECT COUNT(id) FROM `users` WHERE email=:email OR phone=:phone");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':phone', $email, PDO::PARAM_STR);
        $stmt->execute();
        if ($stmt->fetchColumn()>0) return true;
        return false;
    }
    public function getUserById($id){
        $stmt = $this->pdo->prepare("SELECT * FROM `users` WHERE id=:id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public static function getRecommendedList($id){
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare("SELECT rec.product_id, rec.user_id, prod.* FROM `recommended` rec INNER JOIN `products` prod ON rec.product_id = prod.id WHERE rec.user_id=:user_id ORDER BY RAND() LIMIT 3");
        $stmt->bindParam(':user_id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $userData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($userData) return $userData;
    }
    public static function addToRecommended($user_id, $product_id){
        $pdo = DB::getConnection();
        $stmt1 = $pdo->prepare('SELECT id FROM `recommended` WHERE product_id=:product_id');
        $stmt1->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt1->execute();
        $userData = $stmt1->fetchAll(PDO::FETCH_ASSOC);
        if (count($userData)===0) {
            $stmt2 = $pdo->prepare("SELECT id, brand FROM `products` WHERE id = :product_id");
            $stmt2->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt2->execute();
            $product = $stmt2->fetch(PDO::FETCH_ASSOC);
            $product_brand = $product['brand'];
            $brand_related = $pdo->query("SELECT id FROM `products` WHERE brand = '{$product_brand}' AND id!={$product['id']}")->fetchAll(PDO::FETCH_ASSOC);
            $brand_related_products_ids = array();
            foreach ($brand_related as $brand_related_data) {
               $brand_related_products_ids[] = $brand_related_data['id'];
            }
            foreach ($brand_related_products_ids as $id) {
                $pdo->query("INSERT INTO `recommended` (user_id, product_id) VALUES ({$user_id}, {$id})");
            }
        }
    }
}