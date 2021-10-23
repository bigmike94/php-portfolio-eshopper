<?php
class Cart{
	private $pdo;
	function __construct(){
		$this->pdo = DB::getConnection();
	}
	public function getProducts($productsAssoc){
		$productsArray = array();
		$totalPrice = 0;
		if (isset($_COOKIE['products'])) {
			foreach($productsAssoc as $productId => $quantity){
				$product = $this->pdo->prepare("SELECT products.id, products.name, products.img, products.price, products.code, ${quantity} as quantity, $quantity*products.price as subtotal FROM `products` WHERE id=:id");
				$product->bindParam(':id', intval($productId), PDO::PARAM_INT);
				$product->execute();
				$product = $product->fetch(PDO::FETCH_ASSOC);
				$productsArray[] = $product;
			}
		}
		return $productsArray;
	}
	public function insertOrder($user_id, $order_time, $delivery_time, $products){
        //status: 0 - new, 1-approved and in process, 2 - finished
        $stmt = $this->pdo->prepare('INSERT INTO `orders` (user_id, order_time, delivery_time, products) VALUES (:user_id, :order_time, :delivery_time, :products)');
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':order_time', $order_time, PDO::PARAM_INT);
        $stmt->bindParam(':delivery_time', $delivery_time, PDO::PARAM_INT);
        $stmt->bindParam(':products', $products, PDO::PARAM_STR);
        $stmt->execute();
        return $this->pdo->lastInsertId();
    }
}
?>