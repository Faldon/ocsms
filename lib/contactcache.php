<?php
/**
 * ownCloud - ocsms
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @contributor: stagprom <https://github.com/stagprom/>
 * @copyright Loic Blot 2014-2015
 */

namespace OCA\OcSms\Lib;

class ContactCache {
	/**
	* @var array used to cache the parsed contacts for every request
	*/
	private $contacts;
	private $contactsInverted;
	private $contactPhotos;

	private $cfgMapper;
	private $contactsManager;

	public function __construct ($cfgMapper, $contactsManager) {
		$contacts = array();
		$contactPhotos = array();
		$contactsInverted = array();
		
		$this->cfgMapper = $cfgMapper;
		$this->contactsManager = $contactsManager;
	}

	public function getContacts() {
		// Only load contacts if they aren't in the buffer
		if(count($this->contacts) == 0) {
			$this->loadContacts();
		}
		return $this->contacts;
	}

	public function getInvertedContacts() {
		// Only load contacts if they aren't in the buffer
		if(count($this->contactsInverted) == 0) {
			$this->loadContacts();
		}
		return $this->contactsInverted;
	}

	public function getContactPhotos() {
		// Only load contacts if they aren't in the buffer
		if(count($this->contactPhotos) == 0) {
			$this->loadContacts();
		}
		return $this->contactPhotos;
	}

	/**
	 * Partially importe this function from owncloud Chat app
	 * https://github.com/owncloud/chat/blob/master/app/chat.php
	 */
	private function loadContacts() {
		$this->contacts = array();
		$this->contactsInverted = array();

		// Cache country because of loops
                $configuredCountry = $this->cfgMapper->getCountry();

		$cm = $this->contactsManager;
		if ($cm == null) {
			return;
		}

		$result = $cm->search('',array('FN'));

		foreach ($result as $r) {
			if (isset ($r["TEL"])) {
				$phoneIds = $r["TEL"];
				if (is_array($phoneIds)) {
					$countPhone = count($phoneIds);
					for ($i=0; $i < $countPhone; $i++) {
						$this->pushPhoneNumberToCache($phoneIds[$i], $r["FN"], $configuredCountry);
					}
				}
				else {
					$this->pushPhoneNumberToCache($phoneIds, $r["FN"], $configuredCountry);
				}
				
				if (isset ($r["PHOTO"])) {
					// Remove useless prefix
					$photoURL = preg_replace("#^VALUE=uri:#","",$r["PHOTO"], 1);
					$this->contactPhotos[$r["FN"]] = $photoURL;
				}
			}
		}
	}

	private function pushPhoneNumberToCache($rawPhone, $contactName, $country) {

		$phoneNb = PhoneNumberFormatter::format($country, $rawPhone);
		$this->contacts[$phoneNb] = $contactName;
		// Inverted contacts
		if (!isset($this->contactsInverted[$contactName])) {
			$this->contactsInverted[$contactName] = array();
		}
		array_push($this->contactsInverted[$contactName], $phoneNb);
	}
};

?>
