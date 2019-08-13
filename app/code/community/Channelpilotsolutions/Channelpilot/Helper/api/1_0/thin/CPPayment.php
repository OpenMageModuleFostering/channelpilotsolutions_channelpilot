<?php

class CPPayment {
    /**
     * id of the payment type
     * @var string
     */
    public $typeId;

    /**
     * title of the payment title
     * @var string
     */
    public $typeTitle;

    /**
     * costs for payment
     * @var string
     */
    public $costs;

    /**
     * When was the order payed? Timestamp formatted in ISO 8601 (e.g. "2009-06-30T18:30:00+02:00")
     * @var string
     */
    public $paymentTime;

    /**
     *
     * @param string $paymentTime
     */
    function __construct($paymentTime) {
        $this->paymentTime = $paymentTime;
    }
}

?>
