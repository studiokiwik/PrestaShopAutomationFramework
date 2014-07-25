<?php

namespace PrestaShop\ShopCapability;

use PrestaShop\OptionProvider;

class BackOfficeNavigation extends ShopCapability
{
	public static $crud_url_settings;
	private static $controller_links;

	public function setup()
	{
		static::$crud_url_settings = [
			'AdminTaxes' => ['object_name' => 'tax'],
			'AdminTaxRulesGroup' => ['object_name' => 'tax_rules_group']
		];
	}


	/**
	* Returns an array with controller names as key and URLs as values.
	* Assumes the browser is on a Back-Office page
	*/
	public function getMenuLinks()
	{
		$links = [];

		$browser = $this->getShop()->getBrowser();
		$maintabs = $browser->find('li.maintab', ['unique' => false]);
		foreach ($maintabs as $maintab)
		{
			$as = $maintab->findElements(\WebDriverBy::tagName('a'));
			foreach ($as as $a)
			{
				$href = $a->getAttribute('href');
				$m = [];
				if (preg_match('/\?controller=(\w+)\b/', $href, $m))
				{
					$links[$m[1]] = $href;
				}
			}
		}

		return $links;
	}

	/**
	 * Construct a normalized link to a CRUD action
	 * 
	 * @param  string $controller_name
	 * @param  string $action [new, view, edit, delete]
	 * @param  int $id optional id of entity
	 * @return string
	 */
	public function getCRUDLink($controller_name, $action, $id = null)
	{
		if (isset(static::$crud_url_settings[$controller_name]))
		{
			$data = static::$crud_url_settings[$controller_name];

			if ($id === null && $action !== 'new')
				throw new \Exception('Missing id parameter for action other than `new`.');

			if (!static::$controller_links)
				static::$controller_links = $this->getMenuLinks();

			if (!isset(static::$controller_links[$controller_name]))
				throw new Exception(sprintf('Could not find controller link %s.', $controller_name));
				
			$base = static::$controller_links[$controller_name];

			$actmap = [
				'new' => 'add'.$data['object_name'],
				'view' => 'view'.$data['object_name'],
				'edit' => 'update'.$data['object_name'],
				'delete' => 'delete'.$data['object_name']
			];

			if (!isset($actmap[$action]))
				throw new \Exception(sprintf('Unknown action %s.', $action));

			$link = $base.'&'.$actmap[$action];

			if ($action !== 'new')
				$link .= '&id_'.$data['object_name'].'='.$id;

			return $link;
		}
		else
			throw new \Exception(sprintf('CRUD parameters for %s are not defined.', $controller_name));
	}

	/**
	 * Logs in to the back-office.
	 * Options may include: admin_email, admin_password, stay_logged_in
	 */
	public function login($options = [])
	{
		$options = OptionProvider::addDefaults('BackOfficeLogin', $options);

		$browser = $this->getShop()->getBrowser();
		$browser
		->visit($this->getShop()->getBackOfficeURL())
		->fillIn('#email', $options['admin_email'])
		->fillIn('#passwd', $options['admin_password'])
		->checkbox('#stay_logged_in', $options['stay_logged_in'])
		->click('button[name=submitLogin]')
		->ensureElementShowsUpOnPage('#maintab-AdminDashboard', 15);

		return $this;
	}

	/**
	* Visit a controller page
	* e.g. AdminDashboard
	*
	* Preconditions: be on a back-office page
	*/
	public function visit($controller_name)
	{
		$links = $this->getMenuLinks();
		$browser = $this->getShop()->getBrowser();
		if (isset($links[$controller_name]))
		{
			return $browser->visit($links[$controller_name]);
		}
		else
			throw new \PrestaShop\Exception\AdminControllerNotFoundException();
	}
}
