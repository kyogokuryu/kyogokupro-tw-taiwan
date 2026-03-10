<?php
require_once ('com/gmo_pg/client/output/BaseOutput.php');
/**
 * <b>Amazon Pay住所情報参照　出力パラメータクラス</b>
 *
 * @package com.gmo_pg.client
 * @subpackage output
 * @see outputPackageInfo.php
 * @author GMO PaymentGateway
 */
class SearchAddressAmazonpayOutput extends BaseOutput {

	/**
	 * @var string 配送先国コード
	 */
	private $shippingCountryCode;
	/**
	 * @var string 配送先郵便番号
	 */
	private $shippingPostalCode;
	/**
	 * @var string 配送先都道府県
	 */
	private $shippingStateOrRegion;
	/**
	 * @var string 配送先住所1
	 */
	private $shippingAddressLine1;
	/**
	 * @var string 配送先住所2
	 */
	private $shippingAddressLine2;
	/**
	 * @var string 配送先住所3
	 */
	private $shippingAddressLine3;
	/**
	 * @var string 配送先氏名
	 */
	private $shippingName;
	/**
	 * @var string 配送先電話番号
	 */
	private $shippingPhoneNumber;
	/**
	 * @var string Amazonアカウント名
	 */
	private $amazonAccountName;
	/**
	 * @var string Amazonアカウントメールアドレス
	 */
	private $amazonMailAddress;
	/**
	 * @var string Amazonアカウント電話番号
	 */
	private $amazonPhoneNumber;


	/**
	 * コンストラクタ
	 *
	 * @param IgnoreCaseMap $params  出力パラメータ
	 */
	public function __construct($params = null) {
		parent::__construct($params);

		// 引数が無い場合は戻る
		if (is_null($params)) {
            return;
        }

        // マップの展開
		$this->setShippingCountryCode($params->get('ShippingCountryCode'));
		$this->setShippingPostalCode($params->get('ShippingPostalCode'));
		$this->setShippingStateOrRegion($params->get('ShippingStateOrRegion'));
		$this->setShippingAddressLine1($params->get('ShippingAddressLine1'));
		$this->setShippingAddressLine2($params->get('ShippingAddressLine2'));
		$this->setShippingAddressLine3($params->get('ShippingAddressLine3'));
		$this->setShippingName($params->get('ShippingName'));
		$this->setShippingPhoneNumber($params->get('ShippingPhoneNumber'));
		$this->setAmazonAccountName($params->get('AmazonAccountName'));
		$this->setAmazonMailAddress($params->get('AmazonMailAddress'));
		$this->setAmazonPhoneNumber($params->get('AmazonPhoneNumber'));

	}

	/**
	 * 配送先国コード取得
	 * @return string 配送先国コード
	 */
	public function getShippingCountryCode() {
		return $this->shippingCountryCode;
	}
	/**
	 * 配送先郵便番号取得
	 * @return string 配送先郵便番号
	 */
	public function getShippingPostalCode() {
		return $this->shippingPostalCode;
	}
	/**
	 * 配送先都道府県取得
	 * @return string 配送先都道府県
	 */
	public function getShippingStateOrRegion() {
		return $this->shippingStateOrRegion;
	}
	/**
	 * 配送先住所1取得
	 * @return string 配送先住所1
	 */
	public function getShippingAddressLine1() {
		return $this->shippingAddressLine1;
	}
	/**
	 * 配送先住所2取得
	 * @return string 配送先住所2
	 */
	public function getShippingAddressLine2() {
		return $this->shippingAddressLine2;
	}
	/**
	 * 配送先住所3取得
	 * @return string 配送先住所3
	 */
	public function getShippingAddressLine3() {
		return $this->shippingAddressLine3;
	}
	/**
	 * 配送先氏名取得
	 * @return string 配送先氏名
	 */
	public function getShippingName() {
		return $this->shippingName;
	}
	/**
	 * 配送先電話番号取得
	 * @return string 配送先電話番号
	 */
	public function getShippingPhoneNumber() {
		return $this->shippingPhoneNumber;
	}
	/**
	 * Amazonアカウント名取得
	 * @return string Amazonアカウント名
	 */
	public function getAmazonAccountName() {
		return $this->amazonAccountName;
	}
	/**
	 * Amazonアカウントメールアドレス取得
	 * @return string Amazonアカウントメールアドレス
	 */
	public function getAmazonMailAddress() {
		return $this->amazonMailAddress;
	}
	/**
	 * Amazonアカウント電話番号取得
	 * @return string Amazonアカウント電話番号
	 */
	public function getAmazonPhoneNumber() {
		return $this->amazonPhoneNumber;
	}

	/**
	 * 配送先国コード設定
	 *
	 * @param string $shippingCountryCode
	 */
	public function setShippingCountryCode($shippingCountryCode) {
		$this->shippingCountryCode = $shippingCountryCode;
	}
	/**
	 * 配送先郵便番号設定
	 *
	 * @param string $shippingPostalCode
	 */
	public function setShippingPostalCode($shippingPostalCode) {
		$this->shippingPostalCode = $shippingPostalCode;
	}
	/**
	 * 配送先都道府県設定
	 *
	 * @param string $shippingStateOrRegion
	 */
	public function setShippingStateOrRegion($shippingStateOrRegion) {
		$this->shippingStateOrRegion = $shippingStateOrRegion;
	}
	/**
	 * 配送先住所1設定
	 *
	 * @param string $shippingAddressLine1
	 */
	public function setShippingAddressLine1($shippingAddressLine1) {
		$this->shippingAddressLine1 = $shippingAddressLine1;
	}
	/**
	 * 配送先住所2設定
	 *
	 * @param string $shippingAddressLine2
	 */
	public function setShippingAddressLine2($shippingAddressLine2) {
		$this->shippingAddressLine2 = $shippingAddressLine2;
	}
	/**
	 * 配送先住所3設定
	 *
	 * @param string $shippingAddressLine3
	 */
	public function setShippingAddressLine3($shippingAddressLine3) {
		$this->shippingAddressLine3 = $shippingAddressLine3;
	}
	/**
	 * 配送先氏名設定
	 *
	 * @param string $shippingName
	 */
	public function setShippingName($shippingName) {
		$this->shippingName = $shippingName;
	}
	/**
	 * 配送先電話番号設定
	 *
	 * @param string $shippingPhoneNumber
	 */
	public function setShippingPhoneNumber($shippingPhoneNumber) {
		$this->shippingPhoneNumber = $shippingPhoneNumber;
	}
	/**
	 * Amazonアカウント名設定
	 *
	 * @param string $amazonAccountName
	 */
	public function setAmazonAccountName($amazonAccountName) {
		$this->amazonAccountName = $amazonAccountName;
	}
	/**
	 * Amazonアカウントメールアドレス設定
	 *
	 * @param string $amazonMailAddress
	 */
	public function setAmazonMailAddress($amazonMailAddress) {
		$this->amazonMailAddress = $amazonMailAddress;
	}
	/**
	 * Amazonアカウント電話番号設定
	 *
	 * @param string $amazonPhoneNumber
	 */
	public function setAmazonPhoneNumber($amazonPhoneNumber) {
		$this->amazonPhoneNumber = $amazonPhoneNumber;
	}

	/**
	 * 文字列表現
	 * <p>
	 *  現在の各パラメータを、パラメータ名=値&パラメータ名=値の形式で取得します。
	 * </p>
	 * @return string 出力パラメータの文字列表現
	 */
	public function toString() {
		$str ='';
		$str .= 'ShippingCountryCode=' . $this->encodeStr($this->getShippingCountryCode());
		$str .='&';
		$str .= 'ShippingPostalCode=' . $this->encodeStr($this->getShippingPostalCode());
		$str .='&';
		$str .= 'ShippingStateOrRegion=' . $this->encodeStr($this->getShippingStateOrRegion());
		$str .='&';
		$str .= 'ShippingAddressLine1=' . $this->encodeStr($this->getShippingAddressLine1());
		$str .='&';
		$str .= 'ShippingAddressLine2=' . $this->encodeStr($this->getShippingAddressLine2());
		$str .='&';
		$str .= 'ShippingAddressLine3=' . $this->encodeStr($this->getShippingAddressLine3());
		$str .='&';
		$str .= 'ShippingName=' . $this->encodeStr($this->getShippingName());
		$str .='&';
		$str .= 'ShippingPhoneNumber=' . $this->encodeStr($this->getShippingPhoneNumber());
		$str .='&';
		$str .= 'AmazonAccountName=' . $this->encodeStr($this->getAmazonAccountName());
		$str .='&';
		$str .= 'AmazonMailAddress=' . $this->encodeStr($this->getAmazonMailAddress());
		$str .='&';
		$str .= 'AmazonPhoneNumber=' . $this->encodeStr($this->getAmazonPhoneNumber());


	    if ($this->isErrorOccurred()) {
            // エラー文字列を連結して返す
            $errString = parent::toString();
            $str .= '&' . $errString;
        }

        return $str;
	}

}
?>
