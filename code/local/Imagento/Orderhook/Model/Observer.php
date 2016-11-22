<?php
/**
 * imdeveloper 2016
 * imdeveloper@yandex.ru
 * Class Imagento_Orderhook_Model_Observer
 */
class Imagento_Orderhook_Model_Observer
{
    const STATE_NEW = 'new';
    const STATE_PENDING_PAYMENT = 'pending_payment';
    const STATE_PROCESSING = 'processing';
    const STATE_COMPLETE = 'complete';
    const STATE_CLOSED = 'closed';
    const STATE_CANCELED = 'canceled';
    const STATE_HOLDED = 'holded';
    const STATE_PAYMENT_REVIEW = 'payment_review';

    /**
     * @param $order
     * @return string
     */
    public function checkStatus($order)
    {

        // input order Object
        switch ($order->getState()) {
            case self::STATE_NEW:
                $statusNew = 'INITIALIZED';
                break;
            case self::STATE_PENDING_PAYMENT:
                $statusNew = 'IN_PROGRESS';
                break;
            case self::STATE_PROCESSING:
                $statusNew = 'IN_PROGRESS';
                break;
            case self::STATE_COMPLETE:
                $statusNew = 'DELIVERED';
                break;
            case self::STATE_CLOSED:
                $statusNew = 'CANCELLED';
                break;
            case self::STATE_CANCELED:
                $statusNew = 'CANCELLED';
                break;
            case self::STATE_HOLDED:
                $statusNew = 'IN_PROGRESS';
                break;
            case self::STATE_PAYMENT_REVIEW:
                $statusNew = 'IN_PROGRESS';
                break;
            default:
                $statusNew = 'IN_PROGRESS';
                break;
        }

        return $statusNew;

    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function implementOrderStatus(Varien_Event_Observer $observer)
    {
        $order = $observer->getOrder();

        $statusNew = $this->checkStatus($order); //check status order

        $items = $order->getAllVisibleItems();

        $products = array();

        $qtyProducts = -1;
        foreach ($items as $product) {
            $qtyProducts++;
            $productModel = Mage::getModel('catalog/product');
            $productModel = $productModel->load($product->getId());
            $description = $productModel->getShortDescription();
            $name = $product->getName();
            $price = $product->getPrice();
            $productCat = Mage::getModel('catalog/product')->load($product->getId());  // load the product object from the product id
            $categoryIds = $productCat->getCategoryIds(); // get the list of categories which the product belongs to.
            foreach ($categoryIds as $i): // iterate through list of categories
                $category = Mage::getModel('catalog/category')->load($i);  // get the id of the category from the category model
                $cat = $productCategories[] = $category->getName();  // get the name of the category and put it into an array for use later
            endforeach;
            $qty = round($product->getQtyOrdered());
            $id = $product->getId();
            $url = Mage::helper('catalog/product')->getProductUrl($id);
            //var_dump($product->getData());
            $products[$qtyProducts] = array(
                'name' => $name,
                'cost' => $price,
                'category' => $cat,
                'quantity' => $qty,
                'externalItemId' => $id,
                'url' => $url,
                'description' => $description,
            );

        }

        $orderStatus = $statusNew;
        $dateCreated = $order->getCreatedAt(); //date
        $orderId = $order->getId(); //order id
        $customerId = ($order->getCustomerId()) ? $order->getCustomerId() : $order->getCustomerEmail(); // customer id or email
        $total = $order->getGrandTotal(); // total price


        //необязательные


        $customerEmail = $order->getCustomerEmail(); //email customer
        $customerFirstName = $order->getCustomerFirstname(); //customer firstname
        $customerLastName = $order->getCustomerLastname();  // customer lastname
        $deliveryMethod = $order->getShippingMethod(); // shipping method
        $deliveryAddress = $order->getShippingAddressId(); // adress id
        $paymentMethod = 'none';
        //var_dump($order->getData());

        $user = 'gaydarzhy@yandex.ua';
        $password = 'Sputnik2000';
        $add_orders_url = 'https://esputnik.com/api/v1/orders';

        $order = new stdClass();

        // ОБЯЗАТЕЛЬНЫЕ ПОЛЯ

        $order->status = $orderStatus; // Статус заказа. Возможные значения: INITIALIZED, IN_PROGRESS, CANCELLED, DELIVERED, ABANDONED_SHOPPING_CART. Для RFM анализа учитываются только заказы со статусом DELIVERED.
        $order->date = "2014-09-22T18:53:40";  // Дата заказа в формате yyyy-MM-ddTHH:mm:ss.
        $order->externalOrderId = $orderId;  // Идентификатор заказа в Вашей системе.
        $order->externalCustomerId = $customerId;  // Идентификатор клиента в Вашей системе. Если вы ходите идентифицировать клиентов
        $order->totalCost = $total;  // Итоговая сумма по заказу.

        // НЕОБЯЗАТЕЛЬНЫЕ ПОЛЯ

        $order->email = $customerEmail;  // Email клиента.
        $order->phone = "380501112233";  // Номер телефона клиента.
        $order->firstName = $customerFirstName; // Имя клиента.
        $order->lastName = $customerLastName; // Фамилия клиента.
        $order->storeId = "";  // Для ситуации, если Вам нужно хранить несколько наборов данных (по разным магазинам) в одной учетной записи eSputnik, иначе можно оставить пустым.
        $order->shipping = 1;  // Стоимость доставки (дополнительная информация, при расчётах не учитывается).
        $order->deliveryMethod = $deliveryMethod; // Способ доставки заказа.
        $order->deliveryAddress = $deliveryAddress; // Адрес доставки заказа.
        $order->taxes = 0;  // Налоги (дополнительная информация, при расчётах не учитывается).
        $order->paymentMethod = $paymentMethod; // Способ оплаты заказа.
        $order->discount = 10;  // Скидка (дополнительная информация, при расчётах не учитывается).
        $order->restoreUrl = "";  // Cсылка на восстановление корзины, если необходима такая функциональность.
        $order->statusDescription = "";  // Дополнительное описание статуса заказа.

        $order->items = $products;

        $orders_list = new stdClass();
        $orders_list->orders = array($order);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orders_list));
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_URL, $add_orders_url);
        curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $password);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);


    }

}