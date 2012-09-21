<?php

/**
* Options form input fields
*/
class GFEwayOptionsForm {

	public $customerID;
	public $useTest;
	public $roundTestAmounts;
	public $forceTestAccount;
	public $sslVerifyPeer;

	/**
	* initialise from form post, if posted
	*/
	public function __construct() {
		if ($this->isFormPost()) {
			$this->customerID = isset($_POST['customerID']) ? stripslashes(trim($_POST['customerID'])) : '';
			$this->useTest = isset($_POST['useTest']) ? stripslashes(trim($_POST['useTest'])) : '';
			$this->roundTestAmounts = isset($_POST['roundTestAmounts']) ? stripslashes(trim($_POST['roundTestAmounts'])) : '';
			$this->forceTestAccount = isset($_POST['forceTestAccount']) ? stripslashes(trim($_POST['forceTestAccount'])) : '';
			$this->sslVerifyPeer = isset($_POST['sslVerifyPeer']) ? stripslashes(trim($_POST['sslVerifyPeer'])) : '';
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
	private $nextTabIndex = 0;					// next HTML form element tabindex

	/**
	* @param GFEwayPlugin $plugin handle to the plugin object
	* @param string $menuPage URL slug for this admin menu page
	*/
	public function __construct($plugin, $menuPage) {
		$this->plugin = $plugin;
		$this->menuPage = $menuPage;
		$this->scriptURL = parse_url($_SERVER['SCRIPT_NAME'], PHP_URL_PATH) . "?page={$menuPage}";
	}

	/**
	* process the admin request
	*/
	public function process() {

		echo "<div class='wrap'>\n";
		screen_icon();
		echo "<h2>Gravity Forms eWAY Payments</h2>\n";

		$this->frm = new GFEwayOptionsForm();
		if ($this->frm->isFormPost()) {
			$errmsg = $this->frm->validate();
			if (empty($errmsg)) {
				$this->plugin->options['customerID'] = $this->frm->customerID;
				$this->plugin->options['useTest'] = ($this->frm->useTest == 'Y');
				$this->plugin->options['roundTestAmounts'] = ($this->frm->roundTestAmounts == 'Y');
				$this->plugin->options['forceTestAccount'] = ($this->frm->forceTestAccount == 'Y');
				$this->plugin->options['sslVerifyPeer'] = ($this->frm->sslVerifyPeer == 'Y');

				update_option(GFEWAY_PLUGIN_OPTIONS, $this->plugin->options);
				$this->plugin->showMessage(__('Options saved.'));
			}
			else {
				$this->plugin->showError($errmsg);
			}
		}
		else {
			// initialise form from stored options
			$this->frm->customerID = $this->plugin->options['customerID'];
			$this->frm->useTest = $this->plugin->options['useTest'] ? 'Y' : 'N';
			$this->frm->roundTestAmounts = $this->plugin->options['roundTestAmounts'] ? 'Y' : 'N';
			$this->frm->forceTestAccount = $this->plugin->options['forceTestAccount'] ? 'Y' : 'N';
			$this->frm->sslVerifyPeer = $this->plugin->options['sslVerifyPeer'] ? 'Y' : 'N';
		}

		echo <<<HTML
<form action="{$this->scriptURL}" method="post">
	<table class="form-table">

		<tr>
			<th>eWAY Customer ID</th>
			<td>
				<input type='text' class="regular-text" name='customerID' value="{$this->fieldValueHTML('customerID')}" />
			</td>
		</tr>

		<tr valign='top'>
			<th>Use Sandbox (testing enviroment)</th>
			<td>
				<label><input type="radio" name="useTest" value="Y" {$this->fieldValueChecked('useTest', 'Y')} />&nbsp;yes</label>
				&nbsp;&nbsp;<label><input type="radio" name="useTest" value="N" {$this->fieldValueChecked('useTest', 'N')} />&nbsp;no</label>
			</td>
		</tr>

		<tr valign='top'>
			<th>Round Amounts for Sandbox</th>
			<td>
				<label><input type="radio" name="roundTestAmounts" value="Y" {$this->fieldValueChecked('roundTestAmounts', 'Y')} />&nbsp;yes</label>
				&nbsp;&nbsp;<label><input type="radio" name="roundTestAmounts" value="N" {$this->fieldValueChecked('roundTestAmounts', 'N')} />&nbsp;no</label>
			</td>
		</tr>

		<tr valign='top'>
			<th>Force Test Customer ID in Sandbox</th>
			<td>
				<label><input type="radio" name="forceTestAccount" value="Y" {$this->fieldValueChecked('forceTestAccount', 'Y')} />&nbsp;yes</label>
				&nbsp;&nbsp;<label><input type="radio" name="forceTestAccount" value="N" {$this->fieldValueChecked('forceTestAccount', 'N')} />&nbsp;no</label>
			</td>
		</tr>

		<tr valign='top'>
			<th>Verify remote SSL certificate<br />
				(<i>only disable if your website can't be correctly configured!</i>)</th>
			<td>
				<label><input type="radio" name="sslVerifyPeer" value="Y" {$this->fieldValueChecked('sslVerifyPeer', 'Y')} />&nbsp;yes</label>
				&nbsp;&nbsp;<label><input type="radio" name="sslVerifyPeer" value="N" {$this->fieldValueChecked('sslVerifyPeer', 'N')} />&nbsp;no</label>
			</td>
		</tr>

	</table>
	<p class="submit">
	<input type="submit" name="Submit" class="button-primary" value="Save Changes" />
	<input type="hidden" name="action" value="save" />

HTML;
	wp_nonce_field($this->menuPage);
	echo <<<HTML
	</p>
</form>

</div>

HTML;
	}

	/**
	* get value of form field, formatted for HTML display
	*
	* @param string $fieldName name of input field in form
	* @return string HTML-legal text for value in field
	*/
	private function fieldValueHTML($fieldName) {
		return htmlspecialchars($this->frm->$fieldName);
	}

	/**
	* compare field value to specified value, return HTML attribute for checked radio/checkbox if matches
	*
	* @param string $fieldName name of input field in form
	* @param string $value value to check for match against
	* @return string HTML attribute to add to radio / checkbox input
	*/
	protected function fieldValueChecked($fieldName, $value) {
		return ($this->frm->$fieldName === $value) ? ' checked="checked"' : '';
	}
}
