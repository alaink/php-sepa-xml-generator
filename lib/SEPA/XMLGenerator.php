<?php

/**
 * Created by Dumitru Russu. e-mail: dmitri.russu@gmail.com
 * Date: 7/8/13
 * Time: 8:46 PM
 * Sepa Xml Generator
 */
namespace SEPA;

require_once 'ValidationRules.php';
require_once 'error_messages.php';
require_once dirname(__FILE__) . '/../unicode_decode/code/Unidecode.php';
require_once dirname(__FILE__) . '/../iban/php-iban.php';

/**
 * Interface XML Generator
 * Class XMLGeneratorInterface
 * @package SEPA
 */
interface XMLGeneratorInterface {
	public function __construct();
	public function addXmlMessage( Message $message );
	public function getGeneratedXml();
	public function save( $fileName );
	public function view();
	public function __destruct();
}
/**
 * Class XMLGenerator
 * @package SEPA
 */
class XMLGenerator extends  ValidationRules implements XMLGeneratorInterface {

	/**
	 * Path Logs Directory
	 * @var string
	 */
	public static $_PATH_LOGS_DIRECTORY = '/logs/';

	/**
	 * LOG File name
	 * @var string
	 */
	public static $_LOG_FILENAME = 'sepa_logs.txt';

	/**
	 *
	 */
	const PAIN_008_001_02 = '<?xml version="1.0" encoding="UTF-8"?>
						<Document
							xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02"
							xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
							xsi:schemaLocation="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02 pain.008.001.02.xsd">
						</Document>';

    const PAIN_001_001_02 = '<?xml version="1.0" encoding="UTF-8"?>
						<Document
							xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.02"
							xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
							xsi:schemaLocation="urn:iso:std:iso:20022:tech:xsd:pain.001.001.02 pain.001.001.02.xsd">
						</Document>';
	/**
	 * @var array
	 */
	private $sepaMessageObjects = array();

	/**
	 * @var \SimpleXMLElement
	 */
	private $xml;

	public function __construct() {
        $this->setDocument(self::PAIN_008_001_02);
	}

    public function setDocument($document) {
        $this->xml = new \SimpleXMLElement($document);
        return $this;
    }

	/**
	 * Add Xml Messages
	 * @param Message $messageObject
	 * @return $this
	 */
	public function addXmlMessage(Message $messageObject) {

		$this->sepaMessageObjects[] = $messageObject;
		return $this;
	}

	/**
	 * Save Xml File
	 * @param null $fileName
	 * @return bool|mixed
	 */
	public function save($fileName = null) {
		//save to file
		if ( $fileName ) {

			$dom = new \DOMDocument('1.0');
			$dom->preserveWhiteSpace = false;
			$dom->formatOutput = true;

			if ( !$this->xml->children() ) {

				$this->generateMessages();
				$dom->loadXML($this->xml->asXML());
			}
			else {

				$dom->loadXML($this->getGeneratedXml());
			}

			return ($dom->save($fileName) ? true : false);
		}

		return $this;
	}

	/**
	 * Generate Messages
	 */
	private function generateMessages() {
		/** @var $message Message */
		foreach ($this->sepaMessageObjects as $message ) {
			try {

				$this->simpleXmlAppend($this->xml, $message->getSimpleXMLElementMessage());

			} catch(\Exception $e) {

				$this->writeLog($e->getMessage());

			}
		}
	}

	public function writeLog($message) {
		$f = fopen(realpath(__DIR__) . static::$_PATH_LOGS_DIRECTORY . static::$_LOG_FILENAME, 'a');
		fwrite($f, $message . PHP_EOL);
		fclose($f);
	}

	/**
	 * Get Generated Xml
	 * @return mixed
	 */
	public function getGeneratedXml() {
		if ( !$this->xml->children() ) {
			$this->generateMessages();
		}
		return $this->xml->asXML();
	}

	public function view($withOutOfHeader = false) {

		if ( !$withOutOfHeader ) {
			header ("Content-Type:text/xml");
		}

		echo $this->getGeneratedXml();
		return $this;
	}

	/**
	 * Simple Xml Append
	 * @param $to
	 * @param $from
	 */
	protected function simpleXmlAppend($to, $from) {
		$toDom = dom_import_simplexml($to);
		$fromDom = dom_import_simplexml($from);
		$toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
	}

	/**
	 * Rename XML Node Name
	 * @param \DOMElement $node
	 * @param $newName
	 * @return \DOMElement
	 */
	function renameXmlNodeName(\DOMElement $node, $newName) {
		$newNode = $node->ownerDocument->createElement($newName);

		foreach ($node->childNodes as $child){
			$child = $node->ownerDocument->importNode($child, true);
			$newNode->appendChild($child, true);
		}

		foreach ($node->attributes as $attrName => $attrNode) {
			$newNode->setAttribute($attrName, $attrNode);
		}

		$newNode->ownerDocument->replaceChild($newNode, $node);

		return $newNode;
	}

	public function __destruct() {
		unset($this);
	}
}
