<?php
/**
 * phpQuery is a server-side, chainable, CSS3 selector driven
 * Document Object Model (DOM) API based on jQuery JavaScript Library.
 *
 * @version 0.9.5 beta3
 * @link http://code.google.com/p/phpquery/
 * @link http://phpquery-library.blogspot.com/
 * @link http://jquery.com/
 * @author Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @package phpQuery
 */

// class names for instanceof
// TODO move them as class constants into phpQuery
define('DOMDOCUMENT', 'DOMDocument');
define('DOMELEMENT', 'DOMElement');
define('DOMNODELIST', 'DOMNodeList');
define('DOMNODE', 'DOMNode');
require_once(dirname(__FILE__).'/DOMEvent.php');
require_once(dirname(__FILE__).'/DOMDocumentWrapper.php');
require_once(dirname(__FILE__).'/phpQueryEvents.php');
require_once(dirname(__FILE__).'/Callback.php');
require_once(dirname(__FILE__).'/phpQueryObject.php');
/**
 * Static namespace for phpQuery functions.
 *
 * @author Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 * @package phpQuery
 */
abstract class phpQuery {
	public static $debug = false;
	public static $documents = array();
	// TODO
	public static $defaultDocumentID = null;
	public static $lastDomId = null;
//	public static $defaultDoctype = 'html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"';
	/**
	 * @deprecated
	 *
	 * @var unknown_type
	 */
	public static $defaultDoctype = '';
	public static $defaultEncoding = 'UTF-8';
	/** TODO */
	public static $defaultCharset = 'UTF-8';
	/**
	 * Static namespace for plugins.
	 *
	 * @var object
	 */
	public static $plugins = array();
	/**
	 * List of loaded plugins.
	 *
	 * @var unknown_type
	 */
	public static $pluginsLoaded = array();
	public static $pluginsMethods = array();
	public static $pluginsStaticMethods = array();
	/**
	 * Hosts allowed for AJAX connections.
	 * Dot '.' means $_SERVER['HTTP_HOST'] (if any).
	 *
	 * @var array
	 */
	public static $ajaxAllowedHosts = array(
		'.'
	);
	/**
	 * AJAX settings.
	 *
	 * @var array
	 * XXX should it be static or not ?
	 */
	public static $ajaxSettings = array(
		'url' => '',//TODO
		'global' => true,
		'type' => "GET",
		'timeout' => null,
		'contentType' => "application/x-www-form-urlencoded",
		'processData' => true,
//		'async' => true,
		'data' => null,
		'username' => null,
		'password' => null,
		'accepts' => array(
			'xml' => "application/xml, text/xml",
			'html' => "text/html",
			'script' => "text/javascript, application/javascript",
			'json' => "application/json, text/javascript",
			'text' => "text/plain",
			'_default' => "*/*"
		)
	);
	public static $lastModified = null;
	public static $active = 0;
	/**
	 * Multi-purpose function.
	 * Use pq() as shortcut.
	 *
	 * In below examples, $pq is any result of pq(); function.
	 * *************
	 * 1. Import markup into existing document (without any attaching):
	 * - Import into selected document:
	 *   pq('<div/>')				// DOESNT accept text nodes at beginning of input string !
	 * - Import into document with ID from $pq->getDocumentID():
	 *   pq('<div/>', $pq->getDocumentID())
	 * - Import into same document as DOMNode belongs to:
	 *   pq('<div/>', DOMNode)
	 * - Import into document from phpQuery object:
	 *   pq('<div/>', $pq)
	 * *************
	 * 2. Run query:
	 * - Run query on last selected document:
	 *   pq('div.myClass')
	 * - Run query on document with ID from $pq->getDocumentID():
	 *   pq('div.myClass', $pq->getDocumentID())
	 * - Run query on same document as DOMNode belongs to and use node(s)as root for query:
	 *   pq('div.myClass', DOMNode)
	 * - Run query on document from phpQuery object
	 *   and use object's stack as root node(s) for query:
	 *   pq('div.myClass', $pq)
	 *
	 * @param string|DOMNode|DOMNodeList|array	$arg1	HTML markup, CSS Selector, DOMNode or array of DOMNodes
	 * @param string|phpQuery|DOMNode	$context	DOM ID from $pq->getDocumentID(), phpQuery object (determines also query root) or DOMNode (determines also query root)
	 *
	 * @return	phpQuery|false			phpQuery object or false in case of error.
	 */
	/**
	 * Enter description here...
	 *
	 * @return false|phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public static function pq($arg1, $context = null) {
		if (! $context) {
			$domId = self::$lastDomId;
			if (! $domId)
				throw new Exception("Can't use last created DOM, because there isn't any. Use phpQuery::newDocument() first.");
//		} else if (is_object($context) && ($context instanceof PHPQUERY || is_subclass_of($context, 'phpQueryObject')))
		} else if (is_object($context) && $context instanceof phpQueryObject)
			$domId = $context->getDocumentID();
		else if ($context instanceof DOMDOCUMENT) {
			$domId = self::getDocumentID($context);
			if (! $domId) {
				//throw new Exception('Orphaned DOMDocument');
				$domId = self::newDocument($context)->getDocumentID();
			}
		} else if ($context instanceof DOMNODE) {
			$domId = self::getDocumentID($context);
			if (! $domId){
				throw new Exception('Orphaned DOMNode');
//				$domId = self::newDocument($context->ownerDocument);
			}
		} else
			$domId = $context;
		if ($arg1 instanceof phpQueryObject) {
//		if (is_object($arg1) && (get_class($arg1) == 'phpQueryObject' || $arg1 instanceof PHPQUERY || is_subclass_of($arg1, 'phpQueryObject'))) {
			/**
			 * Return $arg1 or import $arg1 stack if document differs:
			 * pq(pq('<div/>'))
			 */
			if ($arg1->getDocumentID() == $domId)
				return $arg1;
			$class = get_class($arg1);
			// support inheritance by passing old object to overloaded constructor
			$phpQuery = $class != 'phpQuery'
				? new $class($arg1, $domId)
				: new phpQueryObject($domId);
			$phpQuery->elements = array();
			foreach($arg1->elements as $node)
				$phpQuery->elements[] = $phpQuery->document->importNode($node, true);
			return $phpQuery;
		} else if ($arg1 instanceof DOMNODE || (is_array($arg1) && isset($arg1[0]) && $arg[0] instanceof DOMNODE)) {
			/**
			 * Wrap DOM nodes with phpQuery object, import into document when needed:
			 * pq(array($domNode1, $domNode2))
			 */
			$phpQuery = new phpQueryObject($domId);
			if (!($arg1 instanceof DOMNODELIST) && ! is_array($arg1))
				$arg1 = array($arg1);
			$phpQuery->elements = array();
			foreach($arg1 as $node)
				$phpQuery->elements[] = ! $node->ownerDocument->isSameNode($phpQuery->document)
					? $phpQuery->document->importNode($node, true)
					: $node;
			return $phpQuery;
		} else if (self::isMarkup($arg1)) {
			/**
			 * Import HTML:
			 * pq('<div/>')
			 */
			$phpQuery = new phpQueryObject($domId);
			return $phpQuery->newInstance(
				$phpQuery->documentWrapper->import($arg1)
			);
		} else {
			/**
			 * Run CSS query:
			 * pq('div.myClass')
			 */
			$phpQuery = new phpQueryObject($domId);
//			if ($context && ($context instanceof PHPQUERY || is_subclass_of($context, 'phpQueryObject')))
			if ($context && $context instanceof phpQueryObject)
				$phpQuery->elements = $context->elements;
			else if ($context && $context instanceof DOMNODELIST) {
				$phpQuery->elements = array();
				foreach($context as $node)
					$phpQuery->elements[] = $node;
			} else if ($context && $context instanceof DOMNODE)
				$phpQuery->elements = array($context);
			return $phpQuery->find($arg1);
		}
	}
	/**
	 * Sets default document to $id. Document has to be loaded prior
	 * to using this method.
	 * $id can be retrived via getDocumentID() or getDocumentIDRef().
	 *
	 * @param unknown_type $id
	 */
	public static function selectDocument($id) {
		$id = self::getDocumentID($id);
		self::debug("Selecting document '$id' as default one");
		self::$lastDomId = self::getDocumentID($id);
	}
	/**
	 * Returns document with id $id or last used as phpQueryObject.
	 * $id can be retrived via getDocumentID() or getDocumentIDRef().
	 * Chainable.
	 *
	 * @see phpQuery::selectDocument()
	 * @param unknown_type $id
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public static function getDocument($id = null) {
		if ($id)
			phpQuery::selectDocument($id);
		else
			$id = phpQuery::$lastDomId;
		return new phpQueryObject($id);
	}
	/**
	 * Creates new document from markup.
	 * Chainable.
	 *
	 * @param unknown_type $markup
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 * @TODO support DOMDocument
	 */
	public static function newDocument($markup = null, $contentType = null) {
		if (! $markup)
			$markup = '';
		$documentID = phpQuery::createDocumentWrapper($markup, $contentType);
		return new phpQueryObject($documentID);
	}
	public static function newDocumentHTML($markup = null, $charset = 'utf-8') {
		if (!isset($charset))
			$charset = self::$defaultCharset;
		return self::newDocument($markup, "text/html;charset=$charset");
	}
	public static function newDocumentXML($markup = null, $charset = 'utf-8') {
		return self::newDocument($markup, "text/xml;charset=$charset");
	}
	public static function newDocumentXHTML($markup = null, $charset = 'utf-8') {
		return self::newDocument($markup, "application/xhtml+xml;charset=$charset");
	}
	public static function newDocumentPHP($markup = null, $contentType = "text/html;charset=utf-8") {
		$markup = phpQuery::phpToMarkup($markup);
		return self::newDocument($markup, $contentType);
	}
	public static function phpToMarkup($php, $charset = 'utf-8') {
		$regexes = array(
			'@(<(?!\\?)(?:[^>]|\\?>)+\\w+\\s*=\\s*)(\')([^\']*)<?php?(.*?)(?:\\?>)([^\']*)\'@s',
			'@(<(?!\\?)(?:[^>]|\\?>)+\\w+\\s*=\\s*)(")([^"]*)<?php?(.*?)(?:\\?>)([^"]*)"@s',
		);
		foreach($regexes as $regex)
			while (preg_match($regex, $php, $matches)) {
				$php = preg_replace_callback(
					$regex,
					create_function('$m, $charset = "'.$charset.'"',
						'return $m[1].$m[2]
							.htmlspecialchars("<?php".$m[4]."?>", ENT_QUOTES|ENT_NOQUOTES, $charset)
							.$m[5].$m[2];'
					),
					$php
				);
}
		$regex = '@(^|>[^<]*)+?(<\?php(.*?)(\?>))@s';
//preg_match_all($regex, $php, $matches);
//var_dump($matches);
		$php = preg_replace($regex, '\\1<php><!-- \\3 --></php>', $php);
		return $php;
	}
	public static function markupToPHP($content) {
		if ($content instanceof phpQueryObject)
			$content = $content->htmlOuter();
		/* <php>...</php> to <?php...?> */
		$content = preg_replace_callback(
			'@<php>\s*<!--(.*?)-->\s*</php>@s',
			create_function('$m',
				'return "<'.'?php \n".htmlspecialchars_decode($m[1])."\n ?'.'>";'
			),
			$content
		);
		/*$content = str_replace(
			array('<?php<!--', '<?php <!--', '-->?>', '--> ?>'),
			array('<?php', '<?php', '?>', '?>'),
			$content
		);*/
		$regexes = array(
			'@(<(?!\\?)(?:[^>]|\\?>)+\\w+\\s*=\\s*)(\')([^\']*)(?:&lt;|%3C)\\?(?:php)?(.*?)(?:\\?(?:&gt;|%3E))([^\']*)\'@s',
			'@(<(?!\\?)(?:[^>]|\\?>)+\\w+\\s*=\\s*)(")([^"]*)(?:&lt;|%3C)\\?(?:php)?(.*?)(?:\\?(?:&gt;|%3E))([^"]*)"@s',
		);
		foreach($regexes as $regex)
			while (preg_match($regex, $content))
				$content = preg_replace_callback(
					$regex,
					create_function('$m',
						'return $m[1].$m[2].$m[3]."<?php \n"
							.str_replace(
								array("%20", "%3E", "%09", "&#10;", "&#9;", "%7B", "%24", "%7D", "%22", "%5B", "%5D"),
								array(" ", ">", "	", "\n", "	", "{", "$", "}", \'"\', "[", "]"),
								htmlspecialchars_decode($m[4])
							)
							." \n?>".$m[5].$m[2];'
					),
					$content
				);
		return $content;
	}
	/**
	 * Creates new document from file $file.
	 * Chainable.
	 *
	 * @param string $file URLs allowed. See File wrapper page at php.net for more supported sources.
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public static function newDocumentFile($file, $contentType = null) {
		$documentID = self::createDocumentWrapper(
			file_get_contents($file), $contentType
		);
		return new phpQueryObject($documentID);
	}
	public static function newDocumentFileHTML($file, $charset = 'utf-8') {
		return self::newDocumentFile($file, "text/html;charset=$charset");
	}
	public static function newDocumentFileXML($file, $charset = 'utf-8') {
		return self::newDocumentFile($file, "text/xml;charset=$charset");
	}
	public static function newDocumentFileXHTML($file, $charset = 'utf-8') {
		return self::newDocumentFile($file, "application/xhtml+xml;charset=$charset");
	}
	public static function newDocumentFilePHP($file, $contentType = null) {
		return self::newDocumentPHP(file_get_contents($file), $contentType);
	}
	/**
	 * Reuses existing DOMDocument object.
	 * Chainable.
	 *
	 * @param $document DOMDocument
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPick
	 * up
	 */
	public static function loadDocument($document) {
		// TODO
		die('TODO loadDocument');
	}
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $html
	 * @param unknown_type $domId
	 * @return unknown New DOM ID
	 * @todo support PHP tags in input
	 * @todo support passing DOMDocument object from self::loadDocument
	 */
	protected static function createDocumentWrapper($html, $contentType = null, $documentID = null) {
		if (function_exists('domxml_open_mem')) {
			throw new Exception("Old PHP4 DOM XML extension detected. phpQuery won't work until this extension is enabled.");
			return null;
		}
		$id = $documentID
			? $documentID
			: md5(microtime());
		$document = null;
		if ($html instanceof DOMDOCUMENT) {
			// TODO support cloning of DOMDocumentWrapper
			if (self::getDocumentID($html)) {
				// document already exists in phpQuery::$documents, make a copy
				$document = clone $html;
			} else {
				// new document, add it to phpQuery::$documents
				$wrapper = new DOMDocumentWrapper($html, $contentType);
			}
		} else {
			$wrapper = new DOMDocumentWrapper($html, $contentType);
		}
		// create document
		phpQuery::$documents[ $id ] = $wrapper;
		// remember last loaded document
		return self::$lastDomId = $id;
	}
	/**
	 * Deprecated, use phpQuery::plugin() instead.
	 *
	 * @deprecated
	 * @param $class
	 * @param $file
	 * @return unknown_type
	 */
	public static function extend($class, $file = null) {
		return self::plugin($class, $file);
	}
	/**
	 * Extend phpQuery with $class from $file.
	 *
	 * @param string $class Extending class name. Real class name can be prepended phpQuery_.
	 * @param string $file Filename to include. Defaults to "{$class}.php".
	 */
	public static function plugin($class, $file = null) {
		// TODO $class checked agains phpQuery_$class
//		if (strpos($class, 'phpQuery') === 0)
//			$class = substr($class, 8);
		if (in_array($class, self::$pluginsLoaded))
			return;
		if (! $file)
			$file = $class.'.php';
		require_once($file);
		self::$pluginsLoaded[] = $class;
		// static methods
		if (class_exists('phpQueryPlugin_'.$class)) {
			$realClass = 'phpQueryPlugin_'.$class;
			$vars = get_class_vars($realClass);
			$loop = isset($vars['phpQueryMethods'])
				&& ! is_null($vars['phpQueryMethods'])
				? $vars['phpQueryMethods']
				: get_class_methods($realClass);
			foreach($loop as $method) {
				if ($method == '__initialize')
					continue;
				if (! is_callable(array($realClass, $method)))
					continue;
				if (isset(self::$pluginsStaticMethods[$method])) {
					throw new Exception("Duplicate method '{$method}' from plugin '{$c}' conflicts with same method from plugin '".self::$pluginsStaticMethods[$method]."'");
					return;
				}
				self::$pluginsStaticMethods[$method] = $class;
			}
			if (method_exists($realClass, '__initialize'))
				call_user_func_array(array($realClass, '__initialize'), array());
		}
		// object methods
		if (class_exists('phpQueryObjectPlugin_'.$class)) {
			$realClass = 'phpQueryObjectPlugin_'.$class;
			$vars = get_class_vars($realClass);
			$loop = isset($vars['phpQueryMethods'])
				&& ! is_null($vars['phpQueryMethods'])
				? $vars['phpQueryMethods']
				: get_class_methods($realClass);
			foreach($loop as $method) {
				if (! is_callable(array($realClass, $method)))
					continue;
				if (isset(self::$pluginsMethods[$method])) {
					throw new Exception("Duplicate method '{$method}' from plugin '{$c}' conflicts with same method from plugin '".self::$pluginsMethods[$method]."'");
					return;
				}
				self::$pluginsMethods[$method] = $class;
			}
		}
		return true;
	}
	/**
	 * Unloades all or specified document from memory.
	 *
	 * @param mixed $documentID @see phpQuery::getDocumentID() for supported types.
	 */
	public static function unloadDocuments($id = null) {
		if (isset($id)) {
			if ($id = self::getDocumentID($id))
				unset(phpQuery::$documents[$id]);
		} else
			unset(phpQuery::$documents);
	}
	/**
	 * Parses phpQuery object or HTML result against PHP tags and makes them active.
	 *
	 * @param phpQuery|string $content
	 * @deprecated
	 * @return string
	 */
	public static function unsafePHPTags($content) {
		return self::markupToPHP($content);
	}
	public static function DOMNodeListToArray($DOMNodeList) {
		$array = array();
		if (! $DOMNodeList)
			return $array;
		foreach($DOMNodeList as $node)
			$array[] = $node;
		return $array;
	}
	/**
	 * Checks if $input is HTML string, which has to start with '<'.
	 *
	 * @deprecated
	 * @param String $input
	 * @return Bool
	 * @todo still used ?
	 */
	public static function isMarkup($input) {
		return substr(trim($input), 0, 1) == '<';
	}
	public function debug($text) {
		if (self::$debug)
			print var_dump($text);
	}
	/**
	 * Make an AJAX request.
	 *
	 * @param array See $options http://docs.jquery.com/Ajax/jQuery.ajax#toptions
	 * Additional options are:
	 * 'document' - document for global events, @see phpQuery::getDocumentID()
	 * 'http_referer' - TODO; not implemented
	 * 'requested_with' - TODO; not implemented (X-Requested-With)
	 * @return Zend_Http_Client
	 * @link http://docs.jquery.com/Ajax/jQuery.ajax
	 *
	 * @TODO $options['cache']
	 * @TODO $options['processData']
	 * @TODO support callbackStructure like each() and map()
	 */
	public static function ajax($options = array(), $xhr = null) {
		$options = array_merge(
			self::$ajaxSettings, $options
		);
		$documentID = isset($options['document'])
			? self::getDocumentID($options['document'])
			: null;
		if ($xhr) {
			// reuse existing XHR object, but clean it up
			$client = $xhr;
//			$client->setParameterPost(null);
//			$client->setParameterGet(null);
			$client->setAuth(false);
			$client->setHeaders("If-Modified-Since", null);
			$client->setHeaders("Http-Referer", null);
			$client->resetParameters();
		} else {
			// create new XHR object
			require_once('Zend/Http/Client.php');
			$client = new Zend_Http_Client();
			$client->setCookieJar();
		}
		if (isset($options['timeout']))
			$client->setConfig(array(
				'timeout'      => $options['timeout'],
			));
//			'maxredirects' => 0,
		foreach(self::$ajaxAllowedHosts as $k => $host)
			if ($host == '.' && isset($_SERVER['HTTP_HOST']))
				self::$ajaxAllowedHosts[$k] = $_SERVER['HTTP_HOST'];
		$host = parse_url($options['url'], PHP_URL_HOST);
		if (! in_array($host, self::$ajaxAllowedHosts)) {
			throw new Exception("Request not permitted, host '$host' not present in phpQuery::\$ajaxAllowedHosts");
			return false;
		}
		$client->setUri($options['url']);
		$client->setMethod($options['type']);
		if (isset($options['httpReferer']) && $options['httpReferer'])
			$client->setHeaders('Http-Referer', $options['httpReferer']);
		$client->setHeaders(array(
//			'content-type' => $options['contentType'],
			'user-agent' => 'Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9a8) Gecko/2007100619 GranParadiso/3.0a8',
			'accept-charset' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
		));
		if ($options['username'])
			$client->setAuth($options['username'], $options['password']);
		if (isset($options['ifModified']) && $options['ifModified'])
			$client->setHeaders("If-Modified-Since",
				self::$lastModified
					? self::$lastModified
					: "Thu, 01 Jan 1970 00:00:00 GMT"
			);
		$client->setHeaders("Accept",
			isset($options['dataType'])
			&& isset(self::$ajaxSettings['accepts'][ $options['dataType'] ])
				? self::$ajaxSettings['accepts'][ $options['dataType'] ].", */*"
				: self::$ajaxSettings['accepts']['_default']
		);
		// TODO $options['processData']
		if ($options['data'] instanceof phpQueryObject) {
			$serialized = $options['data']->serializeArray($options['data']);
			$options['data'] = array();
			foreach($serialized as $r)
				$options['data'][ $r['name'] ] = $r['value'];
		}
		if (strtolower($options['type']) == 'get') {
			$client->setParameterGet($options['data']);
		} else if (strtolower($options['type']) == 'post') {
			$client->setEncType($options['contentType']);
			$client->setParameterPost($options['data']);
		}
		if (self::$active == 0 && $options['global'])
			phpQueryEvents::trigger($documentID, 'ajaxStart');
		self::$active++;
		// beforeSend callback
		if (isset($options['beforeSend']) && $options['beforeSend'])
			phpQuery::callbackRun($options['beforeSend'], array($client));
		// ajaxSend event
		if ($options['global'])
			phpQueryEvents::trigger($documentID, 'ajaxSend', array($client, $options));
		if (phpQuery::$debug) {
			self::debug("{$options['type']}: {$options['url']}\n");
			self::debug("Options: <pre>".var_export($options, true)."</pre>\n");
//			if ($client->getCookieJar())
//				self::debug("Cookies: <pre>".var_export($client->getCookieJar()->getMatchingCookies($options['url']), true)."</pre>\n");
		}
		// request
		$response = $client->request();
		if (phpQuery::$debug) {
			self::debug('Status: '.$response->getStatus().' / '.$response->getMessage());
			self::debug($client->getLastRequest());
			self::debug($response->getHeaders());
		}
		if ($response->isSuccessful()) {
			// XXX tempolary
			self::$lastModified = $response->getHeader('Last-Modified');
			if (isset($options['success']) && $options['success'])
				phpQuery::callbackRun($options['success'], array($response->getBody(), $response->getStatus()));
			if ($options['global'])
				phpQueryEvents::trigger($documentID, 'ajaxSuccess', array($client, $options));
		} else {
			if (isset($options['error']) && $options['error'])
				phpQuery::callbackRun($options['error'], array($client, $response->getStatus(), $response->getMessage()));
			if ($options['global'])
				phpQueryEvents::trigger($documentID, 'ajaxError', array($client, /*$response->getStatus(),*/$response->getMessage(), $options));
		}
		if (isset($options['complete']) && $options['complete'])
			phpQuery::callbackRun($options['complete'], array($client, $response->getStatus()));
		if ($options['global'])
			phpQueryEvents::trigger($documentID, 'ajaxComplete', array($client, $options));
		if ($options['global'] && ! --self::$active)
			phpQueryEvents::trigger($documentID, 'ajaxStop');
		return $client;
//		if (is_null($domId))
//			$domId = self::$lastDomId ? self::$lastDomId : false;
//		return new phpQueryAjaxResponse($response, $domId);
	}
	/**
	 * Enter description here...
	 *
	 * @param array|phpQuery $data
	 *
	 */
	public static function param($data) {
		return http_build_query($data, null, '&');
	}

	public static function get($url, $data = null, $callback = null, $type = null) {
		if (!is_array($data)) {
			$callback = $data;
			$data = null;
		}
		// TODO some array_values on this shit
		return phpQuery::ajax(array(
			'type' => 'GET',
			'url' => $url,
			'data' => $data,
			'success' => $callback,
			'dataType' => $type,
		));
	}

	public static function post($url, $data = null, $callback = null, $type = null) {
		if (!is_array($data)) {
			$callback = $data;
			$data = null;
		}
		return phpQuery::ajax(array(
			'type' => 'POST',
			'url' => $url,
			'data' => $data,
			'success' => $callback,
			'dataType' => $type,
		));
	}
	public static function ajaxSetup($options) {
		self::$ajaxSettings = array_merge(
			self::$ajaxSettings,
			$options
		);
	}
	public static function ajaxAllowHost($host1, $host2 = null, $host3 = null) {
		$loop = is_array($host1)
			? $host1
			: func_get_args();
		foreach($loop as $host) {
			if ($host && ! in_array($host, phpQuery::$ajaxAllowedHosts)) {
				phpQuery::$ajaxAllowedHosts[] = $host;
			}
		}
	}
	public static function ajaxAllowURL($url1, $url2 = null, $url3 = null) {
		$loop = is_array($url1)
			? $url1
			: func_get_args();
		foreach($loop as $url)
			phpQuery::ajaxAllowHost(parse_url($url, PHP_URL_HOST));
	}
	/**
	 * Returns JSON representation of $data.
	 *
	 * @static
	 * @param mixed $data
	 * @return string
	 */
	public static function toJSON($data) {
		if (function_exists('json_encode'))
			return json_encode($data);
		require_once('Zend/Json/Encoder');
		return Zend_Json_Encoder::encode($data);
	}
	/**
	 * Parses JSON into proper PHP type.
	 *
	 * @static
	 * @param string $json
	 * @return mixed
	 */
	public static function parseJSON($json) {
		if (function_exists('json_decode'))
			return json_decode($json, true);
		require_once('Zend/Json/Decoder');
		return Zend_Json_Decoder::decode($json);
	}
	/**
	 * Returns source's document ID.
	 *
	 * @param $source DOMNode|phpQueryObject
	 * @return string
	 */
	public static function getDocumentID($source) {
		if ($source instanceof DOMDOCUMENT) {
			foreach(phpQuery::$documents as $id => $document) {
				if ($source->isSameNode($document['document']))
					return $id;
			}
		} else if ($source instanceof DOMNODE) {
			foreach(phpQuery::$documents as $id => $document) {
				if ($source->ownerDocument->isSameNode($document['document']))
					return $id;
			}
		} else if ($source instanceof phpQueryObject)
			return $source->getDocumentID();
		else if (is_string($source) && isset(phpQuery::$documents[$source]))
			return $source;
	}
	/**
	 * Get DOMDocument object related to $source.
	 * Returns null if such document doesn't exist.
	 *
	 * @param $source DOMNode|phpQueryObject|string
	 * @return string
	 */
	public static function getDOMDocument($source) {
		if ($source instanceof DOMDOCUMENT)
			return $source;
		$source = self::getDocumentID($source);
		return $source
			? self::$documents[$id]['document']
			: null;
	}

	// UTILITIES
	// http://docs.jquery.com/Utilities

	/**
	 *
	 * @return unknown_type
	 * @link http://docs.jquery.com/Utilities/jQuery.makeArray
	 */
	public static function makeArray($obj) {
		$array = array();
		if (is_object($object) && $object instanceof DOMNODELIST) {
			foreach($object as $value)
				$array[] = $value;
		} else if (is_object($object) && ! ($object instanceof Iterator)) {
			foreach(get_object_vars($object) as $name => $value)
				$array[0][$name] = $value;
		} else {
			foreach($object as $name => $value)
				$array[0][$name] = $value;
		}
		return $array;
	}
	public static function inArray($value, $array) {
		return in_array($value, $array);
	}
	/**
	 *
	 * @param $object
	 * @param $callback
	 * @return unknown_type
	 * @link http://docs.jquery.com/Utilities/jQuery.each
	 */
	public static function each($object, $callback, $param1 = null, $param2 = null, $param3 = null) {
		$paramStructure = null;
		if (func_num_args() > 2) {
			$paramStructure = func_get_args();
			$paramStructure = array_slice($paramStructure, 2);
		}
		if (is_object($object) && ! ($object instanceof Iterator)) {
			foreach(get_object_vars($object) as $name => $value)
				phpQuery::callbackRun($callback, array($name, $value), $paramStructure);
		} else {
			foreach($object as $name => $value)
				phpQuery::callbackRun($callback, array($name, $value), $paramStructure);
		}
	}
	/**
	 *
	 * @link http://docs.jquery.com/Utilities/jQuery.map
	 */
	public static function map($array, $callback, $param1 = null, $param2 = null, $param3 = null) {
		$result = array();
		$paramStructure = null;
		if (func_num_args() > 2) {
			$paramStructure = func_get_args();
			$paramStructure = array_slice($paramStructure, 2);
		}
		foreach($array as $v) {
			$vv = phpQuery::callbackRun($callback, array($v), $paramStructure);
//			$callbackArgs = $args;
//			foreach($args as $i => $arg) {
//				$callbackArgs[$i] = $arg instanceof CallbackParam
//					? $v
//					: $arg;
//			}
//			$vv = call_user_func_array($callback, $callbackArgs);
			if (is_array($vv))  {
				foreach($vv as $vvv)
					$result[] = $vvv;
			} else if ($vv !== null) {
				$result[] = $vv;
			}
		}
		return $result;
	}
	/**
	 *
	 * @param $callback Callback
	 * @param $params
	 * @param $paramStructure
	 * @return unknown_type
	 */
	public static function callbackRun($callback, $params, $paramStructure = null) {
		if (! $callback)
			return;
		if ($callback instanceof CallbackReference) {
			// TODO support ParamStructure to select which $param push to reference
			if (isset($params[0]))
				$callback->callback = $params[0];
			return true;
		}
		if ($callback instanceof Callback) {
			$paramStructure = $callback->params;
			$callback = $callback->callback;
		}
		if (! $paramStructure)
			return call_user_func_array($callback, $params);
		$p = 0;
		foreach($paramStructure as $i => $v) {
			$paramStructure[$i] = $v instanceof CallbackParam
				? $params[$p++]
				: $v;
		}
		return call_user_func_array($callback, $paramStructure);
	}
	/**
	 * Merge 2 phpQuery objects.
	 * @param array $one
	 * @param array $two
	 * @protected
	 * @todo node lists, phpQueryObject
	 */
	public static function merge($one, $two) {
		$elements = $one->elements;
		foreach($two->elements as $node) {
			$exists = false;
			foreach($elements as $node2) {
				if ($node2->isSameNode($node))
					$exists = true;
			}
			if (! $exists)
				$elements[] = $node;
		}
		return $elements;
//		$one = $one->newInstance();
//		$one->elements = $elements;
//		return $one;
	}
	/**
	 *
	 * @param $array
	 * @param $callback
	 * @param $invert
	 * @return unknown_type
	 * @link http://docs.jquery.com/Utilities/jQuery.grep
	 */
	public static function grep($array, $callback, $invert = false) {
		$result = array();
		foreach($array as $k => $v) {
			$r = call_user_func_array($callback, array($v, $k));
			if ($r === !(bool)$invert)
				$result[] = $v;
		}
		return $result;
	}
	public static function unique($array) {
		return array_unique($array);
	}
	/**
	 *
	 * @param $function
	 * @return unknown_type
	 * @TODO there are problems with non-static methods, second parameter pass it
	 * 	but doesnt verify is method is really callable
	 */
	public static function isFunction($function) {
		return is_callable($function);
	}
	public static function trim($str) {
		return trim($str);
	}
	/* PLUGINS NAMESPACE */
	public static function browserGet($url, $callback, $param1 = null, $param2 = null, $param3 = null) {
		if (self::extend('WebBrowser')) {
			$params = func_get_args();
			return self::callbackRun(array(self::$plugins, 'browserGet'), $params);
		}
	}
	public static function browserPost($url, $data, $callback, $param1 = null, $param2 = null, $param3 = null) {
		if (self::extend('WebBrowser')) {
			$params = func_get_args();
			return self::callbackRun(array(self::$plugins, 'browserPost'), $params);
		}
	}
	public static function browser($ajaxSettings, $callback, $param1 = null, $param2 = null, $param3 = null) {
		if (self::extend('WebBrowser')) {
			$params = func_get_args();
			return self::callbackRun(array(self::$plugins, 'browser'), $params);
		}
	}
}
/**
 * Plugins static namespace class.
 *
 * @author Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 * @package phpQuery
 * @todo move plugin methods here (as statics)
 */
class phpQueryPlugins {
	public function __call($method, $args) {
		if (isset(phpQuery::$pluginsStaticMethods[$method])) {
			$class = phpQuery::$pluginsStaticMethods[$method];
			$realClass = "phpQueryPlugin_$class";
			$return = call_user_func_array(
				array($realClass, $method),
				$args
			);
			return $return
				? $return
				: $this;
		} else
			throw new Exception("Method '{$method}' doesnt exist");
	}
}
/**
 * Shortcut to phpQuery::pq($arg1, $context)
 * Chainable.
 *
 * @see phpQuery::pq()
 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
 * @author Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 * @package phpQuery
 */
function pq($arg1, $context = null) {
	$args = func_get_args();
	return call_user_func_array(
		array('phpQuery', 'pq'),
		$args
	);
}
// add plugins dir and Zend framework to include path
set_include_path(
	get_include_path()
		.PATH_SEPARATOR.dirname(__FILE__).'/'
		.PATH_SEPARATOR.dirname(__FILE__).'/plugins/'
);
// why ? no __call nor __get for statics in php...
phpQuery::$plugins = new phpQueryPlugins();
// include bootstrap file (personal library config)
if (file_exists(dirname(__FILE__).'/bootstrap.php'))
	require_once dirname(__FILE__).'/bootstrap.php';