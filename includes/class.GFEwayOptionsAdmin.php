<?php

/**
* Options form input fields
*/
class GFEwayOptionsForm {

	public $customerID;
	public $useStored;
	public $useTest;
	public $useBeagle;
	public $roundTestAmounts;
	public $forceTestAccount;
	public $sslVerifyPeer;

	/**
	* initialise from form post, if posted
	*/
	public function __construct() {
		if (self::isFormPost()) {
			$this->customerID			= sanitize_text_field(self::getPostValue('customerID'));
			$this->useStored			= self::getPostValue('useStored');
			$this->useTest				= self::getPostValue('useTest');
			$this->useBeagle			= self::getPostValue('useBeagle');
			$this->roundTestAmounts		= self::getPostValue('roundTestAmounts');
			$this->forceTestAccount		= self::getPostValue('forceTestAccount');
			$this->sslVerifyPeer		= self::getPostValue('sslVerifyPeer');
		}
	}

	/**
	* Is this web request a form post?
	*
	* Checks to see whether the HTML input form was posted.
	*
	* @return boolean
	*/
	public static function isFormPost() {
		return ($_SERVER['REQUEST_METHOD'] == 'POST');
	}

	/**
	* Read a field from form post input.
	*
	* Guaranteed to return a string, trimmed of leading and trailing spaces, sloshes stripped out.
	*
	* @return string
	* @param string $fieldname name of the field in the form post
	*/
	public static function getPostValue($fieldname) {
		return isset($_POST[$fieldname]) ? wp_unslash(trim($_POST[$fieldname])) : '';
	}

	/**
	* Validate the form input, and return error messages.
	*
	* Return a string detailing error messages for validation errors discovered,
	* or an empty string if no errors found.
	* The string should be HTML-clean, ready for putting inside a paragraph tag.
	*
	* @return string
	*/
	public function validate() {
		$errmsg = '';

		if (strlen($this->customerID) === 0)
			$errmsg .= "# Please enter the eWAY account number.<br/>\n";

		return $errmsg;
	}

}

/**
* Options admin
*/
class GFEwayOptionsAdmin {

	private $plugin;							// handle to the plugin object
	private $menuPage;							// slug for admin menu page
	private $scriptURL = '';
	private $frm;								// handle for the form validator

	/**
	* @param GFEwayPlugin $plugin handle to the plugin object
	* @param string $menuPage URL slug for this admin menu page
	*/
	public function __construct($plugin, $menuPage, $scriptURL) {
		$this->plugin		= $plugin;
		$this->menuPage		= $menuPage;
		$this->scriptURL	= $scriptURL;

		wp_enqueue_script('jquery');
	}

	/**
	* process the admin request
	*/
	public function process() {
		$this->frm = new GFEwayOptionsForm();
		if ($this->frm->isFormPost()) {
			check_admin_referer('save', $this->menuPage . '_wpnonce');

			$errmsg = $this->frm->validate();
			if (empty($errmsg)) {
				$this->plugin->options['customerID']		= $this->frm->customerID;
				$this->plugin->options['useStored']			= ($this->frm->useStored == 'Y');
				$this->plugin->options['useTest']			= ($this->frm->useTest == 'Y');
				$this->plugin->options['useBeagle']			= ($this->frm->useBeagle == 'Y');
				$this->plugin->options['roundTestAmounts']	= ($this->frm->roundTestAmounts == 'Y');
				$this->plugin->options['forceTestAccount']	= ($this->frm->forceTestAccount == 'Y');
				$this->plugin->options['sslVerifyPeer']		= ($this->frm->sslVerifyPeer == 'Y');

				update_option(GFEWAY_PLUGIN_OPTIONS, $this->plugin->options);
				$this->saveErrorMessages();
				$msg = __('Options saved.');
				echo "<div class='updated fade'><p><strong>$msg</strong></p></div>\n";
			}
			else {
				echo "<div class='error'><p><strong>$errmsg</strong></p></div>\n";
			}
		}
		else {
			// initialise form from stored options
			$this->frm->customerID			= $this->plugin->options['customerID'];
			$this->frm->useStored			= $this->plugin->options['useStored'] ? 'Y' : 'N';
			$this->frm->useTest				= $this->plugin->options['useTest'] ? 'Y' : 'N';
			$this->frm->useBeagle			= $this->plugin->options['useBeagle'] ? 'Y' : 'N';
			$this->frm->roundTestAmounts	= $this->plugin->options['roundTestAmounts'] ? 'Y' : 'N';
			$this->frm->forceTestAccount	= $this->plugin->options['forceTestAccount'] ? 'Y' : 'N';
			$this->frm->sslVerifyPeer		= $this->plugin->options['sslVerifyPeer'] ? 'Y' : 'N';
		}

		require GFEWAY_PLUGIN_ROOT . 'views/admin-settings.php';
	}

	/**
	* save error messages
	*/
	private function saveErrorMessages() {
		$errNames = array (
			GFEWAY_ERROR_ALREADY_SUBMITTED,
			GFEWAY_ERROR_NO_AMOUNT,
			GFEWAY_ERROR_REQ_CARD_HOLDER,
			GFEWAY_ERROR_REQ_CARD_NAME,
			GFEWAY_ERROR_EWAY_FAIL,
		);
		foreach ($errNames as $errName) {
			$msg = $this->frm->getPostValue($errName);
			delete_option($errName);
			if (!empty($msg)) {
				add_option($errName, $msg, '', 'no');
			}
		}
	}

}
