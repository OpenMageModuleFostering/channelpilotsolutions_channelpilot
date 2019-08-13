<?php

/**
 * Holds information about the articles that should be updated. Each value can be null except of the id.
 * @author Channel Pilot Solutions GmbH <api@channelpilot.com>
 * @version 1.0
 */
class CPArticleUpdate {

	/**
	 * The id of the article that should be updated.
	 * @var type string
	 */
	public $id;
	
	/**
	 * Defines if the article is active for selling or should not be offerd online. Can be null.
	 * @var type boolean
	 */
	public $isActive;
	
	/**
	 * The gross- or selling price of this article.
	 * @var type number
	 */
	public $price;
	
	/**
	 * How many articles are in stock?
	 * @var type integer
	 */
	public $stock;
	
	/**
	 * The availability of the product as string. Customers will se this value online.
	 * @var type string
	 */
	public $availability;
	
	function __construct($id, $isActive, $price, $stock, $availability) {
		$this->id = $id;
		$this->isActive = $isActive;
		$this->price = $price;
		$this->stock = $stock;
		$this->availability = $availability;
	}
}

?>
